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

use FastForward\Container\Exception\RuntimeException;
use Psr\Container\ContainerInterface;

/**
 * Class CallableFactory.
 *
 * A factory that wraps a user-provided callable and executes it when invoked.
 * The callable MUST accept a PSR-11 ContainerInterface as its first and only argument.
 * This allows dynamic resolution of services using the container context.
 *
 * This factory SHALL be used when the construction logic must be fully delegated to a closure.
 *
 * @package FastForward\Container\Factory
 */
final class CallableFactory implements FactoryInterface
{
    /**
     * @var \Closure The user-defined factory callable.
     *               This callable MUST accept a ContainerInterface and return a service instance.
     */
    private \Closure $callable;

    /**
     * Constructs a CallableFactory instance.
     *
     * @param callable $callable a callable that returns a service instance and accepts a container
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable(...);
    }

    /**
     * Invokes the factory to create a service.
     *
     * @param ContainerInterface $container the PSR-11 container for dependency resolution
     *
     * @return mixed the constructed service instance
     */
    public function __invoke(ContainerInterface $container): mixed
    {
        $arguments = $this->getArguments($container, new \ReflectionFunction($this->callable));

        return call_user_func_array($this->callable, $arguments);
    }

    /**
     * Retrieves the arguments for the callable from the container.
     *
     * @param ContainerInterface  $container the PSR-11 container for dependency resolution
     * @param \ReflectionFunction $function  the reflection function of the callable
     *
     * @return array the resolved arguments for the callable
     */
    private function getArguments(ContainerInterface $container, \ReflectionFunction $function): array
    {
        $arguments = [];

        foreach ($function->getParameters() as $parameter) {
            if (!$parameter->getType() || $parameter->getType()->isBuiltin()) {
                throw RuntimeException::forInvalidParameterType($parameter->getName());
            }

            $className   = $parameter->getType()->getName();
            $arguments[] = $container->get($className);
        }

        return $arguments;
    }
}
