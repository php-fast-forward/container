Special Patterns and Advanced Behaviors
======================================

This section highlights advanced usage patterns, special behaviors, and edge cases supported by FastForward Container. Use these techniques to unlock more power and flexibility in your dependency management.

Singleton-like Service Resolution
---------------------------------
All containers (including ServiceProviderContainer and AggregateContainer) cache resolved services by default. This means each service is instantiated only once per container instance, ensuring singleton-like behavior. If you need a new instance each time, register a factory that returns a new object on every call.

.. code-block:: php

   // Always returns the same instance
   $service1 = $container->get('foo');
   $service2 = $container->get('foo'); // $service1 === $service2

   // To get a new instance each time, register a factory that does not cache
   $provider = new ArrayServiceProvider([
       'random_factory' => fn() => new InvokableFactory(RandomObject::class),
   ]);

   $container = container($provider);
   $factory = $container->get('random_factory');
   $obj1 = $factory($container); // New instance
   $obj2 = $factory($container); // New instance, different from $obj1


Service Extensions and Decorators
---------------------------------
Extensions allow you to decorate or modify services after creation. You can chain multiple extensions, and each receives the previous result and the container (which may be a wrapper).

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

Container Aliases and Self-Resolution
-------------------------------------
AggregateContainer registers itself under common aliases, including 'container', its class name, and the PSR-11 interface. This allows you to fetch the container itself from within a factory or extension:

.. code-block:: php

   $container = container($provider);
   $self = $container->get('container'); // Returns the container instance
   $self2 = $container->get(FastForward\Container\ContainerInterface::class); // Also returns the container instance
   $self3 = $container->get(FastForward\Container\AggregateContainer::class); // Also returns the container instance
   $self4 = $container->get(Psr\Container\ContainerInterface::class); // Also returns the container instance

Fallback and Resolution Order
-----------------------------
When using AggregateContainer, services are resolved in the order containers are aggregated. The first container to provide a service wins. This enables fallback strategies:

.. code-block:: php

   $containerA = new ArrayServiceProvider(['foo' => fn() => 'A']);
   $containerB = new ArrayServiceProvider(['foo' => fn() => 'B']);
   $container = container($containerA, $containerB);
   $foo = $container->get('foo'); // Returns 'A'

Factories as Services
---------------------
You can register any callable or FactoryInterface as a service. The container will invoke it with itself as argument:

.. code-block:: php

   use FastForward\Container\Factory\InvokableFactory;
   $provider = new ArrayServiceProvider([
       'service' => new InvokableFactory(MyService::class),
   ]);
   $service = container($provider)->get('service');

WrapperContainer for Delegation
-------------------------------
ServiceProviderContainer allows passing a wrapperContainer, which is injected into factories and extensions. This enables advanced delegation or testing scenarios.

.. code-block:: php

   $provider = new ArrayServiceProvider([
       'foo' => fn() => 'bar',
   ]);
   $wrapper = new ServiceProviderContainer($provider);
   $container = new ServiceProviderContainer($provider, $wrapper);
   // Now, factories/extensions receive $wrapper as the container argument

Error Handling and Custom Exceptions
------------------------------------
FastForward Container throws custom exceptions for different error scenarios:

- NotFoundException: Service not found
- InvalidArgumentException: Invalid or unsupported argument
- RuntimeException: Non-callable extension or runtime error

.. code-block:: php

   try {
       $container->get('unknown');
   } catch (FastForward\Container\Exception\NotFoundException $e) {
       // Handle missing service
   }
