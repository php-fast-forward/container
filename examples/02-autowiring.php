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

use FastForward\Container\ServiceProvider\ArrayServiceProvider;

use function FastForward\Container\container;

require __DIR__ . '/bootstrap.php';

final readonly class SystemClock
{
    /**
     * @param string $timezone
     */
    public function __construct(
        private string $timezone,
    ) {}

    /**
     * @return string
     */
    public function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone($this->timezone)))->format(\DATE_ATOM);
    }
}

final readonly class OrderRepository
{
    /**
     * @param SystemClock $clock
     */
    public function __construct(
        private SystemClock $clock,
    ) {}

    /**
     * @param string $sku
     *
     * @return array<string, string>
     */
    public function save(string $sku): array
    {
        return [
            'sku' => $sku,
            'createdAt' => $this->clock->now(),
        ];
    }
}

final readonly class CreateOrderHandler
{
    /**
     * @param OrderRepository $orders
     */
    public function __construct(
        private OrderRepository $orders,
    ) {}

    /**
     * @param string $sku
     *
     * @return array<string, string>
     */
    public function handle(string $sku): array
    {
        return $this->orders->save($sku);
    }
}

exampleTitle(
    '02 Autowiring',
    'Register only the dependency that needs configuration and let the container autowire the rest.',
);

$provider = new ArrayServiceProvider([
    SystemClock::class => static fn(): SystemClock => new SystemClock('America/Sao_Paulo'),
]);

$container = container($provider);
$handler = $container->get(CreateOrderHandler::class);

exampleValue('Created order', $handler->handle('FF-CONTAINER-01'));
