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

use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\Exception\ContainerException;
use Interop\Container\ServiceProviderInterface;
use Throwable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Aggregates ordered PSR-11 containers and Interop service providers.
 *
 * Providers share one adapter. Source positions and inferred alias origins are
 * retained as internal state, preserving external-source precedence.
 *
 * This container implementation respects PSR-11 expectations and throws a
 * NotFoundException when a requested service cannot be found in any delegated container.
 *
 * It caches resolved entries to prevent redundant calls to delegated containers.
 */
class AggregateContainer implements ContainerInterface
{
    /**
     * @var string container alias for reference binding
     */
    public const ALIAS = 'container';

    /**
     * @var list<array{container: PsrContainerInterface, providerIndex: int|null, required: bool}> ordered source descriptors
     */
    private array $containers = [];

    /**
     * @var array<string, mixed> a registry of already resolved service identifiers
     */
    private array $resolved = [];

    /**
     * @var list<ServiceProviderInterface> providers in their original registration order
     */
    private readonly array $providers;

    /**
     * @var array<class-string, int> originating source positions of inferred class aliases
     */
    private array $classAliasOwners = [];

    /**
     * Constructs the AggregateContainer with one or more delegated containers.
     *
     * The constructor SHALL bind itself to common aliases, including the class name
     * and the PSR-11 interface, to simplify resolution of the container itself.
     *
     * @param PsrContainerInterface|ServiceProviderInterface ...$containers ordered containers and providers to compose
     */
    public function __construct(PsrContainerInterface|ServiceProviderInterface ...$containers)
    {
        $this->resolved = [
            self::ALIAS                  => $this,
            self::class                  => $this,
            ContainerInterface::class    => $this,
            PsrContainerInterface::class => $this,
        ];

        $this->providers = array_values(array_filter(
            $containers,
            static fn(object $source): bool => ! $source instanceof PsrContainerInterface,
        ));
        $providerContainer = [] === $this->providers ? null : new ServiceProviderContainer($this->providers, $this);
        $providerIndex = 0;

        foreach ($containers as $container) {
            if ($container instanceof PsrContainerInterface) {
                $this->append($container);

                continue;
            }

            $this->containers[] = [
                'container' => $providerContainer,
                'providerIndex' => $providerIndex,
                'required' => true,
            ];
            $this->resolved[ServiceProviderContainer::class] ??= $providerContainer;
            ++$providerIndex;
        }
    }

    /**
     * Appends a container to the end of the aggregated list.
     *
     * This method MAY be used to dynamically expand the resolution pool.
     *
     * @param PsrContainerInterface $container the container to append
     *
     * @return void
     */
    public function append(PsrContainerInterface $container): void
    {
        $this->containers[] = [
            'container' => $container,
            'providerIndex' => null,
            'required' => false,
        ];

        $this->resolved[$container::class] ??= $container;
    }

    /**
     * Appends a source whose advertised entry failures must stop resolution.
     *
     * Absence still permits later sources. A failure after has() succeeds propagates,
     * including a not-found exception raised while resolving a required dependency.
     *
     * @param PsrContainerInterface $container the authoritative source to append
     *
     * @return void
     */
    public function appendRequired(PsrContainerInterface $container): void
    {
        $this->append($container);
        $this->containers[array_key_last($this->containers)]['required'] = true;
    }

    /**
     * Prepends a container to the beginning of the aggregated list.
     *
     * This method MAY be used to prioritize a container during resolution.
     *
     * @param PsrContainerInterface $container the container to prepend
     *
     * @return void
     */
    public function prepend(PsrContainerInterface $container): void
    {
        $this->resolved[$container::class] = $container;
        array_unshift($this->containers, [
            'container' => $container,
            'providerIndex' => null,
            'required' => false,
        ]);
    }

    /**
     * Determines whether a service identifier can be resolved.
     *
     * This method SHALL return true if the identifier is pre-resolved or can be located
     * in any of the aggregated containers.
     *
     * @param string $id the identifier of the entry to look for
     *
     * @return bool true if the entry exists, false otherwise
     */
    public function has(string $id): bool
    {
        if ($this->isResolvedByContainer($id)) {
            return true;
        }

        foreach ($this->containers as $source) {
            if ($this->sourceHas($source, $id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves the entry associated with the given identifier.
     *
     * This method SHALL resolve from its internal cache first, and otherwise iterate
     * through the aggregated containers to resolve the entry. It MUST throw a
     * NotFoundException if the identifier is absent. If all candidates fail, the first
     * delegated construction exception SHALL be retained instead.
     * Sources registered with appendRequired() SHALL propagate their advertised
     * entry failures immediately, without attempting later sources.
     *
     * @param string $id the identifier of the entry to retrieve
     *
     * @return mixed the resolved entry
     *
     * @throws NotFoundException if the identifier cannot be found in any aggregated container
     * @throws ContainerExceptionInterface if the container cannot resolve the entry
     */
    public function get(string $id): mixed
    {
        if ($this->isResolvedByContainer($id)) {
            return $this->resolved[$id];
        }

        $exception = NotFoundException::forServiceID($id);

        foreach ($this->containers as $source) {
            $container = $source['container'];

            if (! $this->sourceHas($source, $id)) {
                continue;
            }

            try {
                $this->resolved[$id] = $container->get($id);

                if (null !== $source['providerIndex'] && \is_object($this->resolved[$id])) {
                    $this->classAliasOwners[$this->resolved[$id]::class] ??= $source['providerIndex'];
                }

                return $this->resolved[$id];
            } catch (NotFoundExceptionInterface $failure) {
                if ($source['required']) {
                    throw $failure;
                }

                // Ignore NotFoundExceptionInterface
            } catch (ContainerExceptionInterface $failure) {
                if ($source['required']) {
                    throw $failure;
                }

                if ($exception instanceof NotFoundExceptionInterface) {
                    $exception = $failure;
                }
            }
        }

        throw $exception;
    }

    /**
     * Checks a source's advertised entry and the original position of provider definitions.
     *
     * @param array{container: PsrContainerInterface, providerIndex: int|null, required: bool} $source the source descriptor
     * @param string $id the requested identifier
     *
     * @return bool whether this source may resolve the entry
     */
    private function sourceHas(array $source, string $id): bool
    {
        if (! $source['container']->has($id)) {
            return false;
        }

        if (null === $source['providerIndex']) {
            return true;
        }

        try {
            return $this->providerIndexFor($id) === $source['providerIndex'];
        } catch (Throwable $throwable) {
            throw ContainerException::forInvalidService($id, $throwable);
        }
    }

    /**
     * Selects factory positions before extension-only defaults without executing either.
     *
     * @param string $id the entry identifier
     *
     * @return int the originating provider index
     */
    private function providerIndexFor(string $id): int
    {
        foreach ($this->providers as $position => $provider) {
            if (\is_callable($provider->getFactories()[$id] ?? null)) {
                return $position;
            }
        }

        foreach ($this->providers as $position => $provider) {
            if (\is_callable($provider->getExtensions()[$id] ?? null)) {
                return $position;
            }
        }

        foreach ($this->providers as $position => $provider) {
            if ($id === $provider::class) {
                return $position;
            }
        }

        return $this->classAliasOwners[$id] ?? 0;
    }

    /**
     * Determines whether the identifier has already been resolved by this container.
     *
     * This method is used internally to avoid unnecessary delegation to sub-containers.
     *
     * @param string $id the identifier to check
     *
     * @return bool true if the identifier is already resolved, false otherwise
     */
    private function isResolvedByContainer(string $id): bool
    {
        return \array_key_exists($id, $this->resolved);
    }
}
