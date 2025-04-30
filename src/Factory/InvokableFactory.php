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
 * Class InvokableFactory.
 *
 * Factory responsible for instantiating a class with constructor arguments.
 * This class MUST be used when a service should be created using a known class name
 * and optionally injected with fixed arguments.
 *
 * It SHALL invoke the constructor directly using the spread operator and provided arguments.
 * This factory is suitable for services that do not require container-based dependency injection.
 *
 * @package FastForward\Container\Factory
 */
final class InvokableFactory implements FactoryInterface
{
    /**
     * @var string The fully qualified class name to instantiate.
     *             This MUST be a valid, instantiable class.
     */
    private readonly string $class;

    /**
     * @var array<int, mixed> The list of arguments to pass to the class constructor.
     *                        This MAY be empty if the constructor takes no arguments.
     */
    private readonly array $arguments;

    /**
     * Constructs the InvokableFactory with a target class and optional constructor arguments.
     *
     * @param string            $class     the class name to instantiate
     * @param array<int, mixed> $arguments optional list of arguments for the constructor
     */
    public function __construct(string $class, array $arguments = [])
    {
        $this->class     = $class;
        $this->arguments = $arguments;
    }

    /**
     * Instantiates the configured class with the provided arguments.
     *
     * @param ContainerInterface $container the container instance (unused in this factory)
     *
     * @return mixed a new instance of the target class
     */
    public function __invoke(ContainerInterface $container): mixed
    {
        return new ($this->class)(...$this->arguments);
    }
}
