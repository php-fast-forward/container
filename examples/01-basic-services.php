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

final readonly class ApplicationName
{
    /**
     * @param string $value
     */
    public function __construct(
        public string $value,
    ) {}
}

final readonly class Greeter
{
    /**
     * @param string $applicationName
     */
    public function __construct(
        private string $applicationName,
    ) {}

    /**
     * @param string $name
     *
     * @return string
     */
    public function greet(string $name): string
    {
        return sprintf('Hello %s, welcome to %s.', $name, $this->applicationName);
    }
}

exampleTitle(
    '01 Basic services',
    'Register a provider with small value objects and concrete services, then fetch both from the container.',
);

$provider = new ArrayServiceProvider([
    ApplicationName::class => static fn(): ApplicationName => new ApplicationName('FastForward Container'),
    Greeter::class => static fn(ContainerInterface $container): Greeter => new Greeter(
        $container->get(ApplicationName::class)->value,
    ),
]);

$container = container($provider);
$greeter = $container->get(Greeter::class);

exampleValue('Resolved application name', $container->get(ApplicationName::class)->value);
exampleValue('Greeting', $greeter->greet('team'));
