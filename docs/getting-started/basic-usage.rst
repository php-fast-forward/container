Basic Usage
===========

This section explains the most common ways to build a FastForward container once you
understand the basic quickstart flow.

Choose an entry point
---------------------

- ``new AutowireContainer()`` builds concrete classes without registrations.
- ``new AutowireContainer($existingContainer)`` checks an existing PSR-11 container
  first and uses autowiring for the remaining classes, including nested dependencies.
- ``container(...)`` combines providers, configuration and PSR-11 containers.
  ``ContainerFactory`` offers the same composition through a static method;
  ``ContainerBuilder`` lets you register sources across several setup stages.

See :doc:`../advanced/autowire` for complete examples of the first two forms.
The following sections explain composition through the helper.

The mental model
----------------

The ``container()`` helper accepts zero or more initializers and always returns an
autowire-enabled container. Each initializer can be one of the following:

====================================  ==========================================================
Initializer type                      What the helper does
====================================  ==========================================================
``ServiceProviderInterface``          Wraps it in ``ServiceProviderContainer``
``Psr\Container\ContainerInterface``  Adds it directly to the aggregate container
``ConfigInterface``                   Wraps it in ``ConfigContainer``
``string``                            Instantiates the class with ``new`` and then applies the same rules
====================================  ==========================================================

If you pass class names as strings, they must be instantiable without constructor arguments.
If they need runtime values, instantiate them yourself before passing them to ``container()``.

Step 1: Start with one provider
-------------------------------

For most projects, the easiest entry point is a single ``ArrayServiceProvider``:

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use function FastForward\Container\container;

   $provider = new ArrayServiceProvider([
       'logger' => static fn(): Logger => new Logger('app'),
   ]);

   $container = container($provider);
   $logger = $container->get('logger');

At this point you already have:

- explicit service registration through the provider
- PSR-11 compatible ``get()`` and ``has()``
- autowiring for classes that can be constructed from known dependencies

You can also compose the same stack through the static factory:

.. code-block:: php

   use FastForward\Container\ContainerFactory;

   $container = ContainerFactory::create($provider);
   $logger = $container->get('logger');

``ContainerFactory::create()`` accepts the same variadic initializers as
``container()``. Each call creates a fresh stack without requiring a factory instance.

Step 2: Compose more than one source
------------------------------------

You can pass multiple providers and containers when your application is split by feature
or when you need to reuse an existing PSR-11 container:

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use function FastForward\Container\container;

   $catalogProvider = new ArrayServiceProvider([
       ProductCatalog::class => static fn(): ProductCatalog => new ProductCatalog(),
   ]);

   $legacyContainer = new LegacyContainer();

   $container = container($catalogProvider, $legacyContainer);

Provider factories use first-callable precedence and all extensions run afterward
in registration order. External sources remain independent. When multiple external
initializers can resolve the same service ID, the first successful
source wins. Explicit sources are checked before autowiring, including for nested
and optional object dependencies. This makes ``container($override, $defaults)``
a useful pattern for tests and environment-specific overrides. Configure the sources
before the first lookup: successfully resolved entries are cached.

Step 3: Register providers through configuration
------------------------------------------------

If you already use ``fast-forward/config``, you can declare providers inside your config object:

.. code-block:: php

   use FastForward\Config\ArrayConfig;
   use FastForward\Container\ContainerInterface;
   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use function FastForward\Container\container;

   $config = new ArrayConfig([
       'app' => [
           'name' => 'FastForward Storefront',
       ],
       ContainerInterface::class => [
           new ArrayServiceProvider([
               ApplicationName::class => static fn($container): ApplicationName => new ApplicationName(
                   $container->get('config.app.name'),
               ),
           ]),
       ],
   ]);

   $container = container($config);
   $name = $container->get('config.app.name');
   $app = $container->get(ApplicationName::class);

Important detail
----------------

There are two different identifiers involved in the configuration flow:

- Inside your raw config object, you register nested providers under ``FastForward\Container\ContainerInterface::class``.
- After the config is wrapped by ``ConfigContainer``, individual config values are exposed through service IDs such as ``config.app.name``.

This distinction matters because new users often try to store raw config values under
``config.*`` keys. The ``config.`` prefix belongs to the container view, not to the raw
configuration array.

Step 4: Use class names as shortcuts when appropriate
-----------------------------------------------------

Class-string initializers are convenient when the class has a parameterless constructor:

.. code-block:: php

   $container = container(
       AppProvider::class,
       LegacyContainer::class,
   );

If either class needs constructor arguments, instantiate it yourself instead of relying
on the shortcut.

Step 5: Register sources in stages
----------------------------------

Use ``ContainerBuilder`` when sources are registered conditionally or across several
setup stages:

.. code-block:: php

   use FastForward\Container\ContainerBuilder;

   $builder = (new ContainerBuilder())->add($catalogProvider, $legacyContainer);

   if ($featureEnabled) {
       $builder->add($featureProvider);
   }

   $container = $builder->build();

``add()`` is fluent and accepts the same initializer types. Each ``build()`` creates
a fresh composition. Adding sources later affects subsequent builds only; supplied
PSR-11 containers continue to share their own state.

See :doc:`../advanced/container-helper` for reuse, precedence, and error handling.

Summary
-------

- ``container()`` and ``ContainerFactory::create()`` both return an autowire-enabled container.
- You can mix providers, PSR-11 containers, config objects, and instantiable class names.
- ``ContainerBuilder`` exposes the same composition incrementally through ``add()`` and ``build()``.
- The first matching container wins during aggregate resolution.
- Raw config keys and ``config.*`` service IDs are related, but they are not the same thing.
