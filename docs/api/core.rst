Core API
========

Overview
--------

===============================  ================================================  ===========================================
API                              Responsibility                                     Most common use
===============================  ================================================  ===========================================
``container()``                  Build the final composed container                Application entry point
``ContainerFactory``             Compose a fresh container stack                   Static application bootstrap
``ContainerBuilder``             Register sources and build fresh compositions     Conditional or staged application setup
``ContainerInterface``           FastForward-specific PSR-11 interface             Type hint inside FastForward packages
``AggregateContainer``           Resolve services across several child containers  Layering, overrides, fallback
``AutowireContainer``            Construct classes with an optional delegate       Standalone or delegated autowiring
``ServiceProviderContainer``     Expose a provider as a PSR-11 container           Bridging providers into the container stack
===============================  ================================================  ===========================================

``container()``
---------------

Signature:

.. code-block:: php

   use function FastForward\Container\container;

   $container = container(...$initializers);

Accepted initializers:

- ``ConfigInterface``
- ``Psr\Container\ContainerInterface``
- ``Interop\Container\ServiceProviderInterface``
- class strings that can be instantiated with ``new ClassName()``

Important behavior:

- The return value is always ``AutowireContainer``.
- The helper wraps providers in ``ServiceProviderContainer`` automatically.
- The helper wraps config objects in ``ConfigContainer`` automatically.
- Earlier initializers take precedence over later ones in aggregate resolution.
- Unknown class strings and classes that produce unsupported initializer objects raise ``InvalidArgumentException``.

The function delegates composition to ``ContainerFactory::create()``.

``ContainerFactory``
--------------------

``FastForward\Container\ContainerFactory::create()`` exposes the same composition
through a static method. It accepts the same variadic initializers as ``container()``:

.. code-block:: php

   use FastForward\Container\ContainerFactory;

   $container = ContainerFactory::create($provider, $config);
   $another = ContainerFactory::create($provider, $config);

Each call creates a fresh aggregate, provider adapters, and autowiring
container. The factory keeps no assembled container state. Existing PSR-11
containers supplied as initializers remain the same supplied objects.

Initializer order, provider wrapper injection, and nested configuration entries
are preserved. Unsupported initializer classes raise ``InvalidArgumentException``
before an invalid source reaches the aggregate.

Failures while reading or adapting optional nested configuration stop that
configuration's expansion and are ignored. Previously appended sources remain
available, and later explicit initializers are still processed.

The factory delegates registration and assembly to a new ``ContainerBuilder``.

``ContainerBuilder``
--------------------

``FastForward\Container\ContainerBuilder`` exposes incremental registration using
the same initializer types as the factory:

.. code-block:: php

   use FastForward\Container\ContainerBuilder;

   $builder = new ContainerBuilder();
   $builder->add($provider, $config);
   $first = $builder->build();

   $builder->add($featureProvider);
   $second = $builder->build();

``add(...$initializers): self`` appends sources and returns the same builder.
``build(): ContainerInterface`` returns a fresh ``AutowireContainer`` composition.
An empty builder provides autowiring, just like ``ContainerFactory::create()``.

Earlier registrations have precedence. Providers receive their build's aggregate
and can resolve dependencies from sources registered in other setup stages.
Optional nested configuration retains the factory's expansion and error policy.

Each build creates new composition caches and adapters. Later registrations do
not mutate earlier builds. Supplied config/provider/container objects are reused;
external PSR-11 containers retain their own state. Class strings are constructed
anew for every build and must be instantiable without required arguments.
Unknown or unsupported classes raise ``InvalidArgumentException`` during build.

``ContainerInterface``
----------------------

``FastForward\Container\ContainerInterface`` extends the PSR-11 interface without changing
its contract. Use it when you want to type-hint the FastForward container consistently
inside the FastForward ecosystem while staying compatible with PSR-11 consumers.

``AggregateContainer``
----------------------

``AggregateContainer`` combines multiple PSR-11 containers and resolves them in order.

Built-in aliases:

- ``container``
- ``FastForward\Container\AggregateContainer``
- ``FastForward\Container\ContainerInterface``
- ``Psr\Container\ContainerInterface``

Resolution rules:

- If an entry was already resolved once, the cached value is returned.
- Otherwise, child containers are checked from first to last.
- If one child reports ``has($id)`` but throws a PSR not-found or container exception during ``get($id)``, the aggregate keeps checking later containers.
- If no source succeeds, the first construction exception is rethrown. Lookups that only encounter missing entries throw ``NotFoundException``.

Useful methods:

- ``append()`` adds a container to the end of the resolution chain.
- ``prepend()`` adds a container to the beginning of the resolution chain.

Example:

.. code-block:: php

   $aggregate = new AggregateContainer($defaults);
   $aggregate->prepend($tests);

   $service = $aggregate->get('mailer');
   // The test container wins if it provides "mailer".

``AutowireContainer``
---------------------

``AutowireContainer`` provides constructor autowiring, with an optional PSR-11 delegate:

.. code-block:: php

   use FastForward\Container\AutowireContainer;

   $standalone = new AutowireContainer();
   $withDelegate = new AutowireContainer($existingContainer);

Important behavior:

- Omitting the delegate or passing ``null`` creates an empty aggregate internally.
- An existing ``AggregateContainer`` is reused; other delegates are wrapped in one.
- The delegate is checked before autowiring, including for nested constructor dependencies.
- An explicit delegate entry wins even when its concrete class could be autowired.
- Optional object dependencies are resolved before falling back to their declared defaults.
- Successful resolutions are cached. Separate standalone containers have independent caches.
- ``has($id)`` is strict: it attempts resolution and returns ``false`` when resolution throws.

This strict ``has()`` behavior is useful because it prevents false positives for services
that are declared but not actually constructible.

See :doc:`../advanced/autowire` for complete standalone and delegated examples,
interface bindings, scalar arguments and fallback behavior.

``ServiceProviderContainer``
----------------------------

``ServiceProviderContainer`` turns a ``ServiceProviderInterface`` into a PSR-11 container.

Important behavior:

- Entries are lazy and cached after the first successful ``get()``, including ``null``.
- If a final resolved object ID differs from its concrete class, the object is also cached under its class name when that ID has no existing entry or callable definition in the provider container.
- Extensions can target the original ID, the concrete class name, or both.
- Without a factory, a callable ID-based extension receives ``null`` and can supply the entry.
- Extension return values replace the previous value. Explicit ``void`` extensions retain it for compatibility.
- ``has()`` discovers callable factories and extension-only entries without executing them. Invalid factory values alone do not make an entry available.
- Provider metadata failures in ``has()`` and ``get()`` preserve their cause in ``ContainerException``.
- Factory and extension failures are wrapped in ``ContainerException`` with their original cause and are not cached.
- Factories and extensions receive the wrapper container, which defaults to the provider container itself.

This wrapper behavior is especially useful when the provider should resolve dependencies
through a broader application container instead of through its own limited view.
