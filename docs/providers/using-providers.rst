Built-in Providers
==================


FastForward Container provides ready-to-use provider classes to help you register services quickly:

- **ArrayServiceProvider**: Register factories and extensions using plain arrays.
- **AggregateServiceProvider**: Combine multiple providers into one.
- **ServiceProviderContainer**: Wraps a provider as a PSR-11 container.

Examples
--------

**ArrayServiceProvider**
~~~~~~~~~~~~~~~~~~~~~~~
Register factories and extensions using arrays:

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

Or using the ``container()`` helper:

.. code-block:: php

   use FastForward\Container\container;
   $container = container($provider);
   $foo = $container->get('foo');

**AggregateServiceProvider**
~~~~~~~~~~~~~~~~~~~~~~~~~~~
Combine multiple providers into one:

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

Or using the ``container()`` helper:

.. code-block:: php

   $container = container($providerA, $providerB);
   $foo = $container->get('foo');
   $bar = $container->get('bar');

**ServiceProviderContainer**
~~~~~~~~~~~~~~~~~~~~~~~~~~~
Wraps any ServiceProviderInterface as a PSR-11 container:

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProviderContainer;

   $provider = new ArrayServiceProvider([
      'foo' => fn() => new FooService(),
   ]);
   $container = new ServiceProviderContainer($provider);
   $foo = $container->get('foo');
