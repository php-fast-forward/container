Built-in Providers
==================

FastForward Container provides ready-to-use provider classes to help you register services quickly:

- **ArrayServiceProvider**: Register factories and extensions using plain arrays.
- **AggregateServiceProvider**: Combine multiple providers into one.
- **ServiceProviderContainer**: Wraps a provider as a PSR-11 container.

Basic Example
-------------

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProviderContainer;

   $provider = new ArrayServiceProvider([
       'foo' => fn() => new FooService(),
       'bar' => fn() => new BarService(),
   ]);

   $container = new ServiceProviderContainer($provider);
   $foo = $container->get('foo');

You can also use the ``container()`` helper function:

.. code-block:: php

   use FastForward\Container\container;

   $container = container($provider);
   $foo = $container->get('foo');
