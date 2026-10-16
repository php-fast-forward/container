<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Autowiring;

use FastForward\Container\AutowireContainer;
use FastForward\Container\Exception\AutowiringException;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

require $argv[1] ?? getcwd() . '/vendor/autoload.php';

/**
 * An intentionally unbound optional interface.
 */
interface OptionalContract {}

/**
 * A concrete dependency that can be constructed without registrations.
 */
final class Dependency {}

/**
 * An ordinary optional object graph.
 */
final readonly class Application
{
    /**
     * @param Dependency|null $dependency a constructible optional dependency
     * @param OptionalContract|null $optional an absent optional interface
     */
    public function __construct(
        public ?Dependency $dependency = null,
        public ?OptionalContract $optional = null
    ) {}
}

/**
 * A known class whose required scalar is not inferable.
 */
final readonly class RequiredScalar
{
    /**
     * @param string $value the required scalar
     */
    public function __construct(
        public string $value
    ) {}
}

/**
 * A dependency with a genuine execution failure.
 */
final class Broken
{
    /**
     * Always fail to model a broken optional integration.
     */
    public function __construct()
    {
        throw new RuntimeException('example constructor failure');
    }
}

/**
 * A default must not hide Broken's execution failure.
 */
final readonly class OptionalBroken
{
    /**
     * @param Broken|null $dependency the failing optional dependency
     */
    public function __construct(
        public ?Broken $dependency = null
    ) {}
}

/**
 * An optional circular graph still has to report its cycle.
 */
final readonly class Cycle
{
    /**
     * @param self|null $dependency the circular optional dependency
     */
    public function __construct(
        public ?self $dependency = null
    ) {}
}

$standalone = new AutowireContainer();
$application = $standalone->get(Application::class);
if (! $application->dependency instanceof Dependency
    || null !== $application->optional
    || $application !== $standalone->get(Application::class)
) {
    throw new RuntimeException('Standalone optional injection or caching failed.');
}

foreach ([[], [null]] as $delegateArguments) {
    if (! (new AutowireContainer(...$delegateArguments))->get(Dependency::class) instanceof Dependency) {
        throw new RuntimeException('Omitted or explicit-null delegate construction failed.');
    }
}

$registered = new Dependency();
$delegate = new ServiceProviderContainer(new ArrayServiceProvider([
    Dependency::class => static fn(): Dependency => $registered,
]));
$delegated = new AutowireContainer($delegate);
if ($delegated->get(Dependency::class) !== $registered
    || $delegated->get(Application::class)->dependency !== $registered
) {
    throw new RuntimeException('The delegate lost precedence for direct/nested optional lookups.');
}

if (! $standalone->has(RequiredScalar::class) || $standalone->has('missing.example')) {
    throw new RuntimeException('PSR-11 discovery reported the wrong known/unknown state.');
}

try {
    $standalone->get(RequiredScalar::class);
    throw new RuntimeException('A required scalar was silently supplied.');
} catch (AutowiringException) {
    // Known does not mean constructible.
}

try {
    $standalone->get('missing.example');
    throw new RuntimeException('A missing entry was silently supplied.');
} catch (NotFoundExceptionInterface) {
    // Unknown identifiers use the PSR-11 not-found contract.
}

try {
    $standalone->get(OptionalBroken::class);
    throw new RuntimeException('The optional default hid a constructor failure.');
} catch (ContainerException $containerException) {
    if ('example constructor failure' !== $containerException->getPrevious()?->getMessage()) {
        throw new RuntimeException(
            'The constructor failure lost its original cause.',
            $containerException->getCode(),
            previous: $containerException
        );
    }
}

try {
    $standalone->get(Cycle::class);
    throw new RuntimeException('The optional default hid a circular dependency.');
} catch (ContainerException $containerException) {
    if (! str_contains($containerException->getMessage(), 'Circular dependency:')) {
        throw new RuntimeException(
            'The circular dependency lost its diagnostic.',
            $containerException->getCode(),
            previous: $containerException
        );
    }
}

echo 'autowiring: ok', \PHP_EOL;
