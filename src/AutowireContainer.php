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
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\Container;

use DI\Container;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * A composite container implementation that wraps another PSR-11 container and appends
 * an internal PHP-DI autowiring container.
 *
 * It provides auto-resolution of services while maintaining compatibility with
 * pre-defined service providers.
 *
 * This container MUST be used in scenarios where automatic dependency resolution
 * via autowiring is required alongside explicitly registered services.
 *
 * @package FastForward\Container
 */
final class AutowireContainer implements ContainerInterface
{
    /**
     * @var PsrContainerInterface the internal composite container with autowiring support
     */
    private PsrContainerInterface $container;

    /**
     * Constructs the AutowireContainer.
     *
     * If the provided container is not an AggregateContainer, it is wrapped within one.
     * A PHP-DI container is appended to the aggregate to support autowiring.
     *
     * @param PsrContainerInterface $delegateContainer the delegate container to wrap and extend
     */
    public function __construct(PsrContainerInterface $delegateContainer)
    {
        $aggregateContainer = $delegateContainer instanceof AggregateContainer
            ? $delegateContainer
            : new AggregateContainer($delegateContainer);

        $aggregateContainer->append(new Container(wrapperContainer: $this));

        $this->container = $aggregateContainer;
    }

    /**
     * Retrieves an entry from the container by its identifier.
     *
     * @param string $id identifier of the entry to retrieve
     *
     * @return mixed the resolved entry
     *
     * @throws NotFoundExceptionInterface  if the identifier is not found
     * @throws ContainerExceptionInterface if the entry cannot be resolved
     */
    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        if (!$this->container->has($id)) {
            return false;
        }

        try {
            // Attempt to resolve the service to check if it is valid
            $this->get($id);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }
}
