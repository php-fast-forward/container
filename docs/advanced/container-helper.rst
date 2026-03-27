Container Helper Function
========================

The ``container()`` helper is the main entry point for building a fully-featured, autowire-enabled container in FastForward Container. It is designed to be flexible and convenient, supporting multiple initialization patterns and advanced composition.

Overview
--------

.. code-block:: php

   use FastForward\Container\container;
   $container = container($initializer1, $initializer2, ...);

You can pass any combination of the following as arguments:

- **ConfigInterface**: A configuration object (for advanced setups)
- **Psr\Container\ContainerInterface**: Any PSR-11 compatible container
- **ServiceProviderInterface**: Any service provider
- **string**: The fully qualified class name of a ConfigInterface, ServiceProviderInterface, or PSR-11 container (it will be instantiated automatically)

All arguments are optional and variadic. You can mix and match them as needed.

How It Works
------------

1. Each initializer is resolved to a container instance:
   - If it's already a container, it's used as-is.
   - If it's a service provider, it's wrapped in a ServiceProviderContainer.
   - If it's a config, it's wrapped in a ConfigContainer.
   - If it's a string, the class is instantiated and then resolved as above.
   - Any unsupported type throws an InvalidArgumentException.
2. All resolved containers are appended to an AggregateContainer.
3. If a ConfigContainer is present, it may provide nested initializers (via a special config key), which are also resolved and appended.
4. The final result is always an AutowireContainer wrapping the aggregate.

Supported Initializers
----------------------

- ``ConfigInterface``
- ``Psr\Container\ContainerInterface``
- ``ServiceProviderInterface``
- ``string`` (class name of any of the above)

If you pass a string, the function will instantiate the class and treat it as if you had passed the object directly.

Examples
--------

Basic usage with a service provider:

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\container;

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

Using a config object:

.. code-block:: php

   $config = new MyConfig();
   $container = container($config);

Error Handling
--------------

If you pass an unsupported type, or a string that does not correspond to a valid class, the function will throw an InvalidArgumentException.


Advanced: Using Config to Register Providers
--------------------------------------------

If you use a ConfigContainer (from the `fast-forward/config` library), it can provide a special config key (``ConfigContainer::ALIAS.ContainerInterface::class``) containing an array of service providers or containers. The container() helper will automatically resolve and append each of these to the AggregateContainer.

This allows you to register providers or containers via configuration, making your setup more modular and dynamic.

Example:

.. code-block:: php

   use FastForward\Config\ArrayConfig;
   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\container;
   use FastForward\Container\ContainerInterface;

   $config = new ArrayConfig([
      ContainerInterface::class => [
         new ArrayServiceProvider([
            'foo' => fn() => 'bar',
         ]),
      ],
   ]);

   $container = container($config);
   $foo = $container->get('foo'); // 'bar'

In this example, the config provides the special key for providers (using the fully qualified class name as the key). The container() helper will extract and register all providers listed there, just as if you had passed them directly.

Return Value
------------

The function always returns an ``AutowireContainer`` that wraps an ``AggregateContainer`` composed of all resolved sources. This means you get autowiring and aggregation out of the box, regardless of how you initialize the container.

See Also
--------

- :doc:`../providers/using-providers`
- :doc:`../providers/custom-provider`
- :doc:`../providers/factories`
- :doc:`../advanced/autowire`
