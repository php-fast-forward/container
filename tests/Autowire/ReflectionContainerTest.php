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

namespace FastForward\Container\Tests\Autowire;

use Countable;
use Error;
use IntlGregorianCalendar;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use stdClass;
use FastForward\Container\AggregateContainer;
use FastForward\Container\Autowire\ReflectionContainer;
use FastForward\Container\AutowireContainer;
use FastForward\Container\ContainerBuilder;
use FastForward\Container\Exception\AutowiringException;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(ReflectionContainer::class)]
#[UsesClass(AggregateContainer::class)]
#[UsesClass(AutowireContainer::class)]
#[UsesClass(ContainerBuilder::class)]
#[UsesClass(ArrayServiceProvider::class)]
#[UsesClass(ServiceProviderContainer::class)]
#[UsesClass(AutowiringException::class)]
#[UsesClass(ContainerException::class)]
#[UsesClass(NotFoundException::class)]
final class ReflectionContainerTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function registeredInterfaceOverridesOptionalDefault(): void
    {
        $dependency = new NativeDependency();
        $container = $this->container([
            NativeContract::class => static fn(): NativeDependency => $dependency,
        ]);

        self::assertSame($dependency, $container->get(OptionalInterface::class)->dependency);
    }

    /**
     * @return void
     */
    #[Test]
    public function autowiredClassOverridesNonNullDefaultAndIsShared(): void
    {
        $container = $this->container();
        $consumer = $container->get(ObjectDefault::class);

        self::assertSame($container->get(NativeDependency::class), $consumer->dependency);
        self::assertSame($consumer, $container->get(ObjectDefault::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function explicitConcreteEntryTakesPrecedenceOverAutowiring(): void
    {
        $dependency = new NativeDependency();
        $container = $this->container([
            NativeDependency::class => static fn(): NativeDependency => $dependency,
        ]);

        self::assertSame($dependency, $container->get(ObjectDefault::class)->dependency);
    }

    /**
     * @return void
     */
    #[Test]
    public function registeredNullOverridesNonNullDefault(): void
    {
        $container = $this->container([
            NativeDependency::class => static fn(): null => null,
        ]);

        self::assertNull($container->get(ObjectDefault::class)->dependency);
        self::assertNull($container->get(NativeDependency::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function missingInterfaceUsesDeclaredDefault(): void
    {
        self::assertNull($this->container()->get(OptionalInterface::class)->dependency);
    }

    /**
     * @return void
     */
    #[Test]
    public function unavailableNestedGraphUsesDeclaredDefault(): void
    {
        self::assertNull($this->container()->get(OptionalGraph::class)->dependency);
    }

    /**
     * @return void
     */
    #[Test]
    public function unavailableGraphRetainsItsNonNullDefault(): void
    {
        $service = $this->container()
            ->get(GraphObjectDefault::class);

        self::assertSame('default', $service->dependency->label);
    }

    /**
     * @return void
     */
    #[Test]
    public function defaultsAndEmptyVariadicsDoNotInferParameterNameBindings(): void
    {
        $container = $this->container([
            'label' => static fn(): string => 'wrong',
        ]);
        $service = $container->get(DefaultArguments::class);

        self::assertSame('default', $service->label);
        self::assertSame(42, $service->untyped);
        self::assertSame([], $service->dependencies);
    }

    /**
     * @return void
     */
    #[Test]
    public function unsupportedOptionalTypesUseTheirDefaults(): void
    {
        $service = $this->container()
            ->get(OptionalTypes::class);

        self::assertNull($service->union);
        self::assertNull($service->intersection);
        self::assertNull($service->missing);
    }

    /**
     * @param string $id
     * @param string $parameter
     *
     * @return void
     */
    #[Test]
    #[DataProvider('requiredArguments')]
    public function unavailableRequiredArgumentsHaveActionableErrors(string $id, string $parameter): void
    {
        $this->expectException(AutowiringException::class);
        $this->expectExceptionMessage('parameter $' . $parameter);

        $this->container()
            ->get($id);
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function requiredArguments(): iterable
    {
        yield 'scalar' => [RequiredScalar::class, 'label'];
        yield 'nullable without default' => [RequiredNullable::class, 'dependency'];
        yield 'union' => [RequiredUnion::class, 'dependency'];
        yield 'intersection' => [RequiredIntersection::class, 'dependency'];
        yield 'untyped' => [RequiredUntyped::class, 'dependency'];
    }

    /**
     * @return void
     */
    #[Test]
    public function requiredNullableArgumentAcceptsRegisteredNull(): void
    {
        $container = $this->container([
            NativeContract::class => static fn(): null => null,
        ]);

        self::assertNull($container->get(RequiredNullable::class)->dependency);
    }

    /**
     * @return void
     */
    #[Test]
    public function inheritedSelfTypeUsesConstructorDeclaringClass(): void
    {
        $dependency = new SelfBase();
        $container = $this->container([
            SelfBase::class => static fn(): SelfBase => $dependency,
        ]);

        self::assertSame($dependency, $container->get(SelfChild::class)->dependency);
    }

    /**
     * @return void
     */
    #[Test]
    public function parentTypeResolvesTheParentClass(): void
    {
        $container = $this->container();

        self::assertSame($container->get(NativeDependency::class), $container->get(ParentType::class)->dependency);
    }

    /**
     * @return void
     */
    #[Test]
    public function runtimeConstructorFailureDoesNotUseOptionalDefault(): void
    {
        $container = $this->container();

        try {
            $container->get(OptionalThrowing::class);
            self::fail('A constructor failure must escape optional fallback.');
        } catch (ContainerException $containerException) {
            self::assertInstanceOf(RuntimeException::class, $containerException->getPrevious());
            self::assertSame('constructor failed', $containerException->getPrevious()->getMessage());
        }
    }

    /**
     * @return void
     *
     * @throws RuntimeException
     */
    #[Test]
    public function runtimeFactoryFailureDoesNotUseOptionalDefault(): void
    {
        $container = $this->container([
            NativeContract::class => static fn() => throw new RuntimeException('factory failed'),
        ]);

        try {
            $container->get(OptionalInterface::class);
            self::fail('A factory failure must escape optional fallback.');
        } catch (ContainerException $containerException) {
            $previous = $containerException->getPrevious();

            self::assertInstanceOf(RuntimeException::class, $previous);
            self::assertSame('factory failed', $previous->getMessage());
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function unavailableDefaultConstantIsWrappedWithoutPoisoningRetries(): void
    {
        $container = $this->container();

        for ($attempt = 0; $attempt < 2; ++$attempt) {
            try {
                $container->get(UndefinedDefaultConstant::class);
                self::fail('Default evaluation errors must implement the container exception contract.');
            } catch (ContainerException $containerException) {
                $previous = $containerException->getPrevious();

                self::assertInstanceOf(Error::class, $previous);
                self::assertStringContainsString('UNAVAILABLE_AUTOWIRE_DEFAULT', $previous->getMessage());
            }
        }

        self::assertInstanceOf(NativeDependency::class, $container->get(NativeDependency::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function unavailableDefaultConstantDoesNotTriggerAnOptionalFallback(): void
    {
        $this->expectException(ContainerException::class);

        $this->container()
            ->get(OptionalUndefinedDefaultConstant::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function failureWhileConstructingADefaultValuePreservesItsCause(): void
    {
        try {
            $this->container()
                ->get(ThrowingDefaultValue::class);
            self::fail('A default object construction failure must be wrapped.');
        } catch (ContainerException $containerException) {
            $previous = $containerException->getPrevious();

            self::assertInstanceOf(RuntimeException::class, $previous);
            self::assertSame('constructor failed', $previous->getMessage());
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function wrongRegisteredTypeIsAConstructionFailure(): void
    {
        $container = $this->container([
            NativeContract::class => static fn(): stdClass => new stdClass(),
        ]);

        $this->expectException(ContainerException::class);

        $container->get(OptionalInterface::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function optionalCycleReportsPathInsteadOfUsingDefault(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(CycleA::class . ' -> ' . CycleB::class . ' -> ' . CycleA::class);

        $this->container()
            ->get(CycleA::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function failedResolutionDoesNotPoisonRetryOrIndependentServices(): void
    {
        RetryConstruction::$attempts = 0;
        $container = $this->container();

        try {
            $container->get(RetryConstruction::class);
            self::fail('The first construction must fail.');
        } catch (ContainerException) {
            self::assertInstanceOf(NativeDependency::class, $container->get(NativeDependency::class));
        }

        self::assertInstanceOf(RetryConstruction::class, $container->get(RetryConstruction::class));
        self::assertSame(2, RetryConstruction::$attempts);
    }

    /**
     * @return void
     */
    #[Test]
    public function providerFactoriesAndNativeDependenciesResolveAcrossSources(): void
    {
        $dependency = new NativeDependency();
        $container = (new ContainerBuilder())->add(
            new ArrayServiceProvider([
                'entry' => static fn(ContainerInterface $container) => $container->get(OptionalInterface::class),
            ]),
            new ArrayServiceProvider([
                NativeContract::class => static fn(): NativeDependency => $dependency,
            ]),
        )->build();

        self::assertSame($dependency, $container->get('entry')->dependency);
    }

    /**
     * @return void
     */
    #[Test]
    public function eachBuilderBuildHasAnIndependentAutowiringCache(): void
    {
        $builder = new ContainerBuilder();
        $first = $builder->build();
        $second = $builder->build();

        self::assertNotSame($first->get(NativeDependency::class), $second->get(NativeDependency::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function internalHasInspectsMetadataWithoutRunningConstructors(): void
    {
        RetryConstruction::$attempts = 0;
        $resolver = new ReflectionContainer(new AggregateContainer());

        self::assertTrue($resolver->has(RetryConstruction::class));
        self::assertSame(0, RetryConstruction::$attempts);
        self::assertFalse($resolver->has(NativeContract::class));
        self::assertFalse($resolver->has(PrivateConstructor::class));
        self::assertFalse($resolver->has(AbstractService::class));
        self::assertFalse($resolver->has('MissingNativeService'));
    }

    /**
     * @return void
     */
    #[Test]
    public function internalResolverCachesItsSuccessfulObjects(): void
    {
        $resolver = new ReflectionContainer(new AggregateContainer());

        self::assertSame($resolver->get(NativeDependency::class), $resolver->get(NativeDependency::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function internalResolverRejectsUnknownEntries(): void
    {
        $this->expectException(NotFoundException::class);

        (new ReflectionContainer(new AggregateContainer()))->get('missing');
    }

    /**
     * @param bool $wrapped
     * @param string $method
     *
     * @return void
     */
    #[Test]
    #[DataProvider('classProbes')]
    public function autoloaderFailuresPreserveTheirCauseAcrossRetries(bool $wrapped, string $method): void
    {
        $id = __NAMESPACE__ . '\\BrokenAutoloadService';
        $failure = new RuntimeException('autoload failed');
        $loader = static function (string $class) use ($id, $failure): void {
            if ($id === $class) {
                throw $failure;
            }
        };
        $container = $wrapped ? $this->container() : new ReflectionContainer(new AggregateContainer());
        spl_autoload_register($loader);

        try {
            for ($attempt = 0; $attempt < 2; ++$attempt) {
                try {
                    $container->{$method}($id);
                    self::fail('Class probing must report a container exception.');
                } catch (ContainerException $exception) {
                    self::assertSame($failure, $exception->getPrevious());
                }
            }

            self::assertInstanceOf(NativeDependency::class, $container->get(NativeDependency::class));
        } finally {
            spl_autoload_unregister($loader);
        }
    }

    /**
     * @return iterable<string, array{bool, string}>
     */
    public static function classProbes(): iterable
    {
        yield 'internal has' => [false, 'has'];
        yield 'internal get' => [false, 'get'];
        yield 'composed get' => [true, 'get'];
    }

    /**
     * @return void
     */
    #[Test]
    #[RequiresPhpExtension('intl')]
    public function internalOptionalArgumentsWithoutReflectedDefaultsAreOmitted(): void
    {
        $constructor = (new ReflectionClass(IntlGregorianCalendar::class))->getConstructor();

        self::assertInstanceOf(ReflectionMethod::class, $constructor);

        $parameter = $constructor->getParameters()[0];

        self::assertTrue($parameter->isOptional());
        self::assertFalse($parameter->isDefaultValueAvailable());
        self::assertInstanceOf(IntlGregorianCalendar::class, $this->container()->get(IntlGregorianCalendar::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function constructorReferenceArgumentsHaveIndependentStorage(): void
    {
        $container = $this->container();
        $consumer = $container->get(ReferenceArguments::class);

        self::assertSame($container->get(NativeDependency::class), $consumer->dependency);
        self::assertSame('default:updated', $consumer->label);
        self::assertSame('other:updated', $consumer->other);
        self::assertNull($consumer->missing);
    }

    /**
     * @param array<string, callable> $factories
     *
     * @return AutowireContainer
     */
    private function container(array $factories = []): AutowireContainer
    {
        return new AutowireContainer(new ServiceProviderContainer(new ArrayServiceProvider($factories)));
    }
}

interface NativeContract {}

class NativeDependency implements NativeContract {}

final readonly class OptionalInterface
{
    /**
     * @param NativeContract|null $dependency
     */
    public function __construct(
        public ?NativeContract $dependency = null
    ) {}
}

final readonly class ObjectDefault
{
    /**
     * @param NativeDependency|null $dependency
     */
    public function __construct(
        public ?NativeDependency $dependency = new NativeDependency()
    ) {}
}

final readonly class RequiredScalar
{
    /**
     * @param string $label
     */
    public function __construct(
        public string $label
    ) {}
}

final readonly class OptionalGraph
{
    /**
     * @param RequiredScalar|null $dependency
     */
    public function __construct(
        public ?RequiredScalar $dependency = null
    ) {}
}

final readonly class GraphObjectDefault
{
    /**
     * @param RequiredScalar $dependency
     */
    public function __construct(
        public RequiredScalar $dependency = new RequiredScalar('default')
    ) {}
}

final readonly class DefaultArguments
{
    public array $dependencies;

    /**
     * @param string $label
     * @param mixed $untyped
     * @param NativeDependency $dependencies
     */
    public function __construct(
        public string $label = 'default',
        public mixed $untyped = 42,
        NativeDependency ...$dependencies
    ) {
        $this->dependencies = $dependencies;
    }
}

final readonly class OptionalTypes
{
    /**
     * @param NativeDependency|stdClass|null $union
     * @param (NativeContract&Countable)|null $intersection
     * @param UnknownNativeType|null $missing
     */
    public function __construct(
        public NativeDependency|stdClass|null $union = null,
        public (NativeContract&Countable)|null $intersection = null,
        public ?UnknownNativeType $missing = null,
    ) {}
}

final readonly class RequiredNullable
{
    /**
     * @param NativeContract|null $dependency
     */
    public function __construct(
        public ?NativeContract $dependency
    ) {}
}

final readonly class RequiredUnion
{
    /**
     * @param NativeDependency|stdClass $dependency
     */
    public function __construct(
        public NativeDependency|stdClass $dependency
    ) {}
}

final readonly class RequiredIntersection
{
    /**
     * @param NativeContract&Countable $dependency
     */
    public function __construct(
        public NativeContract&Countable $dependency
    ) {}
}

final class RequiredUntyped
{
    /**
     * @param mixed $dependency
     */
    public function __construct(
        public $dependency
    ) {}
}

class SelfBase
{
    /**
     * @param self|null $dependency
     */
    public function __construct(
        public ?self $dependency = null
    ) {}
}

final class SelfChild extends SelfBase {}

final class ParentType extends NativeDependency
{
    /**
     * @param parent $dependency
     */
    public function __construct(
        public parent $dependency
    ) {}
}

final class ThrowingConstruction
{
    /**
     * @throws RuntimeException
     */
    public function __construct()
    {
        throw new RuntimeException('constructor failed');
    }
}

final readonly class OptionalThrowing
{
    /**
     * @param ThrowingConstruction|null $dependency
     */
    public function __construct(
        public ?ThrowingConstruction $dependency = null
    ) {}
}

final readonly class UndefinedDefaultConstant
{
    /**
     * @param string $mode
     */
    public function __construct(
        public string $mode = UNAVAILABLE_AUTOWIRE_DEFAULT
    ) {}
}

final readonly class OptionalUndefinedDefaultConstant
{
    /**
     * @param UndefinedDefaultConstant|null $dependency
     */
    public function __construct(
        public ?UndefinedDefaultConstant $dependency = null
    ) {}
}

final readonly class ThrowingDefaultValue
{
    /**
     * @param mixed $dependency
     */
    public function __construct(
        public mixed $dependency = new ThrowingConstruction()
    ) {}
}

final readonly class CycleA
{
    /**
     * @param CycleB|null $dependency
     */
    public function __construct(
        public ?CycleB $dependency = null
    ) {}
}

final readonly class CycleB
{
    /**
     * @param CycleA $dependency
     */
    public function __construct(
        public CycleA $dependency
    ) {}
}

final class RetryConstruction
{
    public static int $attempts = 0;

    /**
     * @throws RuntimeException
     */
    public function __construct()
    {
        if (1 === ++self::$attempts) {
            throw new RuntimeException('retry');
        }
    }
}

final class PrivateConstructor
{
    private function __construct() {}
}

abstract class AbstractService {}

final class ReferenceArguments
{
    /**
     * @param NativeDependency $dependency
     * @param string $label
     * @param NativeContract|null $missing
     * @param string $other
     */
    public function __construct(
        public NativeDependency &$dependency,
        public string &$label = 'default',
        public ?NativeContract &$missing = null,
        public string &$other = 'other',
    ) {
        $label .= ':updated';
        $other .= ':updated';
    }
}
