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

use FastForward\Container\Autowire\ReflectionContainer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Resolves services through an optional delegate and native constructor autowiring.
 *
 * Explicit delegate entries take precedence over autowiring. Without a delegate,
 * an empty aggregate is created to resolve concrete dependency graphs directly.
 */
final readonly class AutowireContainer implements ContainerInterface
{
    /**
     * @var PsrContainerInterface the internal composite container with autowiring support
     */
    private PsrContainerInterface $container;

    /**
     * Constructs the AutowireContainer.
     *
     * Omission or null creates an empty AggregateContainer. An existing aggregate is
     * reused; other PSR-11 containers are wrapped. Autowiring is appended last so
     * delegate entries take precedence, including for nested dependencies.
     *
     * @param PsrContainerInterface|null $delegateContainer the optional delegate to wrap and extend
     */
    public function __construct(?PsrContainerInterface $delegateContainer = null)
    {
        $delegateContainer ??= new AggregateContainer();

        $aggregateContainer = $delegateContainer instanceof AggregateContainer
            ? $delegateContainer
            : new AggregateContainer($delegateContainer);

        $aggregateContainer->append(new ReflectionContainer($aggregateContainer));

        $this->container = $aggregateContainer;
    }

    /**
     * Retrieves an entry from the container by its identifier.
     *
     * @param string $id identifier of the entry to retrieve
     *
     * @return mixed the resolved entry
     *
     * @throws NotFoundExceptionInterface if the identifier is not found
     * @throws ContainerExceptionInterface if the entry cannot be resolved
     */
    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * Checks whether the composed container knows the entry without calling get().
     *
     * @param string $id the entry identifier
     *
     * @return bool whether an entry or instantiable class is known
     *
     * @throws ContainerExceptionInterface if delegated lookup or class loading fails
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }
}
