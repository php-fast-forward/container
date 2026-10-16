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

namespace FastForward\Container\Factory;

use ReflectionMethod;
use ReflectionException;
use FastForward\Container\Exception\RuntimeException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * A factory that invokes a public method on a class or a container-provided object.
 *
 * This factory MUST be used when service creation requires calling a non-constructor method,
 * and supports both static and instance methods.
 *
 * If the method is not public, a RuntimeException SHALL be thrown.
 *
 * Arguments MAY be resolved from the container if passed as service identifiers.
 */
final readonly class MethodFactory implements FactoryInterface
{
    /**
     * @var array<int|string, mixed> positional or named arguments passed to the method
     */
    private array $arguments;

    /**
     * Constructs the MethodFactory.
     *
     * @param string $class the class name or container service ID on which the method is called
     * @param string $method the name of the method to invoke
     * @param mixed ...$arguments Optional arguments to pass to the method.
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
     * Public static class methods are invoked without resolving an instance. Instance targets
     * are retrieved by class name or service ID and reflected using the resolved object.
     * Direct construction is permitted only when has() reports a class-name entry as absent.
     * Failures resolving registered targets propagate, including missing nested dependencies.
     *
     * @param ContainerInterface $container The container used to resolve the class and arguments
     *
     * @return mixed The result of invoking the method
     *
     * @throws ReflectionException If the method does not exist
     * @throws RuntimeException If the method is not public
     * @throws NotFoundExceptionInterface if a non-class service ID is absent
     */
    public function __invoke(ContainerInterface $container): mixed
    {
        $arguments = $this->resolveArguments($container);

        $method = $this->findPublicStaticMethod();

        if (null !== $method) {
            return $method->invokeArgs(null, $arguments);
        }

        $object = $this->resolveObject($container);

        return $this->reflectPublicMethod($object)
            ->invokeArgs($object, $arguments);
    }

    /**
     * @param ContainerInterface $container the source of named method arguments
     *
     * @return array<int|string, mixed> resolved arguments with their positional or named keys
     */
    private function resolveArguments(ContainerInterface $container): array
    {
        return array_map(
            static fn($argument) => \is_string($argument) && $container->has($argument)
                ? $container->get($argument)
                : $argument,
            $this->arguments
        );
    }

    /**
     * @param object|class-string $target the resolved object or a static method's class
     *
     * @return ReflectionMethod the public method to invoke
     *
     * @throws ReflectionException if the method does not exist
     * @throws RuntimeException if the method is not public
     */
    private function reflectPublicMethod(object|string $target): ReflectionMethod
    {
        $reflectionMethod = new ReflectionMethod($target, $this->method);

        if (! $reflectionMethod->isPublic()) {
            throw RuntimeException::forNonPublicMethod($this->class, $this->method);
        }

        return $reflectionMethod;
    }

    /**
     * @return ReflectionMethod|null a public static method that needs no target instance
     */
    private function findPublicStaticMethod(): ?ReflectionMethod
    {
        if (! method_exists($this->class, $this->method)) {
            return null;
        }

        $method = new ReflectionMethod($this->class, $this->method);

        return $method->isPublic() && $method->isStatic() ? $method : null;
    }

    /**
     * @param ContainerInterface $container the source of the target instance
     *
     * @return object the configured instance, or a class with no registered entry
     *
     * @throws NotFoundExceptionInterface if a non-class service ID is absent
     */
    private function resolveObject(ContainerInterface $container): object
    {
        if (! class_exists($this->class) || $container->has($this->class)) {
            return $container->get($this->class);
        }

        $this->reflectPublicMethod($this->class);

        return new ($this->class)();
    }
}
