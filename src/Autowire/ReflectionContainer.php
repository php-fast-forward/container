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

namespace FastForward\Container\Autowire;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;
use FastForward\Container\ContainerInterface;
use FastForward\Container\Exception\AutowiringException;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Resolves constructor dependencies through the composed container.
 *
 * @internal
 */
final class ReflectionContainer implements ContainerInterface
{
    /**
     * @var array<string, object> successfully constructed services
     */
    private array $resolved = [];

    /**
     * @var array<string, true> the active construction path
     */
    private array $resolving = [];

    /**
     * @param PsrContainerInterface $wrapperContainer the complete container composition
     */
    public function __construct(
        private readonly PsrContainerInterface $wrapperContainer
    ) {}

    /**
     * Checks class metadata without constructing an object.
     *
     * @param string $id the entry identifier
     *
     * @return bool whether the class can be instantiated
     */
    public function has(string $id): bool
    {
        return class_exists($id) && (new ReflectionClass($id))->isInstantiable();
    }

    /**
     * Constructs and caches a concrete class, detecting recursive dependencies.
     *
     * @param string $id the class identifier
     *
     * @return object the resolved service
     *
     * @throws NotFoundException if the class is not instantiable
     * @throws AutowiringException if required arguments cannot be inferred
     * @throws ContainerException if construction fails or a dependency cycle exists
     */
    public function get(string $id): object
    {
        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }

        if (! class_exists($id)) {
            throw NotFoundException::forServiceID($id);
        }

        $class = new ReflectionClass($id);

        if (! $class->isInstantiable()) {
            throw NotFoundException::forServiceID($id);
        }

        if (isset($this->resolving[$id])) {
            throw new ContainerException(\sprintf(
                'Circular dependency: %s.',
                implode(' -> ', [...array_keys($this->resolving), $id]),
            ));
        }

        $this->resolving[$id] = true;

        try {
            $arguments = [];
            $constructor = $class->getConstructor();

            if (null !== $constructor) {
                foreach ($constructor->getParameters() as $parameter) {
                    if (! $parameter->isVariadic()) {
                        $arguments[] = $this->resolveParameter($parameter, $constructor->getDeclaringClass());
                    }
                }
            }

            try {
                return $this->resolved[$id] = $class->newInstanceArgs($arguments);
            } catch (Throwable $throwable) {
                throw ContainerException::forInvalidService($id, $throwable);
            }
        } finally {
            unset($this->resolving[$id]);
        }
    }

    /**
     * Resolves named object types before considering an optional default.
     *
     * @param ReflectionParameter $parameter the constructor parameter
     * @param ReflectionClass<object> $declaringClass the class that declares the constructor
     *
     * @return mixed the dependency or declared default
     *
     * @throws AutowiringException if no dependency or default can be supplied
     */
    private function resolveParameter(ReflectionParameter $parameter, ReflectionClass $declaringClass): mixed
    {
        $type = $parameter->getType();
        $previous = null;

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $id = match ($type->getName()) {
                'self' => $declaringClass->getName(),
                'parent' => get_parent_class($declaringClass->getName()),
                default => $type->getName(),
            };

            if (false !== $id) {
                try {
                    return $this->wrapperContainer->get($id);
                } catch (NotFoundExceptionInterface|AutowiringException $exception) {
                    $previous = $exception;
                }
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new AutowiringException(\sprintf(
            'Cannot autowire %s::__construct(): parameter $%s (%s) requires an explicit service or factory.',
            $declaringClass->getName(),
            $parameter->getName(),
            null === $type ? 'untyped' : (string) $type,
        ), 0, $previous);
    }
}
