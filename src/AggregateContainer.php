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

use FastForward\Container\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class AggregateContainer.
 *
 * Aggregates multiple PSR-11 containers and delegates resolution requests among them.
 * This container implementation MUST respect PSR-11 expectations and SHALL throw a
 * NotFoundException when a requested service cannot be found in any delegated container.
 *
 * It MAY cache resolved entries to prevent redundant calls to delegated containers.
 *
 * @package FastForward\Container
 */
class AggregateContainer implements ContainerInterface
{
    /**
     * @var string container alias for reference binding
     */
    public const ALIAS = 'container';

    /**
     * @var ContainerInterface[] the array of containers aggregated by this instance
     */
    private array $containers;

    /**
     * @var array<string, mixed> a registry of already resolved service identifiers
     */
    private array $resolved = [];

    /**
     * Constructs the AggregateContainer with one or more delegated containers.
     *
     * The constructor SHALL bind itself to common aliases, including the class name
     * and the PSR-11 interface, to simplify resolution of the container itself.
     *
     * @param PsrContainerInterface ...$containers One or more container implementations to aggregate.
     */
    public function __construct(PsrContainerInterface ...$containers)
    {
        $this->containers = $containers;
        $this->resolved   = [
            self::ALIAS               => $this,
            self::class               => $this,
            ContainerInterface::class => $this,
        ];
    }

    /**
     * Appends a container to the end of the aggregated list.
     *
     * This method MAY be used to dynamically expand the resolution pool.
     *
     * @param PsrContainerInterface $container the container to append
     */
    public function append(PsrContainerInterface $container): void
    {
        $this->containers[] = $container;
    }

    /**
     * Prepends a container to the beginning of the aggregated list.
     *
     * This method MAY be used to prioritize a container during resolution.
     *
     * @param PsrContainerInterface $container the container to prepend
     */
    public function prepend(PsrContainerInterface $container): void
    {
        array_unshift($this->containers, $container);
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

        foreach ($this->containers as $container) {
            if ($container->has($id)) {
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
     * NotFoundException if the identifier cannot be resolved.
     *
     * @param string $id the identifier of the entry to retrieve
     *
     * @return mixed the resolved entry
     *
     * @throws NotFoundException if the identifier cannot be found in any aggregated container
     */
    public function get(string $id): mixed
    {
        if ($this->isResolvedByContainer($id)) {
            return $this->resolved[$id];
        }

        $exception = NotFoundException::forServiceID($id);

        foreach ($this->containers as $container) {
            if (!$container->has($id)) {
                continue;
            }

            try {
                $this->resolved[$id] = $container->get($id);

                return $this->resolved[$id];
            } catch (NotFoundExceptionInterface $exception) {
                // Ignore NotFoundExceptionInterface
            } catch (ContainerExceptionInterface $exception) {
                // Future enhancement: Replace with a domain-specific exception if desired
            }
        }

        throw $exception;
    }

    /**
     * Determines whether the identifier has already been resolved by this container.
     *
     * This method SHALL be used internally to avoid unnecessary delegation to sub-containers.
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
