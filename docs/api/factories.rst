Factories API
=============

Overview
--------

All built-in factories implement ``FactoryInterface``:

.. code-block:: php

   interface FactoryInterface
   {
       public function __invoke(ContainerInterface $container): mixed;
   }

Factory matrix
--------------

.. list-table::
   :header-rows: 1

   * - Factory
     - Primary purpose
     - Key behavior
   * - ``AliasFactory``
     - Reuse another service under a new ID
     - Also offers ``AliasFactory::get()``
   * - ``CallableFactory``
     - Build services with a typed callable
     - Resolves class/interface parameters by type
   * - ``InvokableFactory``
     - Instantiate a class through its constructor
     - Resolves string arguments when they match service IDs
   * - ``MethodFactory``
     - Call a public static or instance method
     - Can fall back to ``new ClassName()``
   * - ``ServiceFactory``
     - Wrap an existing instance or value
     - Always returns the same object or value

``AliasFactory``
----------------

Use ``AliasFactory`` when two identifiers should resolve to the same service instance.

The static helper ``AliasFactory::get($alias)`` caches factory instances per alias name,
which is useful when you define the same alias repeatedly across provider declarations.

``CallableFactory``
-------------------

``CallableFactory`` resolves each callable parameter from the container by type.

That means:

- class-typed parameters are fetched from the container
- interface-typed parameters are fetched from the container
- builtin parameters such as ``string`` and ``int`` are not supported and cause ``RuntimeException``

If you want direct access to the container inside the callable, type-hint
``Psr\Container\ContainerInterface`` or ``FastForward\Container\ContainerInterface``.

``InvokableFactory``
--------------------

``InvokableFactory`` is the simplest class factory. It calls:

.. code-block:: php

   new ClassName(...$arguments)

String arguments are resolved carefully:

- if the string matches a known service ID, the container value is injected
- otherwise, the string is treated as a plain literal

This makes it safe to mix service IDs and ordinary scalar values in one constructor call.

``MethodFactory``
-----------------

``MethodFactory`` calls a named public method on a class or an object registered
under a container service ID.

Resolution rules:

- string arguments follow the same service-ID-or-literal logic as ``InvokableFactory``
- concrete public static methods are invoked directly; abstract static contracts resolve their implementation through the container
- instance targets resolve through ``$container->get($target)`` using the supplied class name or service ID; methods are reflected on the actual object
- when ``has(ClassName::class)`` reports an absent entry, the factory tries ``new ClassName()``; that class must be instantiable without constructor arguments
- registered targets always use ``get()``; a nested ``NotFoundExceptionInterface`` remains a construction failure and never triggers a replacement
- missing non-class service IDs remain not-found errors; container, factory and constructor execution failures propagate without constructing a replacement
- non-public methods cause ``RuntimeException``

This is a good fit when your domain already exposes named constructors such as
``fromConfig()``, ``build()``, or ``fromStage()``.

``ServiceFactory``
------------------

``ServiceFactory`` returns the exact value passed to its constructor. It does not clone
objects and it does not create new ones.

Use it when:

- you already created the object elsewhere
- you want to expose an immutable value object
- you want one shared instance under a service ID
