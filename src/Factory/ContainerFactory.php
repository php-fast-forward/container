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

use DI\Container;
use FastForward\Config\ConfigInterface;
use FastForward\Config\Container\ConfigContainer;
use FastForward\Container\AggregateContainer;
use Psr\Container\ContainerInterface;

/**
 * Class ContainerFactory.
 *
 * Factory for creating an aggregate container instance.
 * This factory class MUST be invoked with optional sub-containers and SHALL produce
 * a PSR-11 compliant container composed of configuration, DI definitions, and
 * user-supplied container instances.
 *
 * This factory is particularly useful in dependency injection systems that support
 * callable factories.
 *
 * @package FastForward\Container\Factory
 */
final class ContainerFactory
{
    /**
     * @var ConfigInterface configuration used to bootstrap the container
     */
    private ConfigInterface $config;

    /**
     * Constructs a new instance of ContainerFactory.
     *
     * This constructor MUST receive a ConfigInterface implementation to extract
     * service definitions.
     *
     * @param ConfigInterface $config the configuration object
     */
    public function __construct(
        ConfigInterface $config,
    ) {
        $this->config = $config;
    }

    /**
     * Invokes the factory and returns an aggregate container instance.
     *
     * This method SHALL convert the dependencies from configuration into a DI\Container,
     * and SHALL wrap everything in an AggregateContainer with an internal ConfigContainer.
     *
     * @param ContainerInterface ...$containers One or more additional containers to include.
     *
     * @return ContainerInterface the composed PSR-11 compliant container
     */
    public function __invoke(ContainerInterface ...$containers): ContainerInterface
    {
        $dependencies = $this->config->get('dependencies', []);

        if ($dependencies instanceof ConfigInterface) {
            $dependencies = $dependencies->toArray();
        }

        // Add an autowire-based container using provided dependencies.
        $containers[] = new Container($dependencies);

        return new AggregateContainer(
            new ConfigContainer($this->config),
            ...$containers,
        );
    }
}
