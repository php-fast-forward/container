<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Composition;

use FastForward\Container\ContainerBuilder;
use FastForward\Container\Factory\AliasFactory;
use FastForward\Container\Factory\CallableFactory;
use FastForward\Container\Factory\InvokableFactory;
use FastForward\Container\Factory\MethodFactory;
use FastForward\Container\Factory\ServiceFactory;
use FastForward\Container\ServiceProvider\AggregateServiceProvider;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use RuntimeException;

use function FastForward\Container\container;

require $argv[1] ?? getcwd() . '/vendor/autoload.php';

require __DIR__ . '/Fixtures/Composition/LabelContract.php';
require __DIR__ . '/Fixtures/Composition/Label.php';

$base = new ArrayServiceProvider([
    'label' => static fn(): string => 'base',
    'trail' => static fn(): string => 'start',
], [
    'trail' => static fn($c, string $previous): string => $previous . ':base',
]);
$test = new ArrayServiceProvider([
    'label' => static fn(): string => 'test',
], [
    'trail' => static fn($c, string $previous): string => $previous . ':test',
]);

$first = container($base, $test);
$merged = container(new AggregateServiceProvider($base, $test));
if ('base' !== $first->get('label')
    || 'test' !== $merged->get('label')
    || 'start:base:test' !== $merged->get('trail')
    || 'start:base:test' !== $first->get('trail')
) {
    throw new RuntimeException('Factory precedence or cross-provider extension order failed.');
}

$factories = new ArrayServiceProvider([
    'text' => new ServiceFactory('configured'),
    'nothing' => new ServiceFactory(null),
    Label::class => new InvokableFactory(Label::class, 'text'),
    'alias' => new AliasFactory(Label::class),
    LabelContract::class => new AliasFactory(Label::class),
    'typed' => new CallableFactory(static fn(Label $label): Label => $label),
    'method' => new MethodFactory(Label::class, 'create', 'text'),
]);
$c = container($factories);
$label = $c->get(Label::class);
if (! $label instanceof Label
    || 'configured' !== $label->text
    || $c->get('alias') !== $label
    || $c->get(LabelContract::class) !== $label
    || $c->get('typed') !== $label
    || 'configured' !== $c->get('method')->text
    || ! $c->has('nothing')
    || null !== $c->get('nothing')
) {
    throw new RuntimeException('Factory arguments, aliases, typed callable or null entry failed.');
}

$builder = (new ContainerBuilder())->add($base);
$before = $builder->build();
$builder->add(new ArrayServiceProvider([
    'feature' => static fn(): bool => true,
]));
$after = $builder->build();
if ($before->has('feature') || true !== $after->get('feature')) {
    throw new RuntimeException('Builder changes affected an earlier composition.');
}

$external = new ServiceProviderContainer(new ArrayServiceProvider([
    Label::class => static fn(): Label => new Label('external'),
]));
if (container($external)->get(Label::class) !== container($external)->get(Label::class)) {
    throw new RuntimeException('A reused external container unexpectedly lost its cached instance.');
}

$broken = new ServiceProviderContainer(new ArrayServiceProvider([
    'recover' => static fn(): never => throw new RuntimeException('broken source'),
]));
$fallback = new ArrayServiceProvider([
    'recover' => static fn(): string => 'recovered',
]);
if ('recovered' !== container($broken, $fallback)->get('recover')) {
    throw new RuntimeException('A successful later source did not recover.');
}

echo 'composition: ok', \PHP_EOL;
