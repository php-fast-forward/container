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

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Prints a standard title for CLI examples.
 *
 * @param string $title
 * @param string $description
 *
 * @return void
 */
function exampleTitle(string $title, string $description): void
{
    echo "\n";
    echo $title . "\n";
    echo str_repeat('=', strlen($title)) . "\n";
    echo $description . "\n\n";
}

/**
 * Prints a labeled value in a consistent format.
 *
 * @param string $label
 * @param mixed $value
 *
 * @return void
 */
function exampleValue(string $label, mixed $value): void
{
    if (\is_array($value)) {
        $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } elseif (\is_bool($value)) {
        $value = $value ? 'true' : 'false';
    } elseif (! \is_scalar($value) && null !== $value) {
        $value = var_export($value, true);
    }

    echo sprintf("%s: %s\n", $label, (string) $value);
}
