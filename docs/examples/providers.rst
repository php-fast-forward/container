Registering Providers
=====================

Registering Multiple Providers
=============================

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProvider\AggregateServiceProvider;
   use FastForward\Container\container;

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
=================================

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\container;

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
======================================

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProvider\AggregateServiceProvider;
   use FastForward\Container\container;

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
