<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/container.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @link      https://github.com/php-fast-forward/container
 * @copyright Copyright (c) 2025 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace FastForward\Container;

use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\NotFoundException;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Class ServiceProviderContainer
 *
 * Implements a PSR-11 compliant dependency injection container using a service provider.
 *
 * This container SHALL resolve services by delegating to the factories and extensions defined in the
 * provided ServiceProviderInterface instance. Services are lazily instantiated on first request and
 * cached for subsequent retrieval, enforcing singleton-like behavior within the container scope.
 *
 * The container supports service extension mechanisms by allowing callable extensions to modify or
 * enhance services after construction, based on the service identifier or its concrete class name.
 *
 * If an optional wrapper container is provided, it SHALL be passed to service factories and extensions,
 * allowing for delegation or decoration of service resolution. If omitted, the container defaults to itself.
 *
 * @package FastForward\Container
 */
final class ServiceProviderContainer implements ContainerInterface
{
    /**
     * The service provider supplying factories and extensions for service construction.
     *
     * This property MUST reference a valid ServiceProviderInterface implementation.
     *
     * @var ServiceProviderInterface
     */
    private ServiceProviderInterface $serviceProvider;

    /**
     * The container instance used for service resolution and extension application.
     *
     * This property MAY reference another container for delegation, or default to this container instance.
     *
     * @var PsrContainerInterface
     */
    private PsrContainerInterface $wrapperContainer;

    /**
     * Cache of resolved services keyed by their identifier or class name.
     *
     * This array SHALL store already constructed services to enforce singleton-like behavior within the container scope.
     *
     * @var array<string, mixed>
     */
    private array $cache;

    /**
     * Constructs a new ServiceProviderContainer instance.
     *
     * This constructor SHALL initialize the container with a service provider and an optional delegating container.
     * If no wrapper container is provided, the container SHALL delegate to itself.
     *
     * @param ServiceProviderInterface $serviceProvider The service provider supplying factories and extensions.
     * @param PsrContainerInterface|null $wrapperContainer An optional container for delegation. Defaults to self.
     */
    public function __construct(
        ServiceProviderInterface $serviceProvider,
        ?PsrContainerInterface $wrapperContainer = null,
    ) {
        $this->serviceProvider  = $serviceProvider;
        $this->wrapperContainer = $wrapperContainer ?? $this;
    }

    /**
     * Determines if the container can return an entry for the given identifier.
     *
     * This method MUST return true if the entry exists in the cache or factories, false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool True if the entry exists, false otherwise.
     */
    public function has(string $id): bool
    {
        return isset($this->cache[$id]) || \array_key_exists($id, $this->serviceProvider->getFactories());
    }

    /**
     * Retrieves a service from the container by its identifier.
     *
     * This method SHALL return a cached instance if available, otherwise it resolves
     * the service using the factory provided by the service provider.
     *
     * If the service has a corresponding extension, it SHALL be applied post-construction.
     *
     * @param string $id the identifier of the service to retrieve
     *
     * @return mixed the service instance associated with the identifier
     *
     * @throws NotFoundException  if no factory exists for the given identifier
     * @throws ContainerException if service construction fails due to container errors
     */
    public function get(string $id): mixed
    {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }

        $factory = $this->serviceProvider->getFactories();

        if (!\array_key_exists($id, $factory) || !\is_callable($factory[$id])) {
            throw NotFoundException::forServiceID($id);
        }

        try {
            $service = call_user_func($factory[$id], $this->wrapperContainer);
            $class   = get_class($service);
            $this->applyServiceExtensions($id, $class, $service);
        } catch (ContainerExceptionInterface $containerException) {
            throw ContainerException::forInvalidService($id, $containerException);
        }

        $this->cache[$id] = $service;

        if ($id !== $class && !isset($this->cache[$class])) {
            $this->cache[$class] = $service;
        }

        return $service;
    }

    /**
     * Applies service extensions to the constructed service instance.
     *
     * This method SHALL inspect the set of extensions returned by the service provider,
     * checking both the original service identifier and the concrete class name of the
     * service instance. If a corresponding extension is found, it MUST be a callable and
     * SHALL be invoked with the container and service instance as arguments.
     *
     * Extensions MAY be used to modify or enhance services after creation. Invalid extensions
     * (non-callables) SHALL be ignored silently.
     *
     * @param string $id The identifier of the resolved service.
     * @param string $class The fully qualified class name of the service.
     * @param mixed $service The service instance to apply extensions to.
     *
     * @return void
     *
     * @throws ContainerException If an extension callable fails during execution.
     */
    private function applyServiceExtensions(string $id, string $class, mixed $service): void
    {
        $extensions = $this->serviceProvider->getExtensions();

        if (\array_key_exists($id, $extensions) && \is_callable($extensions[$id])) {
            $extensions[$id]($this->wrapperContainer, $service);
        }

        if ($id !== $class
            && !isset($this->cache[$class])
            && \array_key_exists($class, $extensions)
            && \is_callable($extensions[$class])
        ) {
            $extensions[$class]($this->wrapperContainer, $service);
        }
    }
}
