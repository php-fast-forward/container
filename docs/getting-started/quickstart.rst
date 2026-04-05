Quickstart
==========

This quickstart shows the shortest path from installation to a useful container.

Goal
----

We will register one configured dependency explicitly and let the container resolve the
rest of the object graph:

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use Psr\Container\ContainerInterface;
   use function FastForward\Container\container;

   final readonly class ApplicationName
   {
       public function __construct(
           public string $value,
       ) {}
   }

   final readonly class Greeter
   {
       public function __construct(
           private string $applicationName,
       ) {}

       public function greet(string $name): string
       {
           return sprintf('Hello %s, welcome to %s.', $name, $this->applicationName);
       }
   }

   $provider = new ArrayServiceProvider([
       ApplicationName::class => static fn(): ApplicationName => new ApplicationName('FastForward Container'),
       Greeter::class => static fn(ContainerInterface $container): Greeter => new Greeter(
           $container->get(ApplicationName::class)->value,
       ),
   ]);

   $container = container($provider);
   $greeter = $container->get(Greeter::class);

   echo $greeter->greet('team');
   // Hello team, welcome to FastForward Container.

What just happened?
-------------------

1. ``ArrayServiceProvider`` defined how two services should be created.
2. ``container($provider)`` wrapped the provider in a PSR-11 container and then enabled autowiring on top of it.
3. ``Greeter`` resolved ``ApplicationName`` through the container during construction.
4. Later calls to ``$container->get(Greeter::class)`` return the same cached instance.

A second quick win: autowiring
------------------------------

You do not have to register every class manually. If the container already knows how to
build the configured dependencies, it can autowire the rest:

.. code-block:: php

   final readonly class Clock
   {
       public function __construct(
           private string $timezone,
       ) {}
   }

   final readonly class OrderRepository
   {
       public function __construct(
           private Clock $clock,
       ) {}
   }

   $container = container(new ArrayServiceProvider([
       Clock::class => static fn(): Clock => new Clock('America/Sao_Paulo'),
   ]));

   $repository = $container->get(OrderRepository::class);

In this example, ``Clock`` is registered explicitly because it needs a scalar value.
``OrderRepository`` can be autowired because its dependency is already known by type.

What to read next
-----------------

- Read :doc:`basic-usage` to mix providers, configuration, and existing PSR-11 containers.
- Read :doc:`../providers/index` to choose the right provider and factory style.
- Read :doc:`../examples/index` when you want real-world composition patterns.
