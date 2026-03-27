Built-in Factories
==================

What is a Factory?
------------------
A factory is a special object or function that knows how to create a service (object, value, etc.) when the container is asked for it. In FastForward Container, factories are always callables that receive the container as their first argument, so they can fetch dependencies as needed.

Why Use Factories?
------------------
Factories allow you to:

- Control exactly how a service is built (constructor, static method, closure, etc.)
- Inject dependencies from the container
- Reuse logic for creating similar services
- Alias or decorate existing services

Types of Factories in FastForward Container
-------------------------------------------

1. AliasFactory
^^^^^^^^^^^^^^^
**Purpose:** Make one service ID behave as an alias for another. When you ask for the alias, you get the original service.

**How it works:**

.. code-block:: php

  use FastForward\Container\Factory\AliasFactory;
  $factory = new AliasFactory('real_service_id');
  $service = $factory($container); // Same as $container->get('real_service_id')

**When to use:** When you want two or more names to refer to the same service instance.

2. CallableFactory
^^^^^^^^^^^^^^^^^^
**Purpose:** Wrap any PHP callable (closure, function, invokable object) as a factory. The callable receives the container and can resolve dependencies dynamically.

**How it works:**

.. code-block:: php

  use FastForward\Container\Factory\CallableFactory;
  $factory = new CallableFactory(function ($container) {
     return new MyService($container->get(Dependency::class));
  });
  $service = $factory($container);

**When to use:** When you need full control over how a service is built, or want to use closures for dynamic logic.

3. InvokableFactory
^^^^^^^^^^^^^^^^^^^
**Purpose:** Instantiate a class using its constructor, optionally passing arguments. If an argument is a string and matches a service ID, it is resolved from the container.

**How it works:**

.. code-block:: php

  use FastForward\Container\Factory\InvokableFactory;
  $factory = new InvokableFactory(MyService::class, 'my.dependency', 'literal');
  $service = $factory($container); // 'my.dependency' is resolved from the container if available

**When to use:** For simple services where dependencies are known and can be passed as constructor arguments.

4. MethodFactory
^^^^^^^^^^^^^^^^
**Purpose:** Call a specific method (static or instance) on a class, optionally passing arguments. Arguments that are strings and match service IDs are resolved from the container.

**How it works:**

.. code-block:: php

  use FastForward\Container\Factory\MethodFactory;
  $factory = new MethodFactory(MyService::class, 'staticMethod', 'my.dependency');
  $result = $factory($container);

**When to use:** When you want to use a factory method or static initializer, or need to call a method after construction.

5. ServiceFactory
^^^^^^^^^^^^^^^^^
**Purpose:** Always returns the same fixed instance, regardless of the container.

**How it works:**

.. code-block:: php

  use FastForward\Container\Factory\ServiceFactory;
  $instance = new MyService();
  $factory = new ServiceFactory($instance);
  $service = $factory($container); // Always returns $instance

**When to use:** For registering pre-built or singleton objects.

Summary Table
-------------

=================  ===============================  ================================
Factory            What it does                     When to use
=================  ===============================  ================================
AliasFactory       Alias to another service         Multiple names for one service
CallableFactory    User-defined callable            Full control, dynamic logic
InvokableFactory   Class constructor                Simple instantiation, DI by name
MethodFactory      Class/static method              Factory/static methods, post-init
ServiceFactory     Fixed instance                   Pre-built or singleton objects
=================  ===============================  ================================

Tips for Beginners
------------------
- If you just want to register a class, use InvokableFactory.
- If you want to alias a service, use AliasFactory.
- If you need to run custom logic, use CallableFactory.
- If you want to call a static or instance method, use MethodFactory.
- If you already have an object, use ServiceFactory.

All factories are fully tested (see the tests/Factory directory) and can be combined with providers for advanced scenarios.