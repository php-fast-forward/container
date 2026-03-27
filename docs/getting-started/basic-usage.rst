Basic Usage
===========

This section shows how to initialize and use the FastForward Container step by step, explaining what each part does so you can adapt to your own project with confidence.

Step 1: Import the Required Classes
-----------------------------------
You need to import the main container helper and any configuration or service provider classes you want to use:

.. code-block:: php

   use FastForward\Container\container; // The main helper function
   use FastForward\Config\ArrayConfig;  // Example config provider

Step 2: Define Your Service Providers or Containers
---------------------------------------------------
You can use configuration objects, service provider classes, or even other PSR-11 containers. Here, we use an ArrayConfig to define a list of providers and containers:

.. code-block:: php

   $config = new ArrayConfig([
       FastForward\Container\ContainerInterface::class => [
           SomeServiceProvider::class,         // A class name (will be instantiated)
           SomePsr11Container::class,          // Another container (class name)
           new OtherServiceProvider('arg'),     // An already constructed provider
           new ServiceManager($dependencies),   // An existing PSR-11 container
       ],
   ]);

Step 3: Initialize the Container
--------------------------------
Use the ``container()`` helper to build your container from the config or directly from a list of providers/containers:

.. code-block:: php

   $container = container($config);

What happens here?
------------------
- The ``container()`` function inspects each argument:
  - If it is an object implementing PSR-11, it is added directly.
  - If it is a service provider, it is converted into a container.
  - If it is a string (class name), it is instantiated.
  - If it is a configuration, it may add more containers.
- The result is a container that aggregates all the given providers and containers, with autowire support.

Step 4: Retrieve Services
-------------------------
Now you can fetch any registered or autowired service:

.. code-block:: php

   $service = $container->get(SomeService::class);

Alternative: Direct Initialization
----------------------------------
You can pass providers/containers directly to the helper, without using a configuration object:

.. code-block:: php

   $container = container(
       SomeServiceProvider::class,           // Provider class
       SomePsr11Container::class,            // Another container
       new ApplicationConfig(),              // Configuration object
       new OtherServiceProvider('argument'), // Already instantiated provider
       new ServiceManager($dependencies),    // Already instantiated container
   );

This approach is flexible and allows you to compose your container as you prefer.

Summary
-------
- Always use the ``container()`` helper to initialize your container.
- You can mix providers, containers, configs, and class names.
- Once initialized, use ``$container->get(Service::class)`` to fetch any service.
