Using Factories in Providers
===========================

Factories are the recommended way to define how your services are created inside a service provider. They allow you to:

- Use constructor injection (InvokableFactory)
- Alias services (AliasFactory)
- Use custom logic (CallableFactory)
- Call static or instance methods (MethodFactory)
- Register pre-built objects (ServiceFactory)

Example: Registering with Different Factories
---------------------------------------------

.. code-block:: php

   use FastForward\Container\Factory\InvokableFactory;
   use FastForward\Container\Factory\AliasFactory;
   use FastForward\Container\Factory\CallableFactory;
   use FastForward\Container\Factory\ServiceFactory;

   class MyServiceProvider implements ServiceProviderInterface
   {
       public function getFactories(): array
       {
           return [
               'foo' => new InvokableFactory(FooService::class),
               'bar' => new AliasFactory('foo'),
               'baz' => new CallableFactory(function ($container) {
                   return new BazService($container->get('foo'));
               }),
               'singleton' => new ServiceFactory(new SingletonService()),
           ];
       }

       public function getExtensions(): array
       {
           return [];
       }
   }

See the Factories section for a detailed explanation of each factory type and when to use them.