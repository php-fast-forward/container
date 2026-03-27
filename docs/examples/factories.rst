Factories Example
=================

.. code-block:: php

   use FastForward\Container\Factory\CallableFactory;
   use FastForward\Container\Factory\AliasFactory;
   use FastForward\Container\Factory\InvokableFactory;
   use FastForward\Container\Factory\MethodFactory;
   use FastForward\Container\Factory\ServiceFactory;

   $callableFactory = new CallableFactory(fn($container) => new MyService($container->get(Dep::class)));
   $aliasFactory = new AliasFactory('other_service');
   $invokableFactory = new InvokableFactory(MyService::class, 'arg1');
   $methodFactory = new MethodFactory(MyService::class, 'staticMethod', 'arg1');
   $serviceFactory = new ServiceFactory(new MyService());

   $service = $callableFactory($container);
