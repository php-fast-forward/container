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

use RuntimeException;
use stdClass;
use FastForward\Config\ArrayConfig;
use FastForward\Container\AggregateContainer;
use FastForward\Container\ContainerBuilder;
use FastForward\Container\ContainerFactory;
use FastForward\Container\ContainerInterface;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\InvalidArgumentException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\Factory\AliasFactory;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use FastForward\Container\Tests\Integration\Fixture\CompositionProvider;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;

use function FastForward\Container\container;

/**
 * Exercises the default composition contract against development and isolated production graphs.
 */
#[CoversNothing]
final class ProviderCompositionTest extends TestCase
{
    /**
     * @return iterable<string, array{bool, string}>
     */
    public static function providerOrders(): iterable
    {
        foreach (['helper', 'factory', 'builder'] as $entryPoint) {
            yield $entryPoint . ' factory first' => [false, $entryPoint];
            yield $entryPoint . ' extension first' => [true, $entryPoint];
        }
    }

    /**
     * @param bool $extensionFirst
     * @param string $entryPoint
     *
     * @return void
     */
    #[DataProvider('providerOrders')]
    public function testEveryExtensionRunsOnceAfterFactorySelection(bool $extensionFirst, string $entryPoint): void
    {
        $events = [];
        $factory = new ArrayServiceProvider([
            'demo' => static function () use (&$events): string {
                $events[] = 'factory';

                return 'factory';
            },
        ]);
        $first = new ArrayServiceProvider(extensions: [
            'demo' => static function ($c, $previous) use (&$events): string {
                $events[] = 'first';

                return $previous . '|first';
            },
        ]);
        $second = new ArrayServiceProvider(extensions: [
            'demo' => static function ($c, $previous) use (&$events): string {
                $events[] = 'second';

                return $previous . '|second';
            },
        ]);
        $sources = $extensionFirst ? [$first, $factory, $second] : [$factory, $first, $second];
        $container = match ($entryPoint) {
            'helper' => container(...$sources),
            'factory' => ContainerFactory::create(...$sources),
            default => (new ContainerBuilder())->add(...$sources)->build(),
        };

        self::assertSame([], $events);
        self::assertTrue($container->has('demo'));
        self::assertSame([], $events);
        self::assertSame('factory|first|second', $container->get('demo'));
        self::assertSame('factory|first|second', $container->get('demo'));
        self::assertSame(['factory', 'first', 'second'], $events);
    }

    /**
     * @return void
     */
    public function testEveryEntryPointKeepsFirstFactoryAndComposesExtensionsByDefault(): void
    {
        $first = new ArrayServiceProvider([
            '42' => static fn(): string => 'first',
        ]);
        $last = new ArrayServiceProvider([
            '42' => static fn(): string => 'last',
        ]);
        $builder = (new ContainerBuilder())->add($first, $last);
        $built = $builder->build();

        self::assertSame('first', $built->get('42'));
        self::assertSame('first', container($first, $last)->get('42'));
        self::assertSame('first', $builder->build()->get('42'));
        self::assertSame('first', ContainerFactory::create($first, $last)->get('42'));

        $extension = new ArrayServiceProvider(extensions: [
            '42' => static fn($c, $v): string => ($v ?? 'missing') . '!',
        ]);
        self::assertSame('first!', container($first, $extension)->get('42'));
        self::assertSame('first!', ContainerFactory::create($extension, $first, $last)->get('42'));
        self::assertSame('first!', (new ContainerBuilder())->add($extension, $first, $last)->build()->get('42'));
        self::assertSame('first!', container($extension, $first)->get('42'));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function values(): iterable
    {
        yield 'null' => [null];
        yield 'false' => [false];
        yield 'zero' => [0];
        yield 'string' => ['value'];
        yield 'object' => [new stdClass()];
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    #[DataProvider('values')]
    public function testExplicitValuesAndFinalNullAreShared(mixed $value): void
    {
        $calls = 0;
        $seen = [];
        $container = (new ContainerBuilder())
            ->add(
                new ArrayServiceProvider(extensions: [
                    'value' => static function ($c, $previous) use (&$seen): mixed {
                        $seen[] = $previous;

                        return null;
                    },
                ]),
                new ArrayServiceProvider([
                    'value' => static function () use ($value, &$calls): mixed {
                        ++$calls;

                        return $value;
                    },
                ]),
            )->build();

        self::assertTrue($container->has('value'));
        self::assertNull($container->get('value'));
        self::assertNull($container->get('value'));
        self::assertTrue($container->has('value'));
        self::assertSame([$value], $seen);
        self::assertSame(1, $calls);
    }

    /**
     * @return void
     */
    public function testAbsentFactoryDefaultsAndVoidAndReplacementExtensions(): void
    {
        $seen = [];
        $replacement = new stdClass();
        $container = (new ContainerBuilder())
            ->add(
                new ArrayServiceProvider(extensions: [
                    'entry' => static function ($c, $previous) use (&$seen): string {
                        $seen[] = $previous;

                        return 'default';
                    },
                ]),
                new ArrayServiceProvider(extensions: [
                    'entry' => static function ($c, $previous) use (&$seen): void {
                        $seen[] = $previous;
                    },
                ]),
                new ArrayServiceProvider(extensions: [
                    'entry' => static function ($c, $previous) use (&$seen, $replacement): stdClass {
                        $seen[] = $previous;

                        return $replacement;
                    },
                ]),
            )->build();

        self::assertSame($replacement, $container->get('entry'));
        self::assertSame($replacement, $container->get('entry'));
        self::assertSame([null, 'default', 'default'], $seen);
        self::assertFalse($container->has('unknown'));
        $this->expectException(NotFoundException::class);
        $container->get('unknown');
    }

    /**
     * @return void
     */
    public function testExactClassExtensionsAndAliasesReuseFinalIdentity(): void
    {
        $original = new stdClass();
        $replacement = new stdClass();
        $calls = 0;
        $container = (new ContainerBuilder())
            ->add(
                new ArrayServiceProvider(extensions: [
                    stdClass::class => static function ($c, $previous) use (&$calls, $original): stdClass {
                        self::assertSame($original, $previous);
                        ++$calls;

                        return $previous;
                    },
                ]),
                new ArrayServiceProvider([
                    'entry' => static fn(): stdClass => $original,
                    'alias' => new AliasFactory('entry'),
                    stdClass::class => new AliasFactory('entry'),
                    'original' => static fn(): stdClass => $original,
                ]),
                new ArrayServiceProvider(extensions: [
                    stdClass::class => static fn(): stdClass => $replacement,
                ]),
            )->build();

        self::assertSame($replacement, $container->get('entry'));
        self::assertSame($replacement, $container->get('alias'));
        self::assertSame($replacement, $container->get('original'));
        self::assertSame($replacement, $container->get(stdClass::class));
        self::assertSame(1, $calls);
    }

    /**
     * @param string $stage
     *
     * @return void
     */
    #[DataProvider('failureStages')]
    public function testFailureCannotFallBackAndIncompleteChainsAreRetried(string $stage): void
    {
        $failure = new RuntimeException('Required ' . $stage . ' failed');
        $attempts = 0;
        $extensions = 0;
        $fallback = 0;
        $callback = static function () use (&$attempts, $failure): string {
            if (1 === ++$attempts) {
                throw $failure;
            }

            return 'recovered';
        };
        $external = new ServiceProviderContainer(new ArrayServiceProvider([
            'entry' => static function () use (&$fallback): string {
                ++$fallback;

                return 'silent fallback';
            },
        ]));
        $container = (new ContainerBuilder())
            ->add(
                new ArrayServiceProvider([
                    'entry' => 'factory' === $stage ? $callback : static fn(): string => 'value',
                ]),
                new ArrayServiceProvider(extensions: [
                    'entry' => static function ($c, $previous) use (&$extensions): mixed {
                        ++$extensions;

                        return $previous;
                    },
                ]),
                $external,
                new ArrayServiceProvider(extensions: 'extension' === $stage ? [
                    'entry' => $callback,
                ] : []),
            )->build();

        try {
            $container->get('entry');
            self::fail('Required provider failure must propagate.');
        } catch (ContainerException $containerException) {
            self::assertSame($failure, $containerException->getPrevious());
        }

        self::assertSame('recovered', $container->get('entry'));
        self::assertSame('recovered', $container->get('entry'));
        self::assertSame(2, $attempts);
        self::assertSame('factory' === $stage ? 1 : 2, $extensions);
        self::assertSame(0, $fallback);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function failureStages(): iterable
    {
        yield 'factory' => ['factory'];
        yield 'extension' => ['extension'];
    }

    /**
     * @return void
     */
    public function testCyclesAreReportedAndCanBeRetriedAfterRepair(): void
    {
        $cycle = true;
        $builder = (new ContainerBuilder())
            ->add(
                new ArrayServiceProvider([
                    'a' => new AliasFactory('b'),
                ]),
                new ArrayServiceProvider([
                    'b' => static function ($c) use (&$cycle): mixed {
                        return $cycle ? $c->get('a') : 'repaired';
                    },
                ]),
            );
        $container = $builder->build();
        for ($attempt = 0; $attempt < 2; ++$attempt) {
            try {
                $container->get('a');
                self::fail('Cycle must fail on every attempt.');
            } catch (ContainerException $caught) {
                self::assertStringContainsString('a', $caught->getMessage());
            }
        }

        $cycle = false;
        self::assertSame('repaired', $container->get('a'));
    }

    /**
     * @return void
     */
    public function testMixedSourcesConfiguredProvidersClassStringsAndFreshBuilds(): void
    {
        $external = new ServiceProviderContainer(new ArrayServiceProvider([
            'external' => static fn(): stdClass => new stdClass(),
            'entry' => static fn(): string => 'external',
        ]));
        $wrappers = [];
        $config = new ArrayConfig([
            'app' => [
                'suffix' => '!',
            ],
            ContainerInterface::class => [
                CompositionProvider::class,
                new ArrayServiceProvider(extensions: [
                    'entry' => static function ($c, $previous) use (&$wrappers): string {
                        $wrappers[] = $c;
                        self::assertTrue($c->has('external'));

                        return $previous . $c->get('config.app.suffix');
                    },
                ]),
            ],
        ]);
        $builder = (new ContainerBuilder())
            ->add($config, $external);
        $first = $builder->build();
        $second = $builder->build();
        self::assertSame('provider!', $first->get('entry'));
        self::assertSame('provider!', $second->get('entry'));
        self::assertNotSame($first->get('object'), $second->get('object'));
        self::assertNotSame($first->get(CompositionProvider::class), $second->get(CompositionProvider::class));
        self::assertSame($first->get('external'), $second->get('external'));
        self::assertSame($first->get('container'), $wrappers[0]);
        self::assertNotSame($wrappers[0], $wrappers[1]);
        self::assertSame('external', (new ContainerBuilder())->add($external, $config)->build()->get('entry'));
        $builder->add(new ArrayServiceProvider([
            'entry' => static fn(): string => 'later',
        ]));
        self::assertSame('provider!', $first->get('entry'));
        self::assertSame('provider!', $builder->build()->get('entry'));
    }

    /**
     * @return void
     */
    public function testEmptyAndExternalOnlyCompositionsStillAutowire(): void
    {
        self::assertInstanceOf(stdClass::class, (new ContainerBuilder())->build()->get(stdClass::class));
        $external = new AggregateContainer();
        $container = (new ContainerBuilder())
            ->add($external)->build();
        self::assertInstanceOf(AggregateContainer::class, $container->get(AggregateContainer::class));
        self::assertInstanceOf(stdClass::class, $container->get(stdClass::class));
    }

    /**
     * @return void
     */
    public function testOptionalInvalidConfiguredSourceStopsOnlyThatExpansion(): void
    {
        $config = new ArrayConfig([
            ContainerInterface::class => [
                new ArrayServiceProvider([
                    'kept' => static fn(): string => 'kept',
                ]),
                new stdClass(),
                new ArrayServiceProvider([
                    'skipped' => static fn(): string => 'skipped',
                ]),
            ],
        ]);
        $container = (new ContainerBuilder())
            ->add($config, CompositionProvider::class)->build();
        self::assertSame('kept', $container->get('kept'));
        self::assertFalse($container->has('skipped'));
        self::assertSame('provider', $container->get('entry'));

        $this->expectException(InvalidArgumentException::class);
        (new ContainerBuilder())
            ->add(stdClass::class)->build();
    }

    /**
     * @return void
     *
     * @throws RuntimeException
     */
    public function testDualRoleContainersAreNotCollectedAsProviders(): void
    {
        $source = new class implements PsrContainerInterface, ServiceProviderInterface {
            /**
             * @param string $id
             *
             * @return bool
             */
            public function has(string $id): bool
            {
                return 'external' === $id;
            }

            /**
             * @param string $id
             *
             * @return mixed
             */
            public function get(string $id): mixed
            {
                if (! $this->has($id)) {
                    throw NotFoundException::forServiceID($id);
                }

                return 'external';
            }

            /**
             * @return array
             *
             * @throws RuntimeException
             */
            public function getFactories(): array
            {
                throw new RuntimeException('PSR-11 source must not become a provider.');
            }

            /**
             * @return array
             *
             * @throws RuntimeException
             */
            public function getExtensions(): array
            {
                throw new RuntimeException('PSR-11 source must not become a provider.');
            }
        };
        $container = (new ContainerBuilder())
            ->add(CompositionProvider::class, $source)->build();
        self::assertSame('provider', $container->get('entry'));
        self::assertSame('external', $container->get('external'));
    }

    /**
     * @return void
     */
    public function testNestedConfigurationKeepsOneLevelExpansion(): void
    {
        $nested = new ArrayConfig([
            'nested' => true,
            ContainerInterface::class => [
                new ArrayServiceProvider([
                    'hidden' => static fn(): string => 'hidden',
                ])],
        ]);
        $config = new ArrayConfig([
            ContainerInterface::class => [$nested, CompositionProvider::class],
        ]);
        $container = (new ContainerBuilder())
            ->add($config)->build();
        self::assertSame('provider', $container->get('entry'));
        self::assertTrue($container->get('config.nested'));
        self::assertFalse($container->has('hidden'));
    }

    /**
     * @return void
     */
    public function testRequiredConcreteFactoryFailureCannotBeAutowired(): void
    {
        $failure = new RuntimeException('Explicit concrete factory failed.');
        $container = (new ContainerBuilder())
            ->add(new ArrayServiceProvider([
                stdClass::class => static fn(): never => throw $failure,
            ]))->build();

        try {
            $container->get(stdClass::class);
            self::fail('Autowiring must not hide the explicit factory failure.');
        } catch (ContainerException $containerException) {
            self::assertSame($failure, $containerException->getPrevious());
        }
    }

    /**
     * @return void
     */
    public function testRequiredSourcePropagatesNotFoundButAllowsAbsentEntries(): void
    {
        $failure = NotFoundException::forServiceID('dependency');
        $source = self::createStub(PsrContainerInterface::class);
        $source->method('has')
            ->willReturnCallback(static fn($id): bool => 'broken' === $id);
        $source->method('get')
            ->willThrowException($failure);
        $aggregate = new AggregateContainer();
        $aggregate->appendRequired($source);
        $aggregate->append(new ServiceProviderContainer(new ArrayServiceProvider([
            'other' => static fn(): string => 'available',
            'broken' => static fn(): string => 'fallback',
        ])));
        self::assertSame('available', $aggregate->get('other'));
        $this->expectExceptionObject($failure);
        $aggregate->get('broken');
    }

    /**
     * @return void
     */
    public function testProviderAggregationPreservesInterleavedExternalSourcePrecedence(): void
    {
        $extensions = 0;
        $earlier = new ArrayServiceProvider(
            [
                'unrelated' => static fn(): string => 'unrelated',
            ],
            [
                'entry' => static function ($c, $previous) use (&$extensions): string {
                    ++$extensions;

                    return $previous . '|extended';
                },
            ],
        );
        $external = new ServiceProviderContainer(new ArrayServiceProvider([
            'entry' => static fn(): string => 'external',
        ]));
        $later = new ArrayServiceProvider([
            'entry' => static fn(): string => 'provider',
        ]);

        foreach ([
            container($earlier, $external, $later),
            ContainerFactory::create($earlier, $external, $later),
            (new ContainerBuilder())->add($earlier, $external, $later)->build(),
        ] as $container) {
            self::assertSame('external', $container->get('entry'));
            self::assertSame('unrelated', $container->get('unrelated'));
        }

        self::assertSame(0, $extensions);
        self::assertSame('provider|extended', container($earlier, $later, $external)->get('entry'));
        self::assertSame(1, $extensions);
    }

    /**
     * @return void
     */
    public function testNullFactoryKeepsPrecedenceOverLaterFactoryAcrossEntryPoints(): void
    {
        $laterCalls = 0;
        $first = new ArrayServiceProvider([
            'entry' => static fn(): mixed => null,
        ]);
        $later = new ArrayServiceProvider([
            'entry' => static function () use (&$laterCalls): string {
                ++$laterCalls;

                return 'later';
            },
        ]);

        foreach ([
            container($first, $later),
            ContainerFactory::create($first, $later),
            (new ContainerBuilder())->add($first, $later)->build(),
        ] as $container) {
            self::assertTrue($container->has('entry'));
            self::assertNull($container->get('entry'));
            self::assertNull($container->get('entry'));
        }

        self::assertSame(0, $laterCalls);
    }

    /**
     * @return void
     */
    public function testInferredClassAliasRetainsItsFactorySourcePosition(): void
    {
        $externalValue = new stdClass();
        $providerValue = new stdClass();
        $external = new ServiceProviderContainer(new ArrayServiceProvider([
            stdClass::class => static fn(): stdClass => $externalValue,
        ]));
        $earlier = new ArrayServiceProvider([
            'unrelated' => static fn(): string => 'unrelated',
        ]);
        $later = new ArrayServiceProvider([
            'entry' => static fn(): stdClass => $providerValue,
        ]);
        $container = container($earlier, $external, $later);

        self::assertSame($providerValue, $container->get('entry'));
        self::assertSame($externalValue, $container->get(stdClass::class));

        $providerFirst = container($earlier, $later, $external);
        self::assertSame($providerValue, $providerFirst->get('entry'));
        self::assertSame($providerValue, $providerFirst->get(stdClass::class));
    }
}
