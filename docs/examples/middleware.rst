Using Middlewares
=================

You can register middleware services and compose them using providers.

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\container;

   class AuthMiddleware {
       public function __invoke($request, $handler) { /* ... */ }
   }
   class LoggingMiddleware {
       public function __invoke($request, $handler) { /* ... */ }
   }

   $provider = new ArrayServiceProvider([
       'middlewares' => fn() => [
           new AuthMiddleware(),
           new LoggingMiddleware(),
       ],
   ]);

   $container = container($provider);
   $middlewares = $container->get('middlewares');
   // Use $middlewares in your framework or custom pipeline
