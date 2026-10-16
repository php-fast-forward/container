
Basic Usage
===========

Registering and Fetching Services
---------------------------------

This example shows how to register and retrieve services using the container helper.

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use function FastForward\Container\container;

   $provider = new ArrayServiceProvider([
       'logger' => fn() => new Monolog\Logger('app'),
       'pdo' => fn() => new PDO('sqlite::memory:'),
   ]);

   $container = container($provider);
   $logger = $container->get('logger');
   $pdo = $container->get('pdo');

Using Configuration to Register Providers
-----------------------------------------

You can use a configuration object to register providers dynamically:

.. code-block:: php

   use FastForward\Config\ArrayConfig;
   use function FastForward\Container\container;
   use FastForward\Container\ServiceProvider\ArrayServiceProvider;

   $config = new ArrayConfig([
       FastForward\Container\ContainerInterface::class => [
           new ArrayServiceProvider([
               'cache' => fn() => new MyCache(),
           ]),
       ],
   ]);

   $container = container($config);
   $cache = $container->get('cache');

Autowiring a Service (native reflection)
----------------------------------------

The container supports autowiring for classes with type-hinted dependencies:

.. code-block:: php

   use FastForward\Container\AutowireContainer;

   class MyDependency {}

   class MyService {
       public function __construct(MyDependency $dep) { /* ... */ }
   }

   $container = new AutowireContainer();
   $service = $container->get(MyService::class); // MyDependency is autowired

Pass an existing PSR-11 container to ``new AutowireContainer($existingContainer)``
to give its entries priority, including for constructor dependencies. Use
``container(...)`` when combining providers, configuration or several containers;
autowiring remains the final fallback. See :doc:`../advanced/autowire` for complete
examples of both standalone and delegated autowiring.
