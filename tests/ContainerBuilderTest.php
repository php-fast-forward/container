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

namespace FastForward\Container\Tests;

use stdClass;
use FastForward\Config\ArrayConfig;
use FastForward\Container\AggregateContainer;
use FastForward\Container\Autowire\ReflectionContainer;
use FastForward\Container\AutowireContainer;
use FastForward\Container\ContainerBuilder;
use FastForward\Container\ContainerInterface;
use FastForward\Container\Exception\InvalidArgumentException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\Factory\ServiceFactory;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use FastForward\Container\ServiceProvider\AggregateServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @internal
 */
#[CoversClass(ContainerBuilder::class)]
#[UsesClass(AggregateContainer::class)]
#[UsesClass(AutowireContainer::class)]
#[UsesClass(ReflectionContainer::class)]
#[UsesClass(ServiceProviderContainer::class)]
#[UsesClass(ArrayServiceProvider::class)]
#[UsesClass(ServiceFactory::class)]
#[UsesClass(NotFoundException::class)]
#[UsesClass(InvalidArgumentException::class)]
#[UsesClass(AggregateServiceProvider::class)]
final class ContainerBuilderTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function successiveBuildsResolveServicesLazilyWithIndependentCaches(): void
    {
        $calls = 0;
        $provider = new ArrayServiceProvider([
            'service' => static function () use (&$calls): stdClass {
                ++$calls;

                return new stdClass();
            },
        ]);
        $builder = (new ContainerBuilder())->add($provider);
        $first = $builder->build();
        $second = $builder->build();

        self::assertSame(0, $calls);

        $firstService = $first->get('service');
        $secondService = $second->get('service');

        self::assertNotSame($first, $second);
        self::assertNotSame($firstService, $secondService);
        self::assertSame($firstService, $first->get('service'));
        self::assertSame($secondService, $second->get('service'));
        self::assertSame(2, $calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function laterRegistrationsAffectOnlySubsequentBuilds(): void
    {
        $builder = new ContainerBuilder();
        $first = $builder->build();
        $builder->add(new ArrayServiceProvider([
            'feature' => static fn(): string => 'enabled',
        ]));
        $second = $builder->build();

        self::assertFalse($first->has('feature'));
        self::assertSame('enabled', $second->get('feature'));
    }

    /**
     * @return void
     */
    #[Test]
    public function fluentNamedRegistrationsRetainPrecedenceAndCrossSourceDependencies(): void
    {
        $first = new ArrayServiceProvider([
            'selected' => static fn(PsrContainerInterface $container): string => 'first/' . $container->get(
                'dependency'
            ),
        ]);
        $second = new ArrayServiceProvider([
            'selected' => static fn(): string => 'second',
            'dependency' => static fn(PsrContainerInterface $container): string => $container->get('config.app.name'),
        ]);
        $config = new ArrayConfig([
            'app' => [
                'name' => 'storefront',
            ],
        ]);
        $builder = new ContainerBuilder();

        self::assertSame($builder, $builder->add(primary: $first));
        $container = $builder->add(fallback: $second, configuration: $config)->build();

        self::assertSame('first/storefront', $container->get('selected'));
        self::assertSame('storefront', $container->get('dependency'));
    }

    /**
     * @return void
     */
    #[Test]
    public function suppliedContainersAreReusedAcrossBuilds(): void
    {
        $external = new BuilderSourceContainer();
        $builder = (new ContainerBuilder())->add($external);
        $first = $builder->build();
        $second = $builder->build();

        self::assertSame($external, $first->get(BuilderSourceContainer::class));
        self::assertSame($external, $second->get(BuilderSourceContainer::class));
        self::assertSame($first->get('external'), $second->get('external'));
    }

    /**
     * @return void
     */
    #[Test]
    public function classStringContainersAreConstructedForEachBuild(): void
    {
        $builder = (new ContainerBuilder())->add(BuilderSourceContainer::class);
        $first = $builder->build();
        $second = $builder->build();

        self::assertNotSame($first->get(BuilderSourceContainer::class), $second->get(BuilderSourceContainer::class));
        self::assertNotSame($first->get('external'), $second->get('external'));
    }

    /**
     * @return void
     */
    #[Test]
    public function invalidOptionalConfigurationKeepsPriorAndLaterExplicitSources(): void
    {
        $config = new ArrayConfig([
            ContainerInterface::class => [
                new ArrayServiceProvider([
                    'before' => static fn(): string => 'kept',
                ]),
                new stdClass(),
                new ArrayServiceProvider([
                    'after' => static fn(): string => 'skipped',
                ]),
            ],
        ]);
        $container = (new ContainerBuilder())->add($config)
            ->add(new ArrayServiceProvider([
                'explicit' => static fn(): string => 'available',
            ]))->build();

        self::assertSame('kept', $container->get('before'));
        self::assertSame('available', $container->get('explicit'));
        self::assertFalse($container->has('after'));
    }

    /**
     * @param string $initializer
     *
     * @return void
     */
    #[Test]
    #[DataProvider('unsupportedInitializers')]
    public function unsupportedClassStringsAreRejectedDuringBuild(string $initializer): void
    {
        $builder = (new ContainerBuilder())->add($initializer);

        $this->expectException(InvalidArgumentException::class);

        $builder->build();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsupportedInitializers(): iterable
    {
        yield 'unknown class' => ['UnknownContainerBuilderInitializer'];
        yield 'unsupported object' => [stdClass::class];
    }
}

/**
 * @internal
 */
final readonly class BuilderSourceContainer implements PsrContainerInterface
{
    /**
     * Creates a source with instance-owned state.
     *
     * @param stdClass $value
     */
    public function __construct(
        private stdClass $value = new stdClass()
    ) {}

    /**
     * @param string $id
     *
     * @return mixed
     *
     * @throws NotFoundException if the requested entry is absent
     */
    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw NotFoundException::forServiceID($id);
        }

        return $this->value;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return 'external' === $id;
    }
}
