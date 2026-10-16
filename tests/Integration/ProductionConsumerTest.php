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

namespace FastForward\Container\Tests\Integration;

use FastForward\Config\ArrayConfig;
use FastForward\Container\ContainerBuilder;
use FastForward\Container\ContainerFactory;
use FastForward\Container\ContainerInterface;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\Tests\Integration\Fixture\ConfiguredDependency;
use FastForward\Container\Tests\Integration\Fixture\ConsumerDependency;
use FastForward\Container\Tests\Integration\Fixture\ConsumerService;
use FastForward\Container\Tests\Integration\Fixture\OptionalDependency;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;

use function FastForward\Container\container;

/**
 * Verifies an installed production graph with a separate PHPUnit harness.
 *
 * @internal
 */
#[CoversNothing]
final class ProductionConsumerTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function realConfigurationAndProvidersResolveThroughIndependentCompositions(): void
    {
        $config = new ArrayConfig([
            'app' => [
                'name' => 'production-consumer',
            ],
            ContainerInterface::class => [
                new ArrayServiceProvider([
                    ConsumerDependency::class => static fn(PsrContainerInterface $container): ConsumerDependency => new ConfiguredDependency(
                        $container->get('config.app.name'),
                    ),
                    'nullable' => static fn(): mixed => null,
                ]),
            ],
        ]);
        $containers = [
            ContainerFactory::create($config),
            (new ContainerBuilder())->add($config)->build(),
            container($config),
        ];

        foreach ($containers as $container) {
            $service = $container->get(ConsumerService::class);

            self::assertInstanceOf(ConsumerService::class, $service);
            self::assertSame('production-consumer', $container->get('config.app.name'));
            self::assertSame('production-consumer', $service->dependency->name());
            self::assertInstanceOf(OptionalDependency::class, $service->optional);
            self::assertSame($service, $container->get(ConsumerService::class));
            self::assertSame($service->dependency, $container->get(ConsumerDependency::class));
            self::assertTrue($container->has('nullable'));
            self::assertNull($container->get('nullable'));
        }

        self::assertNotSame($containers[0]->get(ConsumerService::class), $containers[1]->get(ConsumerService::class));
    }
}
