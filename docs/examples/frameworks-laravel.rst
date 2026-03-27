Integrating with Laravel
=======================

You can use FastForward Container to register custom services or providers in a Laravel application, or to compose external providers with Laravel's container.

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use Illuminate\Container\Container as LaravelContainer;

   $laravel = new LaravelContainer();
   $provider = new ArrayServiceProvider([
       'external_service' => fn() => new ExternalService(),
   ]);

   // Register FastForward provider services into Laravel
   foreach ($provider->getFactories() as $id => $factory) {
       $laravel->bind($id, fn() => $factory($laravel));
   }

   // Or directly bind a factory that returns a service from FastForward provider
   $laravel->bind('external_service', new InvokableFactory(ExternalService::class));

   $service = $laravel->make('external_service');