Basic Usage
===========

This section explains the most common ways to build a FastForward container once you
understand the basic quickstart flow.

The mental model
----------------

The ``container()`` helper accepts one or more initializers and always returns an
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

When multiple initializers can resolve the same service ID, the first matching container
inside the aggregate wins. This makes ``container($override, $defaults)`` a useful pattern
for tests and environment-specific overrides.

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

Summary
-------

- ``container()`` is the main entry point and always returns an autowire-enabled container.
- You can mix providers, PSR-11 containers, config objects, and instantiable class names.
- The first matching container wins during aggregate resolution.
- Raw config keys and ``config.*`` service IDs are related, but they are not the same thing.
