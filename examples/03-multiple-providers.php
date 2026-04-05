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
use Psr\Container\ContainerInterface;

use function FastForward\Container\container;

require __DIR__ . '/bootstrap.php';

final readonly class ProductCatalog
{
    /**
     * @param array<string, int> $pricesInCents
     * @param array $pricesInCents
     */
    public function __construct(
        private array $pricesInCents,
    ) {}

    /**
     * @param string $sku
     *
     * @return int
     */
    public function priceFor(string $sku): int
    {
        return $this->pricesInCents[$sku];
    }
}

final readonly class MoneyFormatter
{
    /**
     * @param string $currency
     */
    public function __construct(
        private string $currency,
    ) {}

    /**
     * @param int $amountInCents
     *
     * @return string
     */
    public function format(int $amountInCents): string
    {
        return sprintf('%s %.2f', $this->currency, $amountInCents / 100);
    }
}

final readonly class CheckoutService
{
    /**
     * @param ProductCatalog $catalog
     * @param MoneyFormatter $money
     */
    public function __construct(
        private ProductCatalog $catalog,
        private MoneyFormatter $money,
    ) {}

    /**
     * @param string $sku
     *
     * @return array<string, string>
     */
    public function summarize(string $sku): array
    {
        $amount = $this->catalog->priceFor($sku);

        return [
            'sku' => $sku,
            'total' => $this->money->format($amount),
        ];
    }
}

exampleTitle(
    '03 Multiple providers',
    'Split registrations by feature and compose them with the container() helper.',
);

$catalogProvider = new ArrayServiceProvider([
    ProductCatalog::class => static fn(): ProductCatalog => new ProductCatalog([
        'starter-kit' => 4_990,
        'pro-kit' => 12_990,
    ]),
]);

$checkoutProvider = new ArrayServiceProvider([
    MoneyFormatter::class => static fn(): MoneyFormatter => new MoneyFormatter('BRL'),
    CheckoutService::class => static fn(ContainerInterface $container): CheckoutService => new CheckoutService(
        $container->get(ProductCatalog::class),
        $container->get(MoneyFormatter::class),
    ),
]);

$container = container($catalogProvider, $checkoutProvider);
$checkout = $container->get(CheckoutService::class);

exampleValue('Checkout summary', $checkout->summarize('starter-kit'));
