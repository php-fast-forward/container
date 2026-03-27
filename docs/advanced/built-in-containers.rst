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
Wraps a ConfigInterface and exposes its configuration as services in a PSR-11 container. Useful for integrating configuration-driven service definitions.

.. code-block:: php

   use FastForward\Container\ConfigContainer;
   $container = new ConfigContainer($config);

Composing Containers
--------------------
You can freely compose these containers. For example, you can wrap an AggregateContainer with an AutowireContainer, or use ServiceProviderContainer inside an AggregateContainer. The ``container()`` helper does this automatically for you.

See Also
--------
- :doc:`container-helper`
- :doc:`../providers/using-providers`
- :doc:`../providers/custom-provider`
