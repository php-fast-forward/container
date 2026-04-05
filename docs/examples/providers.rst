Registering Providers
=====================

Registering Multiple Providers
------------------------------

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProvider\AggregateServiceProvider;
   use function FastForward\Container\container;

   $providerA = new ArrayServiceProvider([
       'mailer' => fn() => new Mailer(),
   ]);
   $providerB = new ArrayServiceProvider([
       'notifier' => fn() => new Notifier(),
   ]);

   $container = container($providerA, $providerB);
   $mailer = $container->get('mailer');
   $notifier = $container->get('notifier');

Extending Services with Providers
---------------------------------

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use function FastForward\Container\container;

   class UserRepository {
       public function setLogger($logger) { /* ... */ }
   }

   $provider = new ArrayServiceProvider([
       'user_repo' => fn() => new UserRepository(),
       'logger' => fn() => new Logger('app'),
   ], [
       'user_repo' => function ($container, $repo) {
           $repo->setLogger($container->get('logger'));
           return $repo;
       },
   ]);

   $container = container($provider);
   $repo = $container->get('user_repo');

Composing Providers for Feature Modules
---------------------------------------

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProvider\AggregateServiceProvider;
   use function FastForward\Container\container;

   $userProvider = new ArrayServiceProvider([
       'user_service' => fn() => new UserService(),
   ]);
   $authProvider = new ArrayServiceProvider([
       'auth_service' => fn($container) => new AuthService($container->get('user_service')),
   ]);

   $aggregate = new AggregateServiceProvider($userProvider, $authProvider);
   $container = container($aggregate);

   $userService = $container->get('user_service');
   $authService = $container->get('auth_service');


Using Providers with Config
---------------------------

.. code-block:: php

   use FastForward\Config\ArrayConfig;
   use function FastForward\Container\container;
   use FastForward\Container\ServiceProvider\ArrayServiceProvider;

   $config = new ArrayConfig([
       FastForward\Container\ContainerInterface::class => [
           new ArrayServiceProvider([
               'settings' => fn() => [
                   'debug' => true,
                   'timezone' => 'UTC',
               ],
           ]),
       ],
   ]);

   $container = container($config);
   $settings = $container->get('settings');


Provider Returning a Factory
----------------------------

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\Factory\InvokableFactory;
   use function FastForward\Container\container;

   $provider = new ArrayServiceProvider([
       'service' => new InvokableFactory(MyService::class, 'arg1'),
   ]);

   $container = container($provider);
   $service = $container->get('service');


Provider with Extension for Caching
-----------------------------------

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use function FastForward\Container\container;

   class Cache {
       public function enable() { /* ... */ }
   }

   $provider = new ArrayServiceProvider([
       'cache' => fn() => new Cache(),
   ], [
       'cache' => function ($container, $cache) {
           $cache->enable();
           return $cache;
       },
   ]);

   $container = container($provider);
   $cache = $container->get('cache');
