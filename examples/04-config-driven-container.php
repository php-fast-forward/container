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

use FastForward\Config\ArrayConfig;
use FastForward\Container\ContainerInterface as FastForwardContainerInterface;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use Psr\Container\ContainerInterface;

use function FastForward\Container\container;

require __DIR__ . '/bootstrap.php';

final readonly class ApplicationBanner
{
    /**
     * @param string $name
     * @param string $environment
     */
    public function __construct(
        private string $name,
        private string $environment,
    ) {}

    /**
     * @return string
     */
    public function render(): string
    {
        return sprintf('%s [%s]', $this->name, strtoupper($this->environment));
    }
}

exampleTitle(
    '04 Config-driven container',
    'Load providers from ArrayConfig and read config values through the generated config.* entries.',
);

$config = new ArrayConfig([
    'app' => [
        'name' => 'FastForward Storefront',
        'environment' => 'production',
    ],
    FastForwardContainerInterface::class => [
        new ArrayServiceProvider([
            ApplicationBanner::class => static fn(ContainerInterface $container): ApplicationBanner => new ApplicationBanner(
                $container->get('config.app.name'),
                $container->get('config.app.environment'),
            ),
        ]),
    ],
]);

$container = container($config);
$banner = $container->get(ApplicationBanner::class);

exampleValue('Config value', $container->get('config.app.name'));
exampleValue('Rendered banner', $banner->render());
