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
use WeakReference;
use RuntimeException;
use FastForward\Container\Exception\ContainerException;
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
#[UsesClass(ServiceExtension::class)]
#[UsesClass(AliasFactory::class)]
#[UsesClass(ContainerException::class)]
final class ServiceProviderClassExtensionTest extends TestCase
{
    /**
     * @param bool $classFirst whether the class entry is resolved before the named entry
     *
     * @return void
     */
    #[Test]
    #[DataProvider('lookupOrders')]
    public function distinctObjectsReceiveTheirClassExtensionInEitherOrder(bool $classFirst): void
    {
        $classService = (object) [
            'extensions' => 0,
        ];
        $namedService = (object) [
            'extensions' => 0,
        ];
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            stdClass::class => static fn(): stdClass => $classService,
            'named' => static fn(): stdClass => $namedService,
        ], [
            stdClass::class => static function (ContainerInterface $container, stdClass $service): void {
                ++$service->extensions;
            },
        ]));

        foreach ($classFirst ? [stdClass::class, 'named'] : ['named', stdClass::class] as $id) {
            $container->get($id);
        }

        self::assertSame($classService, $container->get(stdClass::class));
        self::assertSame($namedService, $container->get('named'));
        self::assertNotSame($classService, $namedService);
        self::assertSame(1, $classService->extensions);
        self::assertSame(1, $namedService->extensions);
    }

    /**
     * @return iterable<string, array{bool}> the two independent entry lookup orders
     */
    public static function lookupOrders(): iterable
    {
        yield 'class first' => [true];
        yield 'named first' => [false];
    }

    /**
     * @param string $firstId the first entry requested in the alias chain
     *
     * @return void
     */
    #[Test]
    #[DataProvider('aliasLookups')]
    public function aliasesOfTheCachedClassObjectDoNotRepeatItsExtension(string $firstId): void
    {
        $service = (object) [
            'extensions' => 0,
        ];
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            stdClass::class => static fn(): stdClass => $service,
            'alias' => new AliasFactory(stdClass::class),
            'alias.chain' => new AliasFactory('alias'),
        ], [
            stdClass::class => static function (ContainerInterface $container, stdClass $service): stdClass {
                ++$service->extensions;

                return $service;
            },
        ]));

        self::assertSame($service, $container->get($firstId));
        self::assertSame($service, $container->get(stdClass::class));
        self::assertSame($service, $container->get('alias'));
        self::assertSame($service, $container->get('alias.chain'));
        self::assertSame(1, $service->extensions);
    }

    /**
     * @return iterable<string, array{string}> direct and nested aliases of a class entry
     */
    public static function aliasLookups(): iterable
    {
        yield 'class first' => [stdClass::class];
        yield 'alias first' => ['alias'];
        yield 'nested alias first' => ['alias.chain'];
    }

    /**
     * @param bool $classFirst whether the distinct class entry is resolved before the named alias
     * @param bool $replace whether the class extension replaces the service with a new object
     *
     * @return void
     */
    #[Test]
    #[DataProvider('namedAliasOrders')]
    public function namedAliasesRetainTheExtendedObject(bool $classFirst, bool $replace): void
    {
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            stdClass::class => static fn(): stdClass => (object) [
                'extensions' => 0,
            ],
            'named' => static fn(): stdClass => (object) [
                'extensions' => 0,
            ],
            'alias' => new AliasFactory('named'),
            'alias.chain' => new AliasFactory('alias'),
        ], [
            stdClass::class => static function (ContainerInterface $container, stdClass $service) use (
                $replace
            ): stdClass {
                ++$service->extensions;

                return $replace ? clone $service : $service;
            },
        ]));

        if ($classFirst) {
            $container->get(stdClass::class);
        }

        $alias = $container->get('alias.chain');
        $named = $container->get('named');
        $class = $container->get(stdClass::class);

        self::assertSame($named, $alias);
        self::assertSame($named, $container->get('alias'));
        self::assertSame($named, $container->get('alias.chain'));
        self::assertNotSame($class, $named);
        self::assertSame(1, $class->extensions);
        self::assertSame(1, $named->extensions);
    }

    /**
     * @return iterable<string, array{bool, bool}> lookup orders for mutating and replacing class extensions
     */
    public static function namedAliasOrders(): iterable
    {
        yield 'class first, mutation' => [true, false];
        yield 'named alias first, mutation' => [false, false];
        yield 'class first, replacement' => [true, true];
        yield 'named alias first, replacement' => [false, true];
    }

    /**
     * @param string $classOrder whether the explicit class entry is requested before, after or outside the aliases
     * @param string $replacement the kind of value returned by the class extension
     *
     * @return void
     */
    #[Test]
    #[DataProvider('externalAliasOrders')]
    public function wrapperBackedAliasesShareTheirClassExtensionResult(string $classOrder, string $replacement): void
    {
        $original = new stdClass();
        $state = (object) [
            'calls' => 0,
            'target' => $original,
        ];
        $wrapper = self::createStub(ContainerInterface::class);
        $wrapper->method('get')
            ->willReturnCallback(static fn(): mixed => $state->target);
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            stdClass::class => static fn(): stdClass => $original,
            'first' => new AliasFactory('external'),
            'second' => new AliasFactory('external'),
            'replacement' => new AliasFactory('external'),
        ], [
            stdClass::class => static function (ContainerInterface $container, stdClass $service) use (
                $state,
                $replacement
            ): mixed {
                ++$state->calls;

                return match ($replacement) {
                    'object' => clone $service,
                    'null' => null,
                    'scalar' => 'replacement',
                };
            },
        ]), $wrapper);

        if ('before' === $classOrder) {
            $container->get(stdClass::class);
        }

        $first = $container->get('first');
        if ('object' === $replacement) {
            self::assertInstanceOf(stdClass::class, $first);
            self::assertNotSame($original, $first);
        } else {
            self::assertSame('null' === $replacement ? null : 'replacement', $first);
        }

        self::assertSame($first, $container->get('second'));

        if ('none' !== $classOrder) {
            self::assertSame($first, $container->get(stdClass::class));
        }

        $state->target = $first;
        self::assertSame($first, $container->get('replacement'));
        self::assertSame(1, $state->calls);
    }

    /**
     * @return iterable<string, array{string, string}> class lookup orders for each replacement type
     */
    public static function externalAliasOrders(): iterable
    {
        foreach (['none', 'before', 'after'] as $order) {
            foreach (['object', 'null', 'scalar'] as $replacement) {
                yield $order . ', ' . $replacement => [$order, $replacement];
            }
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function aliasesStillApplyTheirIndividualIdentifierExtensions(): void
    {
        $calls = 0;
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'named' => static fn(): stdClass => (object) [
                'aliases' => [],
            ],
            'first' => new AliasFactory('named'),
            'second' => new AliasFactory('named'),
        ], [
            stdClass::class => static function (ContainerInterface $container, stdClass $service) use (
                &$calls
            ): stdClass {
                ++$calls;

                return clone $service;
            },
            'first' => static function (ContainerInterface $container, stdClass $service): void {
                $service->aliases[] = 'first';
            },
            'second' => static function (ContainerInterface $container, stdClass $service): void {
                $service->aliases[] = 'second';
            },
        ]));

        $first = $container->get('first');
        self::assertSame($first, $container->get('second'));
        self::assertSame($first, $container->get('named'));
        self::assertSame(['first', 'second'], $first->aliases);
        self::assertSame(1, $calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function replacedInputsCanBeCollectedWhileTheirResultRemainsCached(): void
    {
        $reference = null;
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'named' => static function () use (&$reference): stdClass {
                $service = new stdClass();
                $reference = WeakReference::create($service);

                return $service;
            },
        ], [
            stdClass::class => static fn(ContainerInterface $container, stdClass $service): stdClass => clone $service,
        ]));

        $result = $container->get('named');
        gc_collect_cycles();

        self::assertInstanceOf(WeakReference::class, $reference);
        self::assertNull($reference->get());
        self::assertSame($result, $container->get('named'));
    }

    /**
     * @return void
     */
    #[Test]
    public function failedClassExtensionsCanBeRetriedForTheSameInput(): void
    {
        $original = new stdClass();
        $state = (object) [
            'fail' => true,
            'calls' => 0,
        ];
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'named' => static fn(): stdClass => $original,
        ], [
            stdClass::class => static function (ContainerInterface $container, stdClass $service) use (
                $state
            ): stdClass {
                ++$state->calls;
                if ($state->fail) {
                    throw new RuntimeException('Temporary extension failure.');
                }

                return clone $service;
            },
        ]));

        try {
            $container->get('named');
            self::fail('The class extension must fail on its first invocation.');
        } catch (ContainerException $containerException) {
            self::assertInstanceOf(RuntimeException::class, $containerException->getPrevious());
        }

        $state->fail = false;
        $result = $container->get('named');
        self::assertNotSame($original, $result);
        self::assertSame($result, $container->get('named'));
        self::assertSame(2, $state->calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function distinctObjectCanBeReplacedAfterTheClassEntryHasBeenCached(): void
    {
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            stdClass::class => static fn(): stdClass => (object) [
                'name' => 'class',
            ],
            'named' => static fn(): stdClass => (object) [
                'name' => 'named',
            ],
        ], [
            stdClass::class => static fn(ContainerInterface $container, stdClass $service): string => $service->name,
        ]));

        self::assertSame('class', $container->get(stdClass::class));
        self::assertSame('named', $container->get('named'));
        self::assertSame('named', $container->get('named'));
    }
}
