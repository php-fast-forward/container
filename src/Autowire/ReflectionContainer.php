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
use ReflectionMethod;
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
     *
     * @throws ContainerException if class loading or reflection fails
     */
    public function has(string $id): bool
    {
        return $this->reflectClass($id) instanceof ReflectionClass;
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

        $class = $this->reflectClass($id);

        if (! $class instanceof ReflectionClass) {
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
            return $this->resolved[$id] = $this->instantiateClass($id, $class);
        } finally {
            unset($this->resolving[$id]);
        }
    }

    /**
     * Looks up instantiable class metadata within the PSR-11 error boundary.
     *
     * @param string $id the class identifier
     *
     * @return ReflectionClass<object>|null the class metadata, if instantiable
     *
     * @throws ContainerException if class loading or reflection fails
     */
    private function reflectClass(string $id): ?ReflectionClass
    {
        try {
            if (! class_exists($id)) {
                return null;
            }

            $class = new ReflectionClass($id);

            return $class->isInstantiable() ? $class : null;
        } catch (Throwable $throwable) {
            throw ContainerException::forInvalidService($id, $throwable);
        }
    }

    /**
     * Invokes the constructor while retaining storage for reference arguments.
     *
     * @param string $id the requested service identifier
     * @param ReflectionClass<object> $class the instantiable class
     *
     * @return object the constructed service
     *
     * @throws AutowiringException if required arguments cannot be inferred
     * @throws ContainerException if dependency resolution or construction fails
     */
    private function instantiateClass(string $id, ReflectionClass $class): object
    {
        $constructor = $class->getConstructor();
        $values = $this->resolveArguments($constructor);
        $arguments = $values;

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();

            if ($parameter->isPassedByReference() && \array_key_exists($name, $values)) {
                $arguments[$name] = &$values[$name];
            }
        }

        try {
            return $class->newInstanceArgs($arguments);
        } catch (Throwable $throwable) {
            throw ContainerException::forInvalidService($id, $throwable);
        }
    }

    /**
     * Resolves constructor values, omitting variadics and unavailable optional arguments.
     *
     * @param ReflectionMethod|null $constructor the constructor, if declared
     *
     * @return array<string, mixed> resolved values keyed by parameter name
     *
     * @throws AutowiringException if required arguments cannot be inferred
     * @throws ContainerException if resolving a dependency or default fails
     */
    private function resolveArguments(?ReflectionMethod $constructor): array
    {
        if (! $constructor instanceof ReflectionMethod) {
            return [];
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                continue;
            }

            try {
                $arguments[$parameter->getName()] = $this->resolveParameter(
                    $parameter,
                    $constructor->getDeclaringClass()
                );
            } catch (AutowiringException $exception) {
                if (! $parameter->isOptional() || $parameter->isDefaultValueAvailable()) {
                    throw $exception;
                }
            }
        }

        return $arguments;
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
     * @throws ContainerException if evaluating a declared default fails
     */
    private function resolveParameter(ReflectionParameter $parameter, ReflectionClass $declaringClass): mixed
    {
        $id = $this->resolveParameterClassName($parameter, $declaringClass);
        $previous = null;

        if (null !== $id) {
            try {
                return $this->wrapperContainer->get($id);
            } catch (NotFoundExceptionInterface|AutowiringException $exception) {
                $previous = $exception;
            }
        }

        return $this->resolveDefaultValue($parameter, $declaringClass, $previous);
    }

    /**
     * Maps a named object type to the service identifier in its declaring scope.
     *
     * @param ReflectionParameter $parameter the constructor parameter
     * @param ReflectionClass<object> $declaringClass the class that declares the constructor
     *
     * @return string|null the class name, or null for unsupported parameter types
     */
    private function resolveParameterClassName(ReflectionParameter $parameter, ReflectionClass $declaringClass): ?string
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return match ($type->getName()) {
            'self' => $declaringClass->getName(),
            'parent' => get_parent_class($declaringClass->getName()) ?: null,
            default => $type->getName(),
        };
    }

    /**
     * Reads a declared default or reports why an explicit factory is required.
     *
     * @param ReflectionParameter $parameter the constructor parameter
     * @param ReflectionClass<object> $declaringClass the class that declares the constructor
     * @param Throwable|null $previous the structural dependency resolution failure
     *
     * @return mixed the declared default
     *
     * @throws AutowiringException if the parameter has no declared default
     * @throws ContainerException if evaluating the default fails
     */
    private function resolveDefaultValue(
        ReflectionParameter $parameter,
        ReflectionClass $declaringClass,
        ?Throwable $previous
    ): mixed {
        if (! $parameter->isDefaultValueAvailable()) {
            $type = $parameter->getType();

            throw new AutowiringException(\sprintf(
                'Cannot autowire %s::__construct(): parameter $%s (%s) requires an explicit service or factory.',
                $declaringClass->getName(),
                $parameter->getName(),
                null === $type ? 'untyped' : (string) $type,
            ), 0, $previous);
        }

        try {
            return $parameter->getDefaultValue();
        } catch (Throwable $throwable) {
            throw ContainerException::forInvalidService($declaringClass->getName(), $throwable);
        }
    }
}
