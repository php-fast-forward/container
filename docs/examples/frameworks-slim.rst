Integrating with Slim Framework
==============================

You can use FastForward Container as the dependency injection container for Slim Framework.

.. code-block:: php

   use FastForward\Container\container;
   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use Slim\App;

   $provider = new ArrayServiceProvider([
       'logger' => fn() => new Logger('api'),
   ]);
   $container = container($provider);

   $app = App::createFromContainer($container);
   $app->get('/ping', function ($request, $response) {
       $logger = $this->get('logger');
       $logger->info('Ping route called');
       return $response->withJson(['pong' => true]);
   });