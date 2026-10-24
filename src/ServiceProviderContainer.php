<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/container.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2025-2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/container
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\Container;

use Fiber;
use Throwable;
use WeakMap;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\Extension\ServiceExtension;
use FastForward\Container\Factory\ServiceFactory;
use FastForward\Container\ServiceProvider\AggregateServiceProvider;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Implements a PSR-11 compliant dependency injection container using a container-interop/service-provider.
 *
 * This container SHALL resolve services by delegating to the factories and extensions defined in the
 * provided provider or ordered provider list.
 *
 * Services are lazily instantiated on first request and
 * cached for subsequent retrieval, enforcing singleton-like behavior within the container scope.
 *
 * The container supports service extension mechanisms by allowing callable extensions to modify or
 * enhance services after construction, based on the service identifier or its concrete class name.
 *
 * If an optional wrapper container is provided, it SHALL be passed to service factories and extensions,
 * allowing for delegation or decoration of service resolution. If omitted, the container defaults to itself.
 */
final class ServiceProviderContainer implements ContainerInterface
{
    /**
     * The container instance used for service resolution and extension application.
     *
     * This property MAY reference another container for delegation, or default to this container instance.
     */
    private readonly PsrContainerInterface $wrapperContainer;

    /**
     * @var ServiceProviderInterface definitions used for extension composition
     */
    private readonly ServiceProviderInterface $serviceProvider;

    /**
     * @var list<ServiceProviderInterface>|null ordered providers when composing first-factory definitions
     */
    private readonly ?array $providers;

    /**
     * Final class-extension values indexed by original and same-class resulting object identities.
     *
     * Weak keys permit discarded input objects to be collected after replacement.
     * Wrapping values in an array distinguishes a cached null result from an absent entry.
     *
     * @var WeakMap<object, array{value: mixed}>|null
     */
    private ?WeakMap $classExtensionResults = null;

    /**
     * Cache of resolved services keyed by their identifier or class name.
     *
     * This array SHALL store already constructed services to enforce singleton-like behavior within the container scope.
     *
     * @var array<string, mixed>
     */
    private array $cache = [];

    /**
     * Active provider entries grouped by Fiber, or by wrapper in the main context.
     *
     * Provider identity permits ordered fallback to another source for the same ID.
     * Completed resolutions remove their entries instead of retaining service graphs.
     *
     * @var WeakMap<object, list<array{container: self, id: string}>>|null
     */
    private static ?WeakMap $resolutionPaths = null;

    /**
     * Constructs a new ServiceProviderContainer instance.
     *
     * This constructor SHALL initialize the container with a service provider and an optional delegating container.
     * If no wrapper container is provided, the container SHALL delegate to itself.
     *
     * @param ServiceProviderInterface|list<ServiceProviderInterface> $serviceProvider a provider or ordered providers; lists keep the first callable factory and compose all extensions
     * @param PsrContainerInterface|null $wrapperContainer An optional container for delegation. Defaults to self.
     */
    public function __construct(
        ServiceProviderInterface|array $serviceProvider,
        ?PsrContainerInterface $wrapperContainer = null,
    ) {
        $this->wrapperContainer = $wrapperContainer ?? $this;
        if (\is_array($serviceProvider)) {
            $this->providers = array_values($serviceProvider);
            $this->serviceProvider = new AggregateServiceProvider(...$this->providers);
        } else {
            $this->providers = null;
            $this->serviceProvider = $serviceProvider;
        }
    }

    /**
     * Determines if the container can return an entry for the given identifier.
     *
     * This method MUST return true for cached entries, callable factories, or callable ID-based extensions.
     * It SHALL NOT execute factories or extensions to discover an entry.
     *
     * @param string $id identifier of the entry to look for
     *
     * @return bool true if the entry exists, false otherwise
     *
     * @throws ContainerException if provider metadata cannot be read
     */
    public function has(string $id): bool
    {
        if ($this->hasEntry($id)) {
            return true;
        }

        try {
            return $this->hasEntry($id, $this->getFactories())
                || $this->hasEntry($id, extensions: $this->serviceProvider->getExtensions());
        } catch (Throwable $throwable) {
            throw ContainerException::forInvalidService($id, $throwable);
        }
    }

    /**
     * Retrieves a service from the container by its identifier.
     *
     * This method SHALL return a cached instance if available, otherwise it resolves
     * the service using the factory provided by the service provider.
     *
     * Extensions SHALL receive the previous value and may replace it. Without a factory,
     * an ID-based extension receives null. The final value SHALL be cached, including null.
     *
     * @param string $id the identifier of the service to retrieve
     *
     * @return mixed the service instance associated with the identifier
     *
     * @throws NotFoundException if neither a callable factory nor an ID-based extension exists
     * @throws ContainerException if factory or extension resolution fails or a dependency cycle exists
     */
    public function get(string $id): mixed
    {
        if (\array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        $context = Fiber::getCurrent() ?? $this->wrapperContainer;
        $activePaths = self::$resolutionPaths ??= new WeakMap();
        $previousPath = $activePaths[$context] ?? [];

        $this->assertEntryIsNotResolving($id, $previousPath);

        $currentEntry = [
            'container' => $this,
            'id' => $id,
        ];
        $activePaths[$context] = [...$previousPath, $currentEntry];

        try {
            return $this->resolveUncachedEntry($id);
        } finally {
            $this->restoreResolutionPath($activePaths, $context, $previousPath);
        }
    }

    /**
     * Rejects re-entry into this provider's active service while allowing ordered fallback to other providers.
     *
     * @param string $id the requested service identifier
     * @param list<array{container: self, id: string}> $path the current execution context's active entries
     *
     * @return void
     *
     * @throws ContainerException if this provider is already resolving the same identifier
     */
    private function assertEntryIsNotResolving(string $id, array $path): void
    {
        foreach ($path as $entry) {
            if ($entry['container'] === $this && $entry['id'] === $id) {
                throw ContainerException::forCircularDependency([...array_column($path, 'id'), $id]);
            }
        }
    }

    /**
     * Restores the caller's path after resolution or releases an empty execution context.
     *
     * @param WeakMap<object, list<array{container: self, id: string}>> $paths active paths by execution context
     * @param object $context the current Fiber or main-context wrapper
     * @param list<array{container: self, id: string}> $previousPath the path before this entry was resolved
     *
     * @return void
     */
    private function restoreResolutionPath(WeakMap $paths, object $context, array $previousPath): void
    {
        if ([] === $previousPath) {
            unset($paths[$context]);

            return;
        }

        $paths[$context] = $previousPath;
    }

    /**
     * Resolves an entry once, preserving construction failures and the final cached value.
     *
     * @param string $id the requested service identifier
     *
     * @return mixed the fully extended service value
     *
     * @throws NotFoundException if neither a factory nor an ID extension can supply the entry
     * @throws ContainerException if reading metadata or constructing the entry fails
     */
    private function resolveUncachedEntry(string $id): mixed
    {
        [$factories, $extensions] = $this->readProviderDefinitions($id);

        $factory = $factories[$id] ?? null;

        if (! \is_callable($factory) && ! \is_callable($extensions[$id] ?? null)) {
            throw NotFoundException::forServiceID($id);
        }

        try {
            $service = $this->createExtendedService($id, $factory, $extensions);
            $alias = $this->inferredClassAlias($id, $service, $factories, $extensions);
        } catch (Throwable $throwable) {
            throw ContainerException::forInvalidService($id, $throwable);
        }

        return $this->cacheResolvedService($id, $service, $alias);
    }

    /**
     * Reads factory and extension metadata in provider order without executing their callables.
     *
     * @param string $id the identifier used to report a metadata failure
     *
     * @return array{array<array-key, mixed>, array<array-key, mixed>} factories followed by extensions
     *
     * @throws ContainerException if provider metadata cannot be read
     */
    private function readProviderDefinitions(string $id): array
    {
        try {
            return [$this->getFactories(), $this->serviceProvider->getExtensions()];
        } catch (Throwable $throwable) {
            throw ContainerException::forInvalidService($id, $throwable);
        }
    }

    /**
     * Reads first-callable factories for a provider list, or the single provider's own policy.
     *
     * Explicit AggregateServiceProvider objects retain their last-factory policy.
     * Definitions are read lazily; no service callback is executed here.
     *
     * @return array<string, mixed> selected factories and provider identity definitions
     */
    private function getFactories(): array
    {
        if (null === $this->providers) {
            return $this->serviceProvider->getFactories();
        }

        $factories = [];
        $identities = [
            AggregateServiceProvider::class => new ServiceFactory($this->serviceProvider),
        ];

        foreach ($this->providers as $provider) {
            foreach ($provider->getFactories() as $id => $factory) {
                if (\is_callable($factory) && ! \array_key_exists($id, $factories)) {
                    $factories[$id] = $factory;
                }
            }

            $identities += [
                $provider::class => new ServiceFactory($provider),
            ];
        }

        return $factories + $identities;
    }

    /**
     * Invokes an available factory and applies the entry's extensions to its value.
     *
     * @param string $id the requested service identifier
     * @param mixed $factory the factory definition, which may be absent or non-callable
     * @param array<array-key, mixed> $extensions the provider's extension definitions
     *
     * @return mixed the completed extension result, including null
     */
    private function createExtendedService(string $id, mixed $factory, array $extensions): mixed
    {
        $service = \is_callable($factory) ? $factory($this->wrapperContainer) : null;

        return $this->applyServiceExtensions($id, $service, $extensions);
    }

    /**
     * Infers a concrete-class alias only when it cannot replace another entry or definition.
     *
     * @param string $id the identifier being resolved
     * @param mixed $service the completed service value
     * @param array<array-key, mixed> $factories the provider's factory definitions
     * @param array<array-key, mixed> $extensions the provider's extension definitions
     *
     * @return string|null the available class alias, or null when none should be registered
     */
    private function inferredClassAlias(string $id, mixed $service, array $factories, array $extensions): ?string
    {
        if (! \is_object($service)) {
            return null;
        }

        $class = $service::class;

        if ($id === $class || $this->hasEntry($class, $factories, $extensions)) {
            return null;
        }

        return $class;
    }

    /**
     * Stores the final value under its requested ID before adding any inferred alias.
     *
     * @param string $id the requested service identifier
     * @param mixed $service the completed service value, including null
     * @param string|null $alias the optional inferred class alias
     *
     * @return mixed the same value placed in the service cache
     */
    private function cacheResolvedService(string $id, mixed $service, ?string $alias): mixed
    {
        $this->cache[$id] = $service;

        if (null === $alias) {
            return $service;
        }

        $this->cache[$alias] = $service;

        return $service;
    }

    /**
     * Checks existing entries using metadata already loaded by the caller.
     *
     * @param string $id the entry identifier
     * @param array<string, mixed> $factories the available factory definitions
     * @param array<string, mixed> $extensions the available extension definitions
     *
     * @return bool whether a resolved entry or callable definition exists
     */
    private function hasEntry(string $id, array $factories = [], array $extensions = []): bool
    {
        return \array_key_exists($id, $this->cache)
            || \is_callable($factories[$id] ?? null)
            || \is_callable($extensions[$id] ?? null);
    }

    /**
     * Applies ID-based and concrete-class extensions, retaining each returned value.
     *
     * The ID-based extension SHALL run first. If its result is an object, an extension
     * for that object's concrete class MAY run next. Each object's class-extension result
     * SHALL be reused for later aliases, including replacement objects and null values.
     * ID-based extensions for other identifiers SHALL still run independently.
     * Invalid extensions SHALL be ignored.
     *
     * Extensions declared void SHALL retain the previous value for compatibility.
     *
     * @param string $id the identifier of the resolved service
     * @param mixed $service the previous entry value, or null when no factory exists
     * @param array<string, mixed> $extensions the provider's extension definitions
     *
     * @return mixed the final entry value
     *
     * @throws ContainerException if an extension callable fails during execution
     */
    private function applyServiceExtensions(string $id, mixed $service, array $extensions): mixed
    {
        $classObjects = [];

        if (\is_callable($extensions[$id] ?? null)) {
            [$service, $classObjects] = $this->applyIdentifierExtension($id, $service, $extensions[$id]);
        }

        [$service, $concreteClassObjects] = $this->applyConcreteClassExtension(
            $id,
            $service,
            $extensions,
            $classObjects
        );

        return $this->completeClassExtensions([...$classObjects, ...$concreteClassObjects], $service);
    }

    /**
     * Runs an ID extension against a reused value without repeating an already completed class-ID extension.
     *
     * @param string $id the requested service identifier
     * @param mixed $service the factory result or previously extended object
     * @param callable $extension the callable registered under this identifier
     *
     * @return array{mixed, list<object>} the ID-extension result and identities completed by a class-ID extension
     */
    private function applyIdentifierExtension(string $id, mixed $service, callable $extension): array
    {
        if (! \is_object($service) || null === ($cachedResult = $this->classExtensionResult($service))) {
            return $this->executeIdentifierExtension($id, $service, $extension);
        }

        if ($id === $service::class) {
            return [$cachedResult['value'], []];
        }

        return $this->executeIdentifierExtension($id, $cachedResult['value'], $extension);
    }

    /**
     * Executes an ID callback and identifies whether it also completed a class-ID extension.
     *
     * @param string $id the requested service identifier
     * @param mixed $service the previous entry value
     * @param callable $extension the callable registered under this identifier
     *
     * @return array{mixed, list<object>} the callback result and any completed class identity
     */
    private function executeIdentifierExtension(string $id, mixed $service, callable $extension): array
    {
        $classObject = \is_object($service) && $id === $service::class ? $service : null;
        $service = (new ServiceExtension($extension))($this->wrapperContainer, $service);

        if (null !== $classObject) {
            return [$service, [$classObject]];
        }

        if (\is_object($service) && $id === $service::class) {
            return [$service, [$service]];
        }

        return [$service, []];
    }

    /**
     * Reuses a completed result or executes one extension for the current concrete class.
     *
     * @param string $id the requested service identifier
     * @param mixed $service the result after the ID extension
     * @param array<string, mixed> $extensions the provider's extension definitions
     * @param list<object> $pendingObjects class identities being completed in this resolution
     *
     * @return array{mixed, list<object>} the concrete-class result and any newly completed input identity
     */
    private function applyConcreteClassExtension(
        string $id,
        mixed $service,
        array $extensions,
        array $pendingObjects
    ): array {
        if (! \is_object($service)) {
            return [$service, []];
        }

        $cachedResult = $this->classExtensionResult($service, $pendingObjects);

        if (null !== $cachedResult) {
            return [$cachedResult['value'], []];
        }

        if ($id === $service::class || ! \is_callable($extensions[$service::class] ?? null)) {
            return [$service, []];
        }

        $extended = (new ServiceExtension($extensions[$service::class]))($this->wrapperContainer, $service);

        return [$extended, [$service]];
    }

    /**
     * Normalizes completed replacement values before recording the current class-extension chain.
     *
     * This method SHALL follow only recorded results; it SHALL NOT execute another class extension.
     *
     * @param list<object> $objects the class identities completed in this resolution
     * @param mixed $service the final callback value
     *
     * @return mixed the latest completed replacement value
     */
    private function completeClassExtensions(array $objects, mixed $service): mixed
    {
        if ([] === $objects) {
            return $service;
        }

        if (\is_object($service) && null !== ($result = $this->classExtensionResult($service, $objects))) {
            $service = $result['value'];
        }

        $this->rememberClassExtensionResult($objects, $service);

        return $service;
    }

    /**
     * Reads the latest completed value and compresses acyclic identity paths.
     *
     * A cycle retains the input's recorded result without compressing the ambiguous path.
     * A path returning to a pending input SHALL remain unchanged until its callback chain completes.
     *
     * @param object $object the original or same-class result identity
     * @param list<object> $pendingObjects class identities being completed in this resolution
     *
     * @return array{value: mixed}|null the wrapped result, or null when no extension completed
     */
    private function classExtensionResult(object $object, array $pendingObjects = []): ?array
    {
        $initial = $this->registeredClassExtensionResult($object);

        if (null === $initial) {
            return null;
        }

        [$result, $path] = $this->followClassExtensionResults($object, $initial);

        if (\is_object($result['value']) && \in_array($result['value'], $pendingObjects, true)) {
            return [
                'value' => $object,
            ];
        }

        $this->compressClassExtensionPath($path, $result);

        return $result;
    }

    /**
     * Looks up an object identity without inspecting or executing extension definitions.
     *
     * @param object $object the class-extension input or same-class result
     *
     * @return array{value: mixed}|null the wrapped completed value, or null when absent
     */
    private function registeredClassExtensionResult(object $object): ?array
    {
        return $this->classExtensionResults[$object] ?? null;
    }

    /**
     * Follows recorded identities while retaining a bounded result for cyclic paths.
     *
     * @param object $object the first identity to follow
     * @param array{value: mixed} $initial the first identity's recorded value
     *
     * @return array{array{value: mixed}, list<object>} the completed result and safely compressible identities
     */
    private function followClassExtensionResults(object $object, array $initial): array
    {
        $visited = new WeakMap();
        $path = [];
        $current = $object;
        $result = $initial;

        while (null !== ($cached = $this->registeredClassExtensionResult($current))) {
            if (isset($visited[$current])) {
                return [$initial, []];
            }

            $visited[$current] = true;
            $path[] = $current;
            $result = $cached;

            if (! \is_object($result['value']) || $result['value'] === $current) {
                return [$result, $path];
            }

            $current = $result['value'];
        }

        return [$result, $path];
    }

    /**
     * Updates existing acyclic identities to their latest completed result.
     *
     * @param list<object> $objects the recorded identities visited during lookup
     * @param array{value: mixed} $result the latest completed result
     *
     * @return void
     */
    private function compressClassExtensionPath(array $objects, array $result): void
    {
        if ([] === $objects) {
            return;
        }

        $results = $this->classExtensionResults ??= new WeakMap();

        foreach ($objects as $object) {
            $results[$object] = $result;
        }
    }

    /**
     * Records completed results while leaving different-class replacements eligible for their own extensions.
     *
     * @param list<object> $objects identities whose completed class-extension result can be reused
     * @param mixed $service the final value of the completed extension chain
     *
     * @return void
     */
    private function rememberClassExtensionResult(array $objects, mixed $service): void
    {
        if ([] === $objects) {
            return;
        }

        $results = $this->classExtensionResults ??= new WeakMap();

        foreach ($objects as $object) {
            $results[$object] = [
                'value' => $service,
            ];

            if (\is_object($service) && $object::class === $service::class) {
                $results[$service] = [
                    'value' => $service,
                ];
            }
        }
    }
}
