Integrating with Laravel
=======================

Laravel 12's container can invoke FastForward factories through a closure
adapter. This example uses ``illuminate/container:^12`` and assumes Composer's
autoloader is loaded and your application defines ``ExternalService``.

In a Laravel application, use the existing ``$app`` container supplied to your
service provider. The standalone container below is useful outside a full
Laravel application.

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\Factory\InvokableFactory;
   use Illuminate\Container\Container as LaravelContainer;

   $laravel = new LaravelContainer();
   $provider = new ArrayServiceProvider([
       'external_service' => fn() => new ExternalService(),
   ]);

   // Adapt the provider's factories to Laravel's closure-based bindings.
   foreach ($provider->getFactories() as $id => $factory) {
       $laravel->bind($id, fn(LaravelContainer $app) => $factory($app));
   }

   $service = $laravel->make('external_service');

Alternatively, adapt a single factory directly:

.. code-block:: php

   $factory = new InvokableFactory(ExternalService::class);
   $laravel->bind('external_service', fn(LaravelContainer $app) => $factory($app));

   $service = $laravel->make('external_service');

``bind()`` creates a fresh service on each resolution; use ``singleton()`` instead
when you want Laravel to share the result. An invokable factory object cannot be
passed directly as the concrete binding: Laravel expects a closure or class name.

This adapter imports factory definitions only. It does not apply provider
extensions or FastForward's shared-instance cache. To keep those behaviors,
construct a FastForward container separately and bind a closure that calls its
``get()`` method. Dependencies of those services then resolve within that
FastForward container.
