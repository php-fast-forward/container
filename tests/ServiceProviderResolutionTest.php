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

use ArrayObject;
use RuntimeException;
use stdClass;
use TypeError;
use FastForward\Container\AggregateContainer;
use FastForward\Container\Autowire\ReflectionContainer;
use FastForward\Container\AutowireContainer;
use FastForward\Container\ContainerBuilder;
use FastForward\Container\ContainerFactory;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\Factory\ServiceFactory;
use FastForward\Container\ServiceProvider\AggregateServiceProvider;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\Extension\ServiceExtension;
use FastForward\Container\ServiceProviderContainer;
use Interop\Container\ServiceProviderInterface;
use FastForward\Container\ServiceProvider\FirstFactoryServiceProvider;
use FastForward\Container\ProviderSourceContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesFunction;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function FastForward\Container\container;

/**
 * @internal
 */
#[CoversClass(ServiceProviderContainer::class)]
#[CoversClass(ServiceExtension::class)]
#[UsesClass(ArrayServiceProvider::class)]
#[UsesClass(AggregateServiceProvider::class)]
#[UsesClass(AggregateContainer::class)]
#[UsesClass(AutowireContainer::class)]
#[UsesClass(ReflectionContainer::class)]
#[UsesClass(ContainerFactory::class)]
#[UsesClass(ContainerBuilder::class)]
#[UsesClass(ServiceFactory::class)]
#[UsesClass(ContainerException::class)]
#[UsesClass(NotFoundException::class)]
#[UsesFunction('FastForward\\Container\\container')]
#[UsesClass(FirstFactoryServiceProvider::class)]
#[UsesClass(ProviderSourceContainer::class)]
final class ServiceProviderResolutionTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function hasFindsExtensionOnlyEntryWithoutExecutingIt(): void
    {
        $calls = 0;
        $container = new ServiceProviderContainer(new ArrayServiceProvider(extensions: [
            'optional' => static function (ContainerInterface $container, mixed $previous) use (&$calls): mixed {
                ++$calls;

                return $previous;
            },
        ]));

        self::assertTrue($container->has('optional'));
        self::assertSame(0, $calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function nonCallableFactoryDoesNotMakeAnEntryDiscoverable(): void
    {
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'invalid' => 'missing-factory',
        ]));

        self::assertFalse($container->has('invalid'));

        $this->expectException(NotFoundException::class);
        $container->get('invalid');
    }

    /**
     * @return void
     */
    #[Test]
    public function callableExtensionCanProvideEntryWithNonCallableFactory(): void
    {
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'optional' => 'missing-factory',
        ], [
            'optional' => static fn(ContainerInterface $container, ?string $previous): string => $previous ?? 'default',
        ]));

        self::assertTrue($container->has('optional'));
        self::assertSame('default', $container->get('optional'));
    }

    /**
     * @return void
     */
    #[Test]
    public function extensionCreatesSharedEntryFromNull(): void
    {
        $calls = 0;
        $container = new ServiceProviderContainer(new ArrayServiceProvider(extensions: [
            'optional' => static function (ContainerInterface $container, ?stdClass $previous) use (&$calls): stdClass {
                self::assertNull($previous);
                ++$calls;

                return new stdClass();
            },
        ]));

        $service = $container->get('optional');

        self::assertInstanceOf(stdClass::class, $service);
        self::assertSame($service, $container->get('optional'));
        self::assertSame(1, $calls);
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    #[Test]
    #[DataProvider('entryValues')]
    public function factoryValuesAreSharedIncludingNull(mixed $value): void
    {
        $calls = 0;
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'value' => static function () use (&$calls, $value): mixed {
                ++$calls;

                return $value;
            },
        ]));

        self::assertSame($value, $container->get('value'));
        self::assertTrue($container->has('value'));
        self::assertSame($value, $container->get('value'));
        self::assertSame(1, $calls);
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    #[Test]
    #[DataProvider('entryValues')]
    public function extensionValuesAreSharedIncludingNull(mixed $value): void
    {
        $calls = 0;
        $container = new ServiceProviderContainer(new ArrayServiceProvider(extensions: [
            'value' => static function (ContainerInterface $container, mixed $previous) use (&$calls, $value): mixed {
                self::assertNull($previous);
                ++$calls;

                return $value;
            },
        ]));

        self::assertSame($value, $container->get('value'));
        self::assertTrue($container->has('value'));
        self::assertSame($value, $container->get('value'));
        self::assertSame(1, $calls);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function entryValues(): array
    {
        return [
            'null' => [null],
            'false' => [false],
            'zero' => [0],
            'float' => [1.25],
            'empty string' => [''],
            'string' => ['configured'],
            'array' => [[
                'enabled' => true,
            ]],
        ];
    }

    /**
     * @return void
     */
    #[Test]
    public function extensionReplacesFactoryResultAndCachesFinalClass(): void
    {
        $original = new stdClass();
        $replacement = new ArrayObject([
            'replaced' => true,
        ]);
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'service' => static fn(): stdClass => $original,
        ], [
            'service' => static function (ContainerInterface $container, stdClass $previous) use (
                $original,
                $replacement
            ): ArrayObject {
                self::assertSame($original, $previous);

                return $replacement;
            },
        ]));

        self::assertSame($replacement, $container->get('service'));
        self::assertSame($replacement, $container->get('service'));
        self::assertSame($replacement, $container->get(ArrayObject::class));
        self::assertFalse($container->has(stdClass::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function inferredClassAliasDoesNotOverrideDefinedFactory(): void
    {
        $aliased = new stdClass();
        $defined = new stdClass();
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'service' => static fn(): stdClass => $aliased,
            stdClass::class => static fn(): stdClass => $defined,
        ]));

        self::assertSame($aliased, $container->get('service'));
        self::assertSame($defined, $container->get(stdClass::class));
        self::assertSame($aliased, $container->get('service'));
        self::assertSame($defined, $container->get(stdClass::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function inferredClassAliasDoesNotOverrideDefinedExtension(): void
    {
        $aliased = new stdClass();
        $defined = new stdClass();
        $calls = 0;
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'service' => static fn(): stdClass => $aliased,
        ], [
            stdClass::class => static function (ContainerInterface $container, ?stdClass $previous) use (
                &$calls,
                $defined
            ): stdClass {
                ++$calls;

                return $previous ?? $defined;
            },
        ]));

        self::assertSame($aliased, $container->get('service'));
        self::assertSame($defined, $container->get(stdClass::class));
        self::assertSame($defined, $container->get(stdClass::class));
        self::assertSame(2, $calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function extensionCanReplaceObjectWithNull(): void
    {
        $calls = 0;
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'service' => static fn(): stdClass => new stdClass(),
        ], [
            'service' => static function (ContainerInterface $container, stdClass $previous) use (&$calls): mixed {
                ++$calls;

                return null;
            },
        ]));

        self::assertNull($container->get('service'));
        self::assertNull($container->get('service'));
        self::assertTrue($container->has('service'));
        self::assertFalse($container->has(stdClass::class));
        self::assertSame(1, $calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function classExtensionReceivesIdExtensionResult(): void
    {
        $replacement = new ArrayObject();
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            'service' => static fn(): stdClass => new stdClass(),
        ], [
            'service' => static fn(): ArrayObject => $replacement,
            ArrayObject::class => static function (ContainerInterface $container, ArrayObject $previous): string {
                $previous['extended'] = true;

                return 'final value';
            },
        ]));

        self::assertSame('final value', $container->get('service'));
        self::assertTrue($replacement['extended']);
        self::assertTrue($container->has(ArrayObject::class));

        $this->expectException(ContainerException::class);
        $container->get(ArrayObject::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function mergedProvidersApplyExtensionsAfterFactoriesInRegistrationOrder(): void
    {
        $events = [];
        $first = new ArrayServiceProvider(extensions: [
            'value' => static function (ContainerInterface $container, string $previous) use (&$events): string {
                $events[] = 'first';

                return $previous . ':first';
            },
        ]);
        $second = new ArrayServiceProvider([
            'value' => static function () use (&$events): string {
                $events[] = 'factory';

                return 'base';
            },
        ], [
            'value' => static function (ContainerInterface $container, string $previous) use (&$events): string {
                $events[] = 'second';

                return $previous . ':second';
            },
        ]);
        $container = container(new AggregateServiceProvider($first, $second));

        self::assertSame('base:first:second', $container->get('value'));
        self::assertSame('base:first:second', $container->get('value'));
        self::assertSame(['factory', 'first', 'second'], $events);
    }

    /**
     * @return void
     */
    #[Test]
    public function composedExtensionOnlyChainCanStartAtNull(): void
    {
        $first = new ArrayServiceProvider(extensions: [
            'value' => static fn(ContainerInterface $container, ?string $previous): string => $previous ?? 'default',
        ]);
        $second = new ArrayServiceProvider(extensions: [
            'value' => static fn(ContainerInterface $container, string $previous): string => $previous . ':extended',
        ]);
        $container = container(new AggregateServiceProvider($first, $second));

        self::assertTrue($container->has('value'));
        self::assertSame('default:extended', $container->get('value'));
    }

    /**
     * @return void
     */
    #[Test]
    public function mergedVoidExtensionPreservesPreviousValue(): void
    {
        $first = new ArrayServiceProvider([
            'service' => static fn(): stdClass => new stdClass(),
        ], [
            'service' => static function (ContainerInterface $container, stdClass $previous): void {
                $previous->first = true;
            },
        ]);
        $second = new ArrayServiceProvider(extensions: [
            'service' => static function (ContainerInterface $container, stdClass $previous): stdClass {
                $previous->second = true;

                return $previous;
            },
        ]);
        $container = container(new AggregateServiceProvider($first, $second));
        $service = $container->get('service');

        self::assertTrue($service->first);
        self::assertTrue($service->second);
        self::assertSame($service, $container->get('service'));
    }

    /**
     * @return void
     */
    #[Test]
    public function earlierExtensionUsesTheLaterFactoryBeforeExtending(): void
    {
        $first = new ArrayServiceProvider(extensions: [
            'value' => static fn(ContainerInterface $container, ?string $previous): string => $previous ?? 'first',
        ]);
        $second = new ArrayServiceProvider([
            'value' => static fn(): string => 'second',
        ]);

        self::assertSame('second', container($first, $second)->get('value'));
    }

    /**
     * @return void
     */
    #[Test]
    public function helperKeepsNullExtensionResultPresentAndShared(): void
    {
        $calls = 0;
        $container = container(new ArrayServiceProvider(extensions: [
            'optional' => static function (ContainerInterface $container, mixed $previous) use (&$calls): mixed {
                ++$calls;

                return null;
            },
        ]));

        self::assertTrue($container->has('optional'));
        self::assertNull($container->get('optional'));
        self::assertNull($container->get('optional'));
        self::assertSame(1, $calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function extensionReceivesWrapperContainer(): void
    {
        $dependency = new stdClass();
        $wrapper = new ServiceProviderContainer(new ArrayServiceProvider([
            'dependency' => static fn(): stdClass => $dependency,
        ]));
        $container = new ServiceProviderContainer(new ArrayServiceProvider(extensions: [
            'service' => static function (ContainerInterface $container, mixed $previous) use ($wrapper): mixed {
                self::assertSame($wrapper, $container);

                return $container->get('dependency');
            },
        ]), $wrapper);

        self::assertSame($dependency, $container->get('service'));
    }

    /**
     * @return void
     */
    #[Test]
    public function incompatibleExtensionFailsWithPreservedCause(): void
    {
        $container = new ServiceProviderContainer(new ArrayServiceProvider(extensions: [
            'service' => static fn(ContainerInterface $container, stdClass $previous): stdClass => $previous,
        ]));

        try {
            $container->get('service');
            self::fail('An extension requiring an object must reject a missing previous value.');
        } catch (ContainerException $containerException) {
            self::assertInstanceOf(TypeError::class, $containerException->getPrevious());
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function extensionFailureIsNotCached(): void
    {
        $calls = 0;
        $failure = new RuntimeException('Extension failed.');
        $container = new ServiceProviderContainer(new ArrayServiceProvider(extensions: [
            'service' => static function (ContainerInterface $container, mixed $previous) use (
                &$calls,
                $failure
            ): stdClass {
                if (1 === ++$calls) {
                    throw $failure;
                }

                return new stdClass();
            },
        ]));

        try {
            $container->get('service');
            self::fail('The first resolution must expose the extension failure.');
        } catch (ContainerException $containerException) {
            self::assertSame($failure, $containerException->getPrevious());
        }

        $service = $container->get('service');

        self::assertSame($service, $container->get('service'));
        self::assertSame(2, $calls);
    }

    /**
     * @param string $source
     *
     * @return void
     */
    #[Test]
    #[DataProvider('failureSources')]
    public function resolutionPreservesStringCodedFailure(string $source): void
    {
        $failure = new class extends RuntimeException {
            public function __construct()
            {
                parent::__construct('SQLSTATE HY000');
                $this->code = 'HY000';
            }
        };
        $callback = static fn(): never => throw $failure;
        $container = new ServiceProviderContainer(new ArrayServiceProvider(
            factories: 'factory' === $source ? [
                'service' => $callback,
            ] : [],
            extensions: 'extension' === $source ? [
                'service' => $callback,
            ] : [],
        ));

        try {
            $container->get('service');
            self::fail('Resolution must expose the original string-coded exception.');
        } catch (ContainerException $containerException) {
            self::assertSame(0, $containerException->getCode());
            self::assertSame($failure, $containerException->getPrevious());
            self::assertSame('HY000', $containerException->getPrevious()->getCode());
        }
    }

    /**
     * @param string $source
     * @param string $operation
     *
     * @return void
     */
    #[Test]
    #[DataProvider('metadataFailureOperations')]
    public function metadataFailurePreservesCause(string $source, string $operation): void
    {
        $failure = new RuntimeException('Provider metadata failed.', 501);
        $provider = new readonly class ($source, $failure) implements ServiceProviderInterface {
            /**
             * @param string $source
             * @param RuntimeException $failure
             */
            public function __construct(
                private string $source,
                private RuntimeException $failure
            ) {}

            /**
             * @return array
             */
            public function getFactories(): array
            {
                if ('factory' === $this->source) {
                    throw $this->failure;
                }

                return [];
            }

            /**
             * @return array
             */
            public function getExtensions(): array
            {
                throw $this->failure;
            }
        };
        $container = new ServiceProviderContainer($provider);

        try {
            $container->{$operation}('service');
            self::fail('Entry discovery and resolution must preserve provider metadata failures.');
        } catch (ContainerException $containerException) {
            self::assertSame(501, $containerException->getCode());
            self::assertSame($failure, $containerException->getPrevious());
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function failureSources(): array
    {
        return [
            'factory' => ['factory'],
            'extension' => ['extension'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function metadataFailureOperations(): array
    {
        return [
            'get factory metadata' => ['factory', 'get'],
            'get extension metadata' => ['extension', 'get'],
            'has factory metadata' => ['factory', 'has'],
            'has extension metadata' => ['extension', 'has'],
        ];
    }
}
