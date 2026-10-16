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

use Throwable;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\Extension\ServiceExtension;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Implements a PSR-11 compliant dependency injection container using a container-interop/service-provider.
 *
 * This container SHALL resolve services by delegating to the factories and extensions defined in the
 * provided ServiceProviderInterface instance.
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
     * Cache of resolved services keyed by their identifier or class name.
     *
     * This array SHALL store already constructed services to enforce singleton-like behavior within the container scope.
     *
     * @var array<string, mixed>
     */
    private array $cache = [];

    /**
     * Constructs a new ServiceProviderContainer instance.
     *
     * This constructor SHALL initialize the container with a service provider and an optional delegating container.
     * If no wrapper container is provided, the container SHALL delegate to itself.
     *
     * @param ServiceProviderInterface $serviceProvider the service provider supplying factories and extensions
     * @param PsrContainerInterface|null $wrapperContainer An optional container for delegation. Defaults to self.
     */
    public function __construct(
        private readonly ServiceProviderInterface $serviceProvider,
        ?PsrContainerInterface $wrapperContainer = null,
    ) {
        $this->wrapperContainer = $wrapperContainer ?? $this;
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
            return $this->hasEntry($id, $this->serviceProvider->getFactories())
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
     * @throws ContainerException if factory or extension resolution fails
     */
    public function get(string $id): mixed
    {
        if (\array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        try {
            $factories = $this->serviceProvider->getFactories();
            $extensions = $this->serviceProvider->getExtensions();
        } catch (Throwable $throwable) {
            throw ContainerException::forInvalidService($id, $throwable);
        }

        $factory = $factories[$id] ?? null;

        if (! \is_callable($factory) && ! \is_callable($extensions[$id] ?? null)) {
            throw NotFoundException::forServiceID($id);
        }

        try {
            $service = \is_callable($factory) ? $factory($this->wrapperContainer) : null;
            $service = $this->applyServiceExtensions($id, $service, $extensions);
            $alias = \is_object($service) ? $service::class : null;

            if (null !== $alias && ($id === $alias || $this->hasEntry($alias, $factories, $extensions))) {
                $alias = null;
            }
        } catch (Throwable $throwable) {
            throw ContainerException::forInvalidService($id, $throwable);
        }

        $this->cache[$id] = $service;

        if (null !== $alias) {
            $this->cache[$alias] = $service;
        }

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
     * for that object's concrete class MAY run next, unless that exact object is already
     * cached under its class identifier. Invalid extensions SHALL be ignored.
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
        if (\is_callable($extensions[$id] ?? null)) {
            $service = (new ServiceExtension($extensions[$id]))($this->wrapperContainer, $service);
        }

        if (\is_object($service)
            && $id !== $service::class
            && $service !== ($this->cache[$service::class] ?? null)
            && \is_callable($extensions[$service::class] ?? null)
        ) {
            return (new ServiceExtension($extensions[$service::class]))($this->wrapperContainer, $service);
        }

        return $service;
    }
}
