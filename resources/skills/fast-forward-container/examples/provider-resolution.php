<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Composition;

use ArrayObject;
use Fiber;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Factory\AliasFactory;
use FastForward\Container\Factory\MethodFactory;
use FastForward\Container\ServiceProvider\AggregateServiceProvider;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

require $argv[1] ?? getcwd() . '/vendor/autoload.php';

require __DIR__ . '/Fixtures/Composition/LabelContract.php';
require __DIR__ . '/Fixtures/Composition/Label.php';
require __DIR__ . '/Fixtures/Composition/LabelFactory.php';

// API contract: fast-forward/container ^1.10.
$numeric = new ServiceProviderContainer(new AggregateServiceProvider(
    new ArrayServiceProvider([
        '42' => static fn(): string => 'base',
        '042' => static fn(): string => 'leading-zero',
    ]),
    new ArrayServiceProvider([
        '42' => static fn(): string => 'override',
    ])
));
if ('override' !== $numeric->get('42') || 'leading-zero' !== $numeric->get('042')) {
    throw new RuntimeException('Numeric identifiers or later-provider precedence changed.');
}

$failure = new RuntimeException('configured target failed');
$methods = new ServiceProviderContainer(new ArrayServiceProvider([
    'label.factory' => static fn(): LabelFactory => new LabelFactory('configured:'),
    'label' => new MethodFactory('label.factory', 'create', 'value'),
    'broken.factory' => static fn(): never => throw $failure,
    'broken' => new MethodFactory('broken.factory', 'create', 'value'),
]));
if ('configured:value' !== $methods->get('label')->text) {
    throw new RuntimeException('The instance-method target lost its service configuration.');
}

try {
    $methods->get('broken');
    throw new RuntimeException('A registered method target failure was hidden.');
} catch (ContainerException $containerException) {
    $cause = $containerException;
    while ($cause->getPrevious() instanceof Throwable) {
        $cause = $cause->getPrevious();
    }

    if ($cause !== $failure) {
        throw new RuntimeException(
            'The target failure lost its original cause.',
            $containerException->getCode(),
            previous: $containerException
        );
    }
}

$original = new Label('first');
$other = new Label('second');
$extensionCalls = 0;
$aliasInput = null;
$extensions = new ServiceProviderContainer(new ArrayServiceProvider([
    'first' => static fn(): Label => $original,
    'second' => static fn(): Label => $other,
    'alias' => static fn(): Label => $original,
], [
    Label::class => static function ($c, Label $previous) use (&$extensionCalls): Label {
        ++$extensionCalls;

        return new Label($previous->text . ':extended');
    },
    'alias' => static function ($c, Label $previous) use (&$aliasInput): void {
        $aliasInput = $previous;
    },
]));
$first = $extensions->get('first');
$second = $extensions->get('second');
$alias = $extensions->get('alias');
if ('first:extended' !== $first->text
    || 'second:extended' !== $second->text
    || $alias !== $first
    || $aliasInput !== $first
    || 2 !== $extensionCalls
) {
    throw new RuntimeException('Distinct instances or alias replacement reuse failed.');
}

$cycleState = new ArrayObject([
    'enabled' => true,
]);
$cycles = new ServiceProviderContainer(new ArrayServiceProvider([
    'entry' => static fn(ContainerInterface $c): Label => $cycleState['enabled'] ? $c->get('alias') : new Label(
        'retry'
    ),
    'alias' => new AliasFactory('entry'),
]));
try {
    $cycles->get('entry');
    throw new RuntimeException('A provider alias cycle was not rejected.');
} catch (ContainerException $containerException) {
    $cause = $containerException;
    while ($cause->getPrevious() instanceof Throwable) {
        $cause = $cause->getPrevious();
    }

    if (! str_contains($cause->getMessage(), 'entry -> alias -> entry')) {
        throw new RuntimeException(
            'The provider cycle lost its path.',
            $containerException->getCode(),
            previous: $containerException
        );
    }
}

$cycleState['enabled'] = false;
if ('retry' !== $cycles->get('entry')->text) {
    throw new RuntimeException('The failed provider path prevented a retry.');
}

$concurrent = new ServiceProviderContainer(new ArrayServiceProvider([
    'entry' => static function (): Label {
        Fiber::suspend();

        return new Label('fiber');
    },
]));
$left = new Fiber(static fn(): mixed => $concurrent->get('entry'));
$right = new Fiber(static fn(): mixed => $concurrent->get('entry'));
$left->start();
$right->start();
$left->resume();
$right->resume();
if ('fiber' !== $left->getReturn()->text || 'fiber' !== $right->getReturn()->text) {
    throw new RuntimeException('Independent Fiber paths were treated as a provider cycle.');
}

echo 'provider-resolution: ok', \PHP_EOL;
