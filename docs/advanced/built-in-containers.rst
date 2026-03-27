Built-in Containers
==================

FastForward Container provides several built-in container classes to cover common use cases and advanced scenarios. Each built-in container has a specific role and can be composed with others for maximum flexibility.

Overview
--------

- **AggregateContainer**: Aggregates multiple containers into one, resolving services in order.
- **AutowireContainer**: Adds autowiring capabilities on top of any PSR-11 container.
- **ServiceProviderContainer**: Wraps a ServiceProviderInterface as a PSR-11 container.
- **ConfigContainer**: Wraps a ConfigInterface as a PSR-11 container.

Details
-------

AggregateContainer
------------------
Aggregates multiple containers. When you call ``get($id)``, it queries each container in order until one returns a value. Useful for composing several independent containers or layering features.

.. code-block:: php

   use FastForward\Container\AggregateContainer;
   $container = new AggregateContainer($containerA, $containerB);

AutowireContainer
-----------------

Adds autowiring support to any PSR-11 container. It will resolve and instantiate classes automatically if they are not found in the underlying container, using constructor injection.

**Autowiring is powered internally by `PHP-DI <https://php-di.org/>`_.**

.. code-block:: php

   use FastForward\Container\AutowireContainer;
   $container = new AutowireContainer($baseContainer);

ServiceProviderContainer
------------------------
Wraps a ServiceProviderInterface and exposes its factories and extensions as a PSR-11 container. This is the bridge between providers and the container system.

.. code-block:: php

   use FastForward\Container\ServiceProviderContainer;
   $container = new ServiceProviderContainer($provider);

ConfigContainer
---------------

**Note:** ConfigContainer is part of the `fast-forward/config` library, not this package. It is fully compatible and can be used together with FastForward Container.

ConfigContainer wraps any object implementing ``ConfigInterface`` and exposes its configuration as services in a PSR-11 container. The config object must implement the methods ``has($key)`` and ``get($key)``, where keys use dot notation for nested access (e.g., ``'database.host'``).

Example:

.. code-block:: php

   use FastForward\Config\ArrayConfig;
   use FastForward\Config\Container\ConfigContainer;

   $config = new ArrayConfig([
      'app' => [
         'env' => 'dev',
         'db' => [
            'host' => 'localhost',
            'port' => 3306,
         ],
      ],
   ]);

   $container = new ConfigContainer($config);

   // Access config values as services:
   $env = $container->get('config.app.env'); // 'dev'
   $host = $container->get('config.app.db.host'); // 'localhost'

The config object must support:

- ``$config->has('app.db.host')`` // returns true
- ``$config->get('app.db.host')`` // returns 'localhost'

This allows you to use configuration-driven service definitions and inject config values as services in your container setup.

Composing Containers
--------------------
You can freely compose these containers. For example, you can wrap an AggregateContainer with an AutowireContainer, or use ServiceProviderContainer inside an AggregateContainer. The ``container()`` helper does this automatically for you.

See Also
--------
- :doc:`container-helper`
- :doc:`../providers/using-providers`
- :doc:`../providers/custom-provider`
