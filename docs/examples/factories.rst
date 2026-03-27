
Using Factories
===============

Using CallableFactory for Dependency Injection
==============================================

.. code-block:: php

   use FastForward\Container\Factory\CallableFactory;

   $factory = new CallableFactory(fn($container) => new Mailer($container->get('logger')));
   $mailer = $factory($container);

Aliasing Services with AliasFactory
==================================

.. code-block:: php

   use FastForward\Container\Factory\AliasFactory;

   $factory = new AliasFactory('mailer');
   $sameMailer = $factory($container); // Returns the same as $container->get('mailer')

Invoking Classes with InvokableFactory
=====================================

.. code-block:: php

   use FastForward\Container\Factory\InvokableFactory;

   $factory = new InvokableFactory(MyService::class, 'arg1', 'arg2');
   $service = $factory($container);

Calling Static Methods with MethodFactory
========================================

.. code-block:: php

   use FastForward\Container\Factory\MethodFactory;

   $factory = new MethodFactory(MyService::class, 'build', 'arg1');
   $service = $factory($container);

Wrapping Existing Instances with ServiceFactory
==============================================

.. code-block:: php

   use FastForward\Container\Factory\ServiceFactory;

   $instance = new MyService();
   $factory = new ServiceFactory($instance);
   $service = $factory($container); // Always returns $instance
