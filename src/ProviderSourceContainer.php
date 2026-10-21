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

namespace FastForward\Container;

use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\ServiceProvider\FirstFactoryServiceProvider;

/**
 * Internal ordered view over one build's shared provider adapter.
 *
 * Each view advertises only entries selected at its original provider position.
 * The shared adapter applies all extensions and owns final values and alias state.
 *
 * @internal
 */
final readonly class ProviderSourceContainer implements ContainerInterface
{
    /**
     * @param ServiceProviderContainer $container the shared build-local adapter
     * @param FirstFactoryServiceProvider $provider the shared definitions and factory selection
     * @param int $index this provider's position among provider initializers
     */
    public function __construct(
        private ServiceProviderContainer $container,
        private FirstFactoryServiceProvider $provider,
        private int $index,
    ) {}

    /**
     * @param string $id the requested entry identifier
     *
     * @return bool whether this source owns the selected entry
     */
    public function has(string $id): bool
    {
        if (0 === $this->index && ServiceProviderContainer::class === $id) {
            return true;
        }

        return $this->container->has($id) && $this->provider->ownsEntry($id, $this->index);
    }

    /**
     * @param string $id the requested entry identifier
     *
     * @return mixed the shared, fully extended entry
     *
     * @throws NotFoundException if another source owns the entry
     */
    public function get(string $id): mixed
    {
        if (0 === $this->index && ServiceProviderContainer::class === $id) {
            return $this->container;
        }

        if (! $this->has($id)) {
            throw NotFoundException::forServiceID($id);
        }

        $value = $this->container->get($id);
        $this->provider->rememberClassAlias($value, $this->index);

        return $value;
    }
}
