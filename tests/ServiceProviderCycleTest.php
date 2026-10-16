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

use Throwable;
use LogicException;
use RuntimeException;
use stdClass;
use WeakReference;
use FastForward\Container\AggregateContainer;
use FastForward\Container\Autowire\ReflectionContainer;
use FastForward\Container\AutowireContainer;
use FastForward\Container\ContainerBuilder;
use FastForward\Container\ContainerFactory;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\Extension\ServiceExtension;
use FastForward\Container\Factory\AliasFactory;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(ServiceProviderContainer::class)]
#[UsesClass(ArrayServiceProvider::class)]
#[UsesClass(AliasFactory::class)]
#[UsesClass(ContainerException::class)]
#[UsesClass(NotFoundException::class)]
#[UsesClass(ServiceExtension::class)]
#[UsesClass(AggregateContainer::class)]
#[UsesClass(AutowireContainer::class)]
#[UsesClass(ReflectionContainer::class)]
#[UsesClass(ContainerFactory::class)]
#[UsesClass(ContainerBuilder::class)]
final class ServiceProviderCycleTest extends TestCase
{
    /**
     * @param array<string, string> $aliases the alias targets
     * @param string $path the expected active resolution path
     *
     * @return void
     */
    #[Test]
    #[DataProvider('aliasCycles')]
    public function aliasCyclesFailBeforeTheFixtureRecursionLimit(array $aliases, string $path): void
    {
        $calls = 0;
        $container = new ServiceProviderContainer(new ArrayServiceProvider($this->boundedAliases($aliases, $calls)));

        try {
            $container->get('a');
            self::fail('Circular aliases must fail promptly.');
        } catch (ContainerException $containerException) {
            $this->assertCyclePath($containerException, $path);
            self::assertSame(\count($aliases), $calls);
        }
    }

    /**
     * @return iterable<string, array{array<string, string>, string}> direct and nested alias cycles
     */
    public static function aliasCycles(): iterable
    {
        yield 'self alias' => [[
            'a' => 'a',
        ], 'a -> a'];
        yield 'two aliases' => [[
            'a' => 'b',
            'b' => 'a',
        ], 'a -> b -> a'];
        yield 'nested cycle' => [[
            'a' => 'b',
            'b' => 'c',
            'c' => 'b',
        ], 'a -> b -> c -> b'];
    }

    /**
     * @return void
     */
    #[Test]
    public function factoryCyclesAcrossProvidersRetainTheCompleteWrapperPath(): void
    {
        $calls = 0;
        $container = ContainerFactory::create(
            new ArrayServiceProvider([
                'a' => static function (ContainerInterface $container) use (&$calls): mixed {
                    if (++$calls > 8) {
                        throw new LogicException('Fixture recursion limit reached.');
                    }

                    return $container->get('b');
                },
            ]),
            new ArrayServiceProvider([
                'b' => static function (ContainerInterface $container) use (&$calls): mixed {
                    if (++$calls > 8) {
                        throw new LogicException('Fixture recursion limit reached.');
                    }

                    return $container->get('a');
                },
            ]),
        );

        try {
            $container->get('a');
            self::fail('The provider cycle must be detected.');
        } catch (ContainerException $containerException) {
            $this->assertCyclePath($containerException, 'a -> b -> a');
            self::assertSame(2, $calls);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function sameIdentifierCanFallBackToAnotherProviderDuringConstruction(): void
    {
        $calls = 0;
        $container = ContainerFactory::create(
            new ArrayServiceProvider([
                'a' => static function (ContainerInterface $container) use (&$calls): mixed {
                    if (++$calls > 8) {
                        throw new LogicException('Fixture recursion limit reached.');
                    }

                    return $container->get('a');
                },
            ]),
            new ArrayServiceProvider([
                'a' => static fn(): string => 'fallback',
            ]),
        );

        self::assertSame('fallback', $container->get('a'));
        self::assertSame('fallback', $container->get('a'));
        self::assertSame(1, $calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function extensionCycleCleanupAllowsRetriesAndIndependentServices(): void
    {
        $cycle = true;
        $calls = 0;
        $service = new stdClass();
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'a' => static fn(): stdClass => $service,
            'independent' => static fn(): string => 'ready',
        ], [
            'a' => static function (ContainerInterface $container, stdClass $previous) use (&$cycle, &$calls): mixed {
                if (++$calls > 8) {
                    throw new LogicException('Fixture recursion limit reached.');
                }

                return $cycle ? $container->get('a') : $previous;
            },
        ]));

        try {
            $container->get('a');
            self::fail('The extension cycle must be detected.');
        } catch (ContainerException $containerException) {
            $this->assertCyclePath($containerException, 'a -> a');
        }

        self::assertSame('ready', $container->get('independent'));
        $cycle = false;

        self::assertSame($service, $container->get('a'));
        self::assertSame($service, $container->get('a'));
        self::assertSame(2, $calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function ordinaryFactoryFailureDoesNotPoisonRetries(): void
    {
        $failure = new RuntimeException('Temporary factory failure.');
        $state = (object) [
            'fail' => true,
        ];
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'a' => static function () use ($state, $failure): string {
                if ($state->fail) {
                    throw $failure;
                }

                return 'ready';
            },
        ]));

        try {
            $container->get('a');
            self::fail('The original failure must be retained.');
        } catch (ContainerException $containerException) {
            self::assertSame($failure, $containerException->getPrevious());
        }

        $state->fail = false;
        self::assertSame('ready', $container->get('a'));
    }

    /**
     * @return void
     */
    #[Test]
    public function repeatedMissingEntriesRemainNotFound(): void
    {
        $container = new ServiceProviderContainer(new ArrayServiceProvider());

        for ($attempt = 0; $attempt < 2; ++$attempt) {
            try {
                $container->get('missing');
                self::fail('The entry must remain missing.');
            } catch (NotFoundException $failure) {
                self::assertSame('Service "missing" not found.', $failure->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function independentWrappersCanResolveTheSameIdentifier(): void
    {
        $other = new ServiceProviderContainer(new ArrayServiceProvider([
            'a' => static fn(): string => 'other composition',
        ]));
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'a' => static fn(): mixed => $other->get('a'),
        ]));

        self::assertSame('other composition', $container->get('a'));
    }

    /**
     * @return void
     */
    #[Test]
    public function ordinaryAliasesShareTheCompletedService(): void
    {
        $calls = 0;
        $service = new stdClass();
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'a' => new AliasFactory('b'),
            'b' => new AliasFactory('base'),
            'base' => static function () use (&$calls, $service): stdClass {
                ++$calls;

                return $service;
            },
        ]));

        self::assertSame($service, $container->get('a'));
        self::assertSame($service, $container->get('b'));
        self::assertSame($service, $container->get('base'));
        self::assertSame($service, $container->get('a'));
        self::assertSame(1, $calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function completedResolutionDoesNotRetainItsWrapper(): void
    {
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'a' => static fn(): string => 'ready',
        ]));
        $reference = WeakReference::create($container);

        self::assertSame('ready', $container->get('a'));
        unset($container);
        gc_collect_cycles();

        self::assertNull($reference->get());
    }

    /**
     * @param array<string, string> $aliases the alias graph to exercise
     * @param int $calls the observable number of executed factories
     *
     * @return array<string, callable(ContainerInterface): mixed> bounded factories for safe baseline failures
     */
    private function boundedAliases(array $aliases, int &$calls): array
    {
        $factories = [];

        foreach ($aliases as $id => $target) {
            $alias = new AliasFactory($target);
            $factories[$id] = static function (ContainerInterface $container) use ($alias, &$calls): mixed {
                if (++$calls > 8) {
                    throw new LogicException('Fixture recursion limit reached.');
                }

                return $alias($container);
            };
        }

        return $factories;
    }

    /**
     * @param ContainerException $failure the wrapped construction failure
     * @param string $path the expected complete cycle path
     *
     * @return void
     */
    private function assertCyclePath(ContainerException $failure, string $path): void
    {
        $cause = $failure;

        while ($cause->getPrevious() instanceof Throwable) {
            $cause = $cause->getPrevious();
        }

        self::assertSame('Circular dependency: ' . $path . '.', $cause->getMessage());
    }
}
