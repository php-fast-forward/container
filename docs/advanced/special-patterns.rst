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

The ID-based extension runs first. Its return value replaces the previous value.
If that result is an object, a matching extension for its concrete class can run next.
The final result is cached under the original ID. Only a final object can also be
bound under its concrete class name. This inferred alias never replaces an
existing entry or a callable factory/extension registered for that class ID in
the provider container.

A completed class-extension result is reused for later lookups of the same object
identity within a provider container. Distinct objects of the same class are
extended independently of lookup order.
Aliases reuse the class extension's completed result, including a replacement
object, scalar or ``null``. This also applies when aliases retrieve the same original
object from a wrapper container. ID-based extensions for each alias still run.

Extensions should return the resulting entry, including when they modify an object
in place. Existing extensions explicitly declared ``void`` retain the previous value
for compatibility. An explicit ``null`` return from a value-returning extension
replaces the entry with ``null``; it does not mean the entry is missing.

Entries with extensions but no factory
-------------------------------------

An extension can supply a default entry when no factory exists for its ID. Its
second parameter must accept ``null``:

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProviderContainer;
   use Psr\Container\ContainerInterface;

   $provider = new ArrayServiceProvider(extensions: [
       'optional.client' => static function (ContainerInterface $container, ?ApiClient $previous): ApiClient {
           return $previous ?? new ApiClient();
       },
       'optional.value' => static fn(ContainerInterface $container, mixed $previous): mixed => null,
   ]);

   $container = new ServiceProviderContainer($provider);
   $container->has('optional.client'); // true, without executing the extension
   $client = $container->get('optional.client');
   $container->get('optional.client') === $client; // true
   $container->has('optional.value'); // true
   $container->get('optional.value'); // null, shared and cached

If the extension requires a non-null previous value, resolution fails with a
container exception preserving the original cause. Failed resolutions are not
cached and can be retried. An ID with neither a callable factory nor a callable
ID-based extension remains unknown.

When combining providers, use ``AggregateServiceProvider`` to select factories
across all providers before applying their extensions in registration order.
An extension-only provider may precede the provider supplying its factory.
Passing providers separately to ``container()`` keeps first-match-wins semantics.
The final ``AutowireContainer::has()`` also checks entry discovery without calling
``get()``. A ``true`` result does not guarantee that construction will succeed;
resolution errors are raised by ``get()``.

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
