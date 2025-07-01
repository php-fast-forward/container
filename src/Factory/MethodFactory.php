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

use FastForward\Container\Exception\RuntimeException;
use Psr\Container\ContainerInterface;

/**
 * A factory that invokes a specified method on a class using reflection and the PSR-11 container.
 *
 * This factory MUST be used when service creation requires calling a non-constructor method,
 * and supports both static and instance methods.
 *
 * If the method is not public, a RuntimeException SHALL be thrown.
 *
 * Arguments MAY be resolved from the container if passed as service identifiers.
 *
 * @package FastForward\Container\Factory
 */
final class MethodFactory implements FactoryInterface
{
    /**
     * @var array<int, mixed> arguments to be passed to the method during invocation
     */
    private array $arguments;

    /**
     * Constructs the MethodFactory.
     *
     * @param string $class        the class name or container service ID on which the method is called
     * @param string $method       the name of the method to invoke
     * @param mixed  ...$arguments Optional arguments to pass to the method.
     */
    public function __construct(
        private string $class,
        private string $method,
        mixed ...$arguments,
    ) {
        $this->arguments = $arguments;
    }

    /**
     * Resolves the class and invokes the configured method with arguments.
     *
     * Arguments MAY be resolved from the container if passed as string identifiers and found.
     * Static methods are invoked without instantiating the class. If the method is not public,
     * this method MUST throw a RuntimeException.
     *
     * @param ContainerInterface $container The container used to resolve the class and arguments
     *
     * @return mixed The result of invoking the method
     *
     * @throws \ReflectionException If the method does not exist
     * @throws RuntimeException     If the method is not public
     */
    public function __invoke(ContainerInterface $container): mixed
    {
        $arguments = array_map(
            static fn ($argument) => \is_string($argument) && $container->has($argument)
                ? $container->get($argument)
                : $argument,
            $this->arguments
        );

        $reflectionMethod = new \ReflectionMethod($this->class, $this->method);

        if (!$reflectionMethod->isPublic()) {
            throw RuntimeException::forNonPublicMethod($this->class, $this->method);
        }

        if ($reflectionMethod->isStatic()) {
            return $reflectionMethod->invokeArgs(null, $arguments);
        }

        try {
            $object = $container->get($this->class);
        } catch (\Throwable) {
            $object = new ($this->class)();
        }

        return $reflectionMethod->invokeArgs($object, $arguments);
    }
}
