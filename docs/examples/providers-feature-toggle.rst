Runtime Feature Toggle
=====================

This example demonstrates how to use providers to enable or disable features at runtime.

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\container;

   $featureEnabled = true;

   $provider = new ArrayServiceProvider([
       'feature' => fn() => $featureEnabled ? new RealFeature() : new NullFeature(),
   ]);

   $container = container($provider);
   $feature = $container->get('feature');
   $feature->run();
