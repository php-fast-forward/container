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

use FastForward\Container\ContainerBuilder;
use FastForward\Container\ContainerFactory;

use function FastForward\Container\container;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;

require ($argv[1] ?? dirname(__DIR__, 4) . '/vendor/autoload.php');

$calls = 0;
$extension = new ArrayServiceProvider(extensions: [
    'demo' => static function ($container, $previous) use (&$calls): string {
        ++$calls;

        return ($previous ?? 'missing') . '|extended';
    },
]);
$factory = new ArrayServiceProvider(['demo' => static fn(): string => 'factory']);

foreach ([[$factory, $extension], [$extension, $factory]] as $providers) {
    foreach ([container(...$providers), ContainerFactory::create(...$providers), (new ContainerBuilder())->add(...$providers)->build()] as $container) {
        if ('factory|extended' !== $container->get('demo') || 'factory|extended' !== $container->get('demo')) {
            throw new RuntimeException('Provider order or shared result failed.');
        }
    }
}

if (6 !== $calls) {
    throw new RuntimeException('Extensions must run exactly once in each build.');
}

echo "provider-composition: ok\n";
