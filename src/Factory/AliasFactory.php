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

namespace FastForward\Container\Factory;

use Psr\Container\ContainerInterface;

/**
 * Class AliasFactory.
 *
 * A factory that resolves an alias to another service within a PSR-11 container.
 * This factory MUST be used when a service should act as an alias for another
 * service already registered in the container.
 *
 * When invoked, it SHALL delegate resolution to the aliased service identifier.
 *
 * @package FastForward\Container\Factory
 */
final class AliasFactory implements FactoryInterface
{
    /**
     * @var string The identifier of the aliased service.
     *             This MUST correspond to a valid entry in the container.
     */
    private readonly string $alias;

    /**
     * Constructs the AliasFactory with the target service identifier.
     *
     * @param string $alias the identifier of the service to which this factory points
     */
    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    /**
     * Resolves the aliased service from the container.
     *
     * This method MUST return the same instance as if the original alias identifier
     * were used directly with the container.
     *
     * @param ContainerInterface $container the container instance to resolve the alias from
     *
     * @return mixed the resolved service instance
     */
    public function __invoke(ContainerInterface $container): mixed
    {
        return $container->get($this->alias);
    }
}
