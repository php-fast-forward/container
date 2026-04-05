Container Helper Function
========================

The ``container()`` helper is the main entry point for building a fully-featured,
autowire-enabled container in FastForward Container. It is intentionally flexible so
you can start small and add more sources later.

Overview
--------

.. code-block:: php

   use function FastForward\Container\container;
   $container = container($initializer1, $initializer2, ...);

Supported initializers
----------------------

====================================  ================================================================
Initializer                           Behavior
====================================  ================================================================
``ConfigInterface``                   Wrapped in ``ConfigContainer``
``Psr\Container\ContainerInterface``  Added directly to the aggregate
``ServiceProviderInterface``          Wrapped in ``ServiceProviderContainer``
``string``                            Instantiated with ``new`` and then resolved using the same rules
====================================  ================================================================

All arguments are optional and variadic. You can mix them freely.

How It Works
------------

1. Each initializer is resolved to a container instance:
   - If it's already a container, it's used as-is.
   - If it's a service provider, it's wrapped in a ServiceProviderContainer.
   - If it's a config, it's wrapped in a ConfigContainer.
   - If it's a string, the class is instantiated and then resolved as above.
   - Any unsupported type throws an InvalidArgumentException.
2. All resolved containers are appended to an AggregateContainer.
3. If a ConfigContainer is present, it may provide nested initializers, which are also resolved and appended.
4. The final result is always an AutowireContainer wrapping the aggregate.

Resolution order
----------------

The aggregate container resolves services from left to right. In practice, this means:

- earlier initializers take precedence over later initializers when both can resolve the same ID
- explicit registrations win before autowiring is attempted
- ``container($tests, $defaults)`` is a simple override pattern for tests and local development

Examples
--------

Basic usage with a service provider:

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use function FastForward\Container\container;

   $provider = new ArrayServiceProvider([
       'foo' => fn() => new FooService(),
   ]);

   $container = container($provider);
   $foo = $container->get('foo');

Using a provider class name:

.. code-block:: php

   $container = container(MyServiceProvider::class);

Composing multiple providers and containers:

.. code-block:: php

   $containerA = container(ProviderA::class);
   $containerB = container(ProviderB::class);
   $main = container($containerA, $containerB);

Using a config object with nested providers:

.. code-block:: php

   use FastForward\Config\ArrayConfig;
   use FastForward\Container\ContainerInterface;
   use FastForward\Container\ServiceProvider\ArrayServiceProvider;

   $config = new ArrayConfig([
       'app' => [
           'name' => 'FastForward',
       ],
       ContainerInterface::class => [
           new ArrayServiceProvider([
               Banner::class => static fn($container): Banner => new Banner(
                   $container->get('config.app.name'),
               ),
           ]),
       ],
   ]);

   $container = container($config);
   $banner = $container->get(Banner::class);

Error Handling
--------------

If you pass an unsupported value, or a string that does not correspond to a valid class,
the function throws ``InvalidArgumentException``.

Configuration note
------------------

When you use ``fast-forward/config``, keep this distinction in mind:

- raw config uses ``FastForward\Container\ContainerInterface::class`` as the key for nested providers
- the wrapped ``ConfigContainer`` exposes normal values through ``config.*`` IDs

The helper internally asks the ``ConfigContainer`` for
``config.FastForward\Container\ContainerInterface`` and the config container translates that
into the raw config key for you.

Return Value
------------

The function always returns an ``AutowireContainer`` that wraps an ``AggregateContainer``
composed of all resolved sources. This gives you autowiring and aggregation out of the box,
regardless of how you initialize the container.

**Autowiring is powered internally by `PHP-DI <https://php-di.org/>`_.**

See Also
--------

- :doc:`../providers/using-providers`
- :doc:`../providers/custom-provider`
- :doc:`../providers/factories`
- :doc:`../advanced/autowire`
