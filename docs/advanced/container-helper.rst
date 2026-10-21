Container Helper Function
=========================

The ``container()`` helper is the main entry point for building a fully-featured,
autowire-enabled container in FastForward Container. It is intentionally flexible so
you can start small and add more sources later.

Overview
--------

.. code-block:: php

   use function FastForward\Container\container;
   $container = container($initializer1, $initializer2, ...);

The helper delegates to ``ContainerFactory::create()``. You can call the same
composition directly through the static factory:

.. code-block:: php

   use FastForward\Container\ContainerFactory;

   $container = ContainerFactory::create($initializer1, $initializer2);

Both forms accept the same variadic initializers and create a fresh stack on each
call. Existing PSR-11 container objects passed as initializers are reused.

Incremental construction
------------------------

Use ``ContainerBuilder`` when registration depends on feature flags or occurs
across several setup stages. The factory creates a new builder internally, so
both construction styles use the same assembly implementation.

.. code-block:: php

   use FastForward\Container\ContainerBuilder;

   $builder = (new ContainerBuilder())->add($config, $baseProvider);

   if ($featureEnabled) {
       $builder->add($featureProvider);
   }

   $container = $builder->build();

``add()`` returns the same builder and accepts the initializers listed below.
Sources are retained in registration order without being instantiated or resolved
during registration. Class strings are instantiated during each build, and
unsupported classes are rejected at that point. Service factories retain their
existing lazy resolution behavior.

The builder is reusable. Each ``build()`` creates fresh aggregate, provider/config
adapters, and autowiring state. Adding sources after a build affects only future
builds. Existing source objects are reused: external PSR-11 containers may still
share their own caches and services across compositions.

The runnable ``examples/07-incremental-builder.php`` demonstrates conditional
registration and the independence of previously built containers.

Cross-provider composition
--------------------------

The helper, factory and builder compose all registered provider extensions by
default. No opt-in or additional entry point is required:

.. code-block:: php

   $helper = container($extensionProvider, $factoryProvider);
   $factory = ContainerFactory::create($extensionProvider, $factoryProvider);
   $builder = (new ContainerBuilder())->add($extensionProvider, $factoryProvider)->build();

Providers are collected before resolving an entry. The first callable factory for
an ID wins, preserving factory precedence. All provider extensions run in
registration order after factory selection, including extensions registered
before that factory. If no provider has a factory, extensions start with null.
Successful final values are cached, including null; void extensions retain their
input and other returns replace it. Aliases and exact concrete-class extensions
retain the provider adapter's identity and cycle rules.

Direct and configuration-discovered providers share one build-local aggregate
provider and adapter. Ordered views keep each selected factory at its original
provider position, preserving precedence against interleaved PSR-11/Config sources.
Extension-only defaults use the first extension position only when no provider has
a factory. External values remain independent and are not decorated by the group. Dual-role PSR-11/provider objects remain external PSR-11 sources.
Configured lists expand one level; invalid optional entries stop that list while
retaining prior entries and later explicit initializers. Class strings are
instantiated for each build.

Once a provider factory or extension is selected, construction/extension failures
propagate with their causes. Later factories, external sources and native
autowiring do not replace a broken selected entry. Failed/incomplete chains are
not cached and may be retried. Absence permits later sources. External PSR-11
sources retain aggregate fallback semantics.

Each build gets fresh adapters, provider caches and a completed aggregate wrapper
for cross-source dependencies. Supplied external containers and captured objects
may still share state. Explicit ``AggregateServiceProvider`` retains its distinct
last-factory-wins policy. Older releases may lack this correction; run
``examples/08-provider-composition.php`` against the installed library to verify it.

Supported initializers
----------------------

====================================  ================================================================
Initializer                           Behavior
====================================  ================================================================
``ConfigInterface``                   Wrapped in ``ConfigContainer``
``Psr\Container\ContainerInterface``  Added directly to the aggregate
``ServiceProviderInterface``          Collected into the build's shared provider adapter
``string``                            Instantiated with ``new`` and then resolved using the same rules
====================================  ================================================================

All arguments are optional and variadic. You can mix them freely.

How It Works
------------

1. Each initializer is resolved to a container instance:
   - If it's already a container, it's used as-is.
   - Service providers are collected into one build-local aggregate provider.
   - If it's a config, it's wrapped in a ConfigContainer.
   - If it's a string, the class is instantiated and then resolved as above.
   - Unknown class names and unsupported constructed objects throw an InvalidArgumentException.
2. Provider definitions select the first callable factory and compose every extension in registration order.
3. The provider adapter and independent resolved containers are appended to an AggregateContainer.
4. If a ConfigContainer is present, it may provide nested initializers, which are also resolved and appended.
5. The final result is always an AutowireContainer wrapping the aggregate.

Resolution order
----------------

The aggregate container resolves services from left to right. In practice, this means:

- earlier initializers take precedence over later initializers when both can resolve the same ID
- explicit registrations win before autowiring is attempted
- nested and optional object dependencies follow the same source order
- ``container($tests, $defaults)`` is a simple override pattern for tests and local development

For autowiring alone, ``new AutowireContainer()`` needs no initializers. To extend
one existing PSR-11 container, use ``new AutowireContainer($existingContainer)``;
its entries are checked before autowiring. See :doc:`autowire` for both examples.

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

Unknown class names and classes that cannot be adapted to a supported container,
provider, or config source raise ``InvalidArgumentException``. For example,
``container(stdClass::class)`` and ``ContainerFactory::create(stdClass::class)``
raise this exception before an invalid source reaches the aggregate.

Values outside the public parameter types, such as an array or a direct
``stdClass`` object, are rejected by PHP with ``TypeError``.

Failures while expanding optional nested configuration are caught. Expansion
stops for that configuration, previously appended sources remain available,
and later explicit initializers are still processed.

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

Autowiring uses native PHP reflection, resolving optional dependencies before defaults.
See :doc:`autowire` for the resolution rules.

See Also
--------

- :doc:`../providers/using-providers`
- :doc:`../providers/custom-provider`
- :doc:`../providers/factories`
- :doc:`../advanced/autowire`
