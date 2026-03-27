Writing Your Own Provider
=========================

You can create your own service provider class by implementing the ``Interop\Container\ServiceProviderInterface``. This gives you full control over how services and extensions are registered.

Example: Custom Provider Using Factories

.. code-block:: php

   use Interop\Container\ServiceProviderInterface;
   use FastForward\Container\Factory\InvokableFactory;
   use FastForward\Container\Factory\AliasFactory;
   use Psr\Container\ContainerInterface;
   use Psr\Log\LoggerInterface;
   use Psr\Log\LoggerAwareInterface;

   class MyServiceProvider implements ServiceProviderInterface
   {
       public function getFactories(): array
       {
           return [
               'foo' => new InvokableFactory(FooService::class),
               'bar' => new AliasFactory('foo'),
           ];
       }

       public function getExtensions(): array
       {
           return [
               'foo' => function (ContainerInterface $container, FooService $service) {
                   // Decorate or modify $service as needed
                   if ($service instanceof LoggerAwareInterface) {
                       $logger = $container->get(LoggerInterface::class);
                       $service->setLogger($logger);
                   }

                   return $service;
               },
           ];
       }
   }

You can then use your provider with ``ServiceProviderContainer`` or the ``container()`` helper:

.. code-block:: php

   $provider = new MyServiceProvider();
   $container = container($provider); // or container(MyServiceProvider::class);
   $foo = $container->get('foo');

Using AggregateServiceProvider
-----------------------------

If you want to combine multiple providers into a single one, use the ``AggregateServiceProvider``. This is useful for modular applications or when you want to compose several independent providers:

.. code-block:: php

   use FastForward\Container\ServiceProvider\AggregateServiceProvider;
   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProviderContainer;

   $providerA = new ArrayServiceProvider([
       'foo' => fn() => new FooService(),
   ]);

   $providerB = new ArrayServiceProvider([
       'bar' => fn() => new BarService(),
   ]);

   $aggregate = new AggregateServiceProvider($providerA, $providerB);
   $container = new ServiceProviderContainer($aggregate);

   $foo = $container->get('foo'); // from providerA
   $bar = $container->get('bar'); // from providerB
