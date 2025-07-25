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

namespace FastForward\Container\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Factory responsible for instantiating a class with constructor arguments.
 *
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
     * This constructor MUST receive a valid, instantiable class name. Any variadic arguments
     * provided SHALL be passed to the class constructor during instantiation. If an argument
     * is a string and matches a service ID in the container, it SHALL be resolved from the container.
     *
     * @param string $class        the fully qualified class name to be instantiated
     * @param mixed  ...$arguments A variadic list of constructor arguments.
     */
    public function __construct(string $class, mixed ...$arguments)
    {
        $this->class     = $class;
        $this->arguments = $arguments;
    }

    /**
     * Creates an instance of the class with the provided arguments.
     *
     * Arguments that are strings and match a known service ID in the container
     * SHALL be replaced with the corresponding container-resolved services.
     *
     * It MUST return a new instance of the class with the resolved arguments.
     * This method SHALL be invoked by the container when the service is requested.
     *
     * @param ContainerInterface $container The PSR-11 compliant container providing dependencies
     *
     * @return mixed The created service instance
     *
     * @throws ContainerExceptionInterface thrown if an error occurs during service instantiation
     * @throws NotFoundExceptionInterface  thrown if a required service ID is not found in the container
     */
    public function __invoke(ContainerInterface $container): mixed
    {
        $arguments = array_map(
            static fn ($argument) => \is_string($argument) && $container->has($argument)
                ? $container->get($argument)
                : $argument,
            $this->arguments
        );

        return new ($this->class)(...$arguments);
    }
}
