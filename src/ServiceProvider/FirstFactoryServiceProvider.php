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

namespace FastForward\Container\ServiceProvider;

use Interop\Container\ServiceProviderInterface;

/**
 * Build-local aggregate retaining the first callable factory for each identifier.
 *
 * All extensions use AggregateServiceProvider's registration-order composition.
 * Explicit AggregateServiceProvider instances retain later-factory precedence.
 *
 * @internal
 */
final class FirstFactoryServiceProvider extends AggregateServiceProvider
{
    /**
     * @var list<ServiceProviderInterface> provider positions in the expanded initializer list
     */
    private readonly array $providers;

    /**
     * @var array<class-string, int> source positions of inferred object aliases
     */
    private array $classAliasOwners = [];

    /**
     * @param ServiceProviderInterface ...$providers ordered provider definitions
     */
    public function __construct(ServiceProviderInterface ...$providers)
    {
        parent::__construct(...$providers);
        $this->providers = $providers;
    }

    /**
     * Identifies the source position of an entry without executing service callbacks.
     *
     * Factory selection precedes extension-only defaults. Identity/inferred aliases
     * with no explicit definition belong to the first relevant provider position.
     *
     * @param string $id the requested entry
     * @param int $index the provider position to check
     *
     * @return bool whether this provider owns the selected entry
     */
    public function ownsEntry(string $id, int $index): bool
    {
        foreach ($this->providers as $position => $provider) {
            if (\is_callable($provider->getFactories()[$id] ?? null)) {
                return $position === $index;
            }
        }

        foreach ($this->providers as $position => $provider) {
            if (\is_callable($provider->getExtensions()[$id] ?? null)) {
                return $position === $index;
            }
        }

        foreach ($this->providers as $position => $provider) {
            if ($id === $provider::class) {
                return $position === $index;
            }
        }

        return ($this->classAliasOwners[$id] ?? 0) === $index;
    }

    /**
     * Retains the source position of an inferred alias after successful resolution.
     *
     * @param mixed $value the completed service value
     * @param int $index the selected provider position
     *
     * @return void
     */
    public function rememberClassAlias(mixed $value, int $index): void
    {
        if (\is_object($value)) {
            $this->classAliasOwners[$value::class] ??= $index;
        }
    }

    /**
     * Selects only the first callable factory without treating a null result as absence.
     *
     * @param array<string, callable> $factories definitions selected so far
     * @param array<string, callable> $next definitions from the next provider
     *
     * @return array<string, callable> selected factory definitions
     */
    protected function mergeFactories(array $factories, array $next): array
    {
        return $factories + array_filter($next, static fn(mixed $factory): bool => \is_callable($factory));
    }
}
