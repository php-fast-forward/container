Providers Example
=================

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProvider\AggregateServiceProvider;
   use FastForward\Container\ServiceProviderContainer;

   $provider1 = new ArrayServiceProvider([
       'service' => fn() => new MyService(),
   ]);
   $provider2 = new ArrayServiceProvider([
       'other' => fn() => new OtherService(),
   ]);

   $aggregate = new AggregateServiceProvider($provider1, $provider2);
   $container = new ServiceProviderContainer($aggregate);

   $service = $container->get('service');
