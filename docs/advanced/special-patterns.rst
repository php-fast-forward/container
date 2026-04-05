Special Patterns and Advanced Behaviors
======================================

This section highlights advanced usage patterns, special behaviors, and edge cases
supported by FastForward Container.

Cached service resolution
-------------------------

All built-in containers cache resolved services by default. This means a service is
normally instantiated only once per container instance.

.. code-block:: php

   $service1 = $container->get('foo');
   $service2 = $container->get('foo');

   // $service1 === $service2

If you need fresh objects repeatedly, register a factory object as the service and call
that factory yourself:

.. code-block:: php

   use FastForward\Container\Factory\InvokableFactory;
   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use function FastForward\Container\container;

   $provider = new ArrayServiceProvider([
       'random.factory' => static fn(): InvokableFactory => new InvokableFactory(RandomObject::class),
   ]);

   $container = container($provider);
   $factory = $container->get('random.factory');
   $obj1 = $factory($container);
   $obj2 = $factory($container);

Provider extensions by ID or class
----------------------------------

Extensions allow you to decorate or modify services after creation. In
``ServiceProviderContainer``, an extension can be registered by:

- the original service ID
- the concrete class name of the resolved service

.. code-block:: php

   $provider = new ArrayServiceProvider([
       'logger' => fn() => new Logger('app'),
   ], [
       'logger' => function ($container, $logger) {
           // Decorate logger
           $logger->pushProcessor(new MyProcessor());
           return $logger;
       },
   ]);

   $container = container($provider);
   $logger = $container->get('logger');

If both an ID-based extension and a class-based extension exist, both are applied.

Provider merge semantics
------------------------

There are two distinct composition models:

- ``container($providerA, $providerB)`` keeps providers separated and resolves from the first one that matches.
- ``AggregateServiceProvider($providerA, $providerB)`` merges factories first, so later factory keys overwrite earlier ones.

Use the first model for overrides and fallbacks. Use the second model when you want to
ship one combined provider object.

Aggregate container aliases and self-resolution
-----------------------------------------------

``AggregateContainer`` registers itself under several IDs so factories and extensions can
fetch the container itself when needed:

.. code-block:: php

   $container = container($provider);
   $self = $container->get('container'); // Returns the container instance
   $self2 = $container->get(FastForward\Container\ContainerInterface::class); // Also returns the container instance
   $self3 = $container->get(FastForward\Container\AggregateContainer::class); // Also returns the container instance
   $self4 = $container->get(Psr\Container\ContainerInterface::class); // Also returns the container instance

Using ``append()`` and ``prepend()``
-----------------------------------

``AggregateContainer`` can be modified after construction:

.. code-block:: php

   $aggregate = new AggregateContainer($defaults);
   $aggregate->prepend($tests);
   $aggregate->append($fallbacks);

``prepend()`` is especially useful when you want a later override to win without rebuilding
the whole aggregate from scratch.

Fallback and recovery behavior
------------------------------

When ``AggregateContainer`` checks its child containers, it keeps going when a child says
it has the entry but then throws a PSR not-found or container exception. This allows later
containers to recover from partial or inconsistent registrations.

Wrapper container for delegation
--------------------------------

``ServiceProviderContainer`` accepts an optional wrapper container. Factories and extensions
receive that wrapper instead of the internal provider container.

.. code-block:: php

   $provider = new ArrayServiceProvider([
       'foo' => fn() => 'bar',
   ]);
   $wrapper = new ServiceProviderContainer($provider);
   $container = new ServiceProviderContainer($provider, $wrapper);

This is useful when provider factories should resolve dependencies through a broader
application container instead of only through the provider itself.

Error Handling and Custom Exceptions
------------------------------------

- NotFoundException: Service not found
- InvalidArgumentException: Invalid or unsupported argument
- RuntimeException: Non-callable extension or runtime error
- ContainerException: PSR container resolution failed while building a service

.. code-block:: php

   try {
       $container->get('unknown');
   } catch (FastForward\Container\Exception\NotFoundException $e) {
       // Handle missing service
   }
