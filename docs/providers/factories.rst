Built-in Factories
==================

What is a Factory?
------------------

A factory is the callable responsible for building a service when the container is asked
for it. In this package, all built-in factories implement ``FactoryInterface`` so they can
be used directly inside providers.

Why Use Factories?
------------------

Factories allow you to:

- Control exactly how a service is built (constructor, static method, closure, etc.)
- Inject dependencies from the container
- Reuse logic for creating similar services
- Alias or decorate existing services

Quick selection guide
---------------------

====================  ===============================================  ==========================================
Factory               Best when                                        Important behavior
====================  ===============================================  ==========================================
``AliasFactory``      Two IDs should return the same service           Supports cached static helper ``AliasFactory::get()``
``CallableFactory``   Construction depends on typed services           Resolves class/interface-typed parameters from the container
``InvokableFactory``  You want ``new ClassName(...)``                  Resolves string arguments only when they match existing service IDs
``MethodFactory``     You need a static or instance factory method     Falls back to ``new ClassName()`` for instance methods when needed
``ServiceFactory``    You already have the final object                Always returns the same instance
====================  ===============================================  ==========================================

1. AliasFactory
^^^^^^^^^^^^^^^

``AliasFactory`` makes one service ID behave as an alias for another. When you ask for the
alias, you receive the same object that would be returned for the original ID.

.. code-block:: php

   use FastForward\Container\Factory\AliasFactory;

   $factory = new AliasFactory('real_service_id');
   $service = $factory($container); // Same as $container->get('real_service_id')

If you reuse the same alias in many places, ``AliasFactory::get('real_service_id')`` returns
the same factory instance every time.

2. CallableFactory
^^^^^^^^^^^^^^^^^^

``CallableFactory`` wraps your own callable, but it does not pass raw scalar arguments into
that callable. Instead, it reflects the callable signature and resolves each class- or
interface-typed parameter from the container.

.. code-block:: php

   use FastForward\Container\Factory\CallableFactory;
   use Psr\Container\ContainerInterface;
   use Psr\Log\LoggerInterface;

   $factory = new CallableFactory(
       static fn(ContainerInterface $container, LoggerInterface $logger): Mailer => new Mailer($logger),
   );

If you declare a builtin parameter such as ``string`` or ``int``, the factory throws
``RuntimeException`` because builtin types cannot be resolved automatically.

3. InvokableFactory
^^^^^^^^^^^^^^^^^^^

``InvokableFactory`` instantiates a class through its constructor.

.. code-block:: php

   use FastForward\Container\Factory\InvokableFactory;

   $factory = new InvokableFactory(MyService::class, 'my.dependency', 'literal');
   $service = $factory($container);

String arguments are treated conservatively:

- if the string matches a registered service ID, it is resolved from the container
- otherwise, it stays a plain literal value

4. MethodFactory
^^^^^^^^^^^^^^^^

``MethodFactory`` calls a public static or instance method on a class.

.. code-block:: php

   use FastForward\Container\Factory\MethodFactory;

   $factory = new MethodFactory(MyService::class, 'build', 'my.dependency');
   $service = $factory($container);

Important behavior:

- Static methods are invoked without instantiating the class.
- Instance methods first try ``$container->get(MyService::class)``.
- If that lookup fails, the factory falls back to ``new MyService()``, so the class must be instantiable without constructor arguments in that branch.
- Non-public methods trigger ``RuntimeException``.

5. ServiceFactory
^^^^^^^^^^^^^^^^^

``ServiceFactory`` wraps a value or object you already created.

.. code-block:: php

   use FastForward\Container\Factory\ServiceFactory;

   $instance = new MyService();
   $factory = new ServiceFactory($instance);
   $service = $factory($container); // Always returns $instance

Summary Table
-------------

=================  ================================================  =========================================
Factory            Good default for                                 Avoid when
=================  ================================================  =========================================
AliasFactory       Secondary names and backwards-compatible IDs      You need a different object, not an alias
CallableFactory    Typed, custom assembly logic                      Your callable needs builtin scalar parameters
InvokableFactory   Simple constructor-based services                 Construction requires branching logic
MethodFactory      Named factory methods or post-construction hooks  The target method is not public
ServiceFactory     Existing instances and immutable values           You need a fresh object per resolution
=================  ================================================  =========================================

Tips for Beginners
------------------

- If you already have the final object, prefer ``ServiceFactory`` because it is the most explicit option.
- If you only need constructor calls, start with ``InvokableFactory``.
- If your closure needs services by type, use ``CallableFactory`` and add proper type hints.
- If you need two names for one service, use ``AliasFactory`` rather than duplicating factory logic.
- If a factory method already exists in your domain code, ``MethodFactory`` usually keeps the documentation easiest to read.
