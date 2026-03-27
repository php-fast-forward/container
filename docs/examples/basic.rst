Basic Example
=============

.. code-block:: php

   use FastForward\Container\container;
   use FastForward\Config\ArrayConfig;

   $config = new ArrayConfig([
       FastForward\Container\ContainerInterface::class => [
           SomeServiceProvider::class,
           new OtherServiceProvider(),
       ],
   ]);

   $container = container($config);
   $service = $container->get(SomeService::class);
