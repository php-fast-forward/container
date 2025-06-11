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

final class ServiceProviderContainer implements ContainerInterface
{
    /**
     * @var ServiceProviderInterface provides factories and extensions for service construction
     */
    private ServiceProviderInterface $serviceProvider;

    /**
     * @var ContainerInterface the underlying container used for delegation, if available
     */
    private PsrContainerInterface $wrapperContainer;

    /**
     * @var array<string, mixed> cached resolved services by their identifiers
     */
    private array $cache;

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
     * @param string $id identifier of the entry to look for
     *
     * @return bool true if the entry exists, false otherwise
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
            $this->applyServiceExtensions($id, $service);
        } catch (ContainerExceptionInterface $containerException) {
            throw ContainerException::forInvalidService($id, $containerException);
        }

        return $this->cache[$id] = $service;
    }

    /**
     * Applies service extensions to the constructed service instance.
     *
     * This method SHALL inspect the set of extensions returned by the service provider,
     * checking both the original service identifier and the concrete class name of the
     * service instance. If a corresponding extension is found, it MUST be a callable and
     * SHALL be invoked with the container and service instance as arguments.
     *
     * This mechanism allows for post-construction decoration or augmentation of the
     * resolved service. Extensions MAY be used to modify or enhance the behavior or state
     * of services after they have been created.
     *
     * Implementations MUST ensure that only valid callables are executed and SHOULD avoid
     * side effects beyond service enhancement. If an extension fails or is not callable,
     * the method SHALL silently ignore it unless explicitly configured otherwise.
     *
     * @param string $id      The identifier of the resolved service.
     * @param mixed  $service The service instance to be extended.
     *
     * @return void
     *
     * @throws ContainerException If any extension fails during invocation.
     */
    private function applyServiceExtensions(string $id, mixed $service): void
    {
        $class = get_class($service);
        $extensions = $this->serviceProvider->getExtensions();

        if (\array_key_exists($id, $extensions) && \is_callable($extensions[$id])) {
            $extensions[$id]($this->wrapperContainer, $service);
        }

        if ($id !== $class && \array_key_exists($class, $extensions) && \is_callable($extensions[$class])) {
            $extensions[$class]($this->wrapperContainer, $service);
        }
    }
}
