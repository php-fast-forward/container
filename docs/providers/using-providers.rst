Built-in Providers
==================

FastForward Container provides ready-to-use provider classes to help you register services
quickly without building your own provider implementation from scratch.

Overview
--------

===============================  ================================================
Type                             Responsibility
===============================  ================================================
``ArrayServiceProvider``         Stores factories and extensions in plain arrays
``AggregateServiceProvider``     Merges several providers into one provider object
``ServiceProviderContainer``     Exposes one provider as a PSR-11 container
===============================  ================================================

``ArrayServiceProvider``
------------------------

Use this provider when you want the smallest possible amount of ceremony:

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProviderContainer;

   $provider = new ArrayServiceProvider([
      'foo' => fn() => new FooService(),
      'bar' => fn() => new BarService(),
   ], [
      'foo' => function ($container, $previous) {
         $previous->setBar($container->get('bar'));
         return $previous;
      },
   ]);

   $container = new ServiceProviderContainer($provider);
   $foo = $container->get('foo');

This is the best default for application code, examples, tests, and small libraries.

You can also pass the provider directly to the helper:

.. code-block:: php

   use function FastForward\Container\container;

   $container = container($provider);
   $foo = $container->get('foo');

``AggregateServiceProvider``
----------------------------

Use this provider when you want to publish or pass around one merged provider:

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProvider\AggregateServiceProvider;
   use FastForward\Container\ServiceProviderContainer;

   $providerA = new ArrayServiceProvider([
      'foo' => fn() => new FooService(),
   ]);
   $providerB = new ArrayServiceProvider([
      'bar' => fn() => new BarService(),
   ]);

   $aggregate = new AggregateServiceProvider($providerA, $providerB);
   $container = new ServiceProviderContainer($aggregate);

   $foo = $container->get('foo'); // from providerA
   $bar = $container->get('bar'); // from providerB

Two details are easy to miss:

- Factories are merged in provider order, so later providers overwrite earlier keys when the same service ID exists in both.
- Extensions with the same key are composed in provider order instead of overwritten.

If you want first-match-wins semantics instead of merge semantics, do not aggregate the
providers first. Pass them separately to ``container($providerA, $providerB)``.

``ServiceProviderContainer``
----------------------------

``ServiceProviderContainer`` is the bridge between a service provider and PSR-11
container consumers:

.. code-block:: php

   use FastForward\Container\ServiceProviderContainer;

   $container = new ServiceProviderContainer($provider);

Important behavior:

- Resolved services are cached.
- A resolved service is also cached under its concrete class name when the original ID is different.
- Extensions may be keyed by the original service ID or by the concrete class name of the resolved service.
- Factories and extensions receive the wrapper container, which defaults to the ``ServiceProviderContainer`` instance itself.
