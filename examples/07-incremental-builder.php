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
use FastForward\Container\ContainerBuilder;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use Psr\Container\ContainerInterface;

require __DIR__ . '/bootstrap.php';

exampleTitle(
    '07 Incremental builder',
    'Register sources in stages and build a new composition after enabling an optional feature.',
);

$builder = (new ContainerBuilder())->add(new ArrayConfig([
    'app' => [
        'name' => 'FastForward Storefront',
    ],
]));
$builder->add(new ArrayServiceProvider([
    'banner' => static fn(ContainerInterface $container): string => $container->get('config.app.name'),
]));
$original = $builder->build();

if (in_array('--with-feature', $argv, true)) {
    $builder->add(new ArrayServiceProvider([
        'loyalty' => static fn(ContainerInterface $container): string => $container->get('banner') . ' loyalty enabled',
    ]));
}

$current = $builder->build();

exampleValue('Original banner', $original->get('banner'));
exampleValue('Original build has loyalty', $original->has('loyalty'));
exampleValue('Current build has loyalty', $current->has('loyalty'));

if ($current->has('loyalty')) {
    exampleValue('Feature', $current->get('loyalty'));
}
