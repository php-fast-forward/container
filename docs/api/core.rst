Core API
========

Overview
--------

===============================  ================================================  ===========================================
API                              Responsibility                                     Most common use
===============================  ================================================  ===========================================
``container()``                  Build the final composed container                Application entry point
``ContainerInterface``           FastForward-specific PSR-11 interface             Type hint inside FastForward packages
``AggregateContainer``           Resolve services across several child containers  Layering, overrides, fallback
``AutowireContainer``            Add autowiring on top of another container        Mixed explicit registration and autowiring
``ServiceProviderContainer``     Expose a provider as a PSR-11 container           Bridging providers into the container stack
===============================  ================================================  ===========================================

``container()``
---------------

Signature:

.. code-block:: php

   use function FastForward\Container\container;

   $container = container(...$initializers);

Accepted initializers:

- ``ConfigInterface``
- ``Psr\Container\ContainerInterface``
- ``Interop\Container\ServiceProviderInterface``
- class strings that can be instantiated with ``new ClassName()``

Important behavior:

- The return value is always ``AutowireContainer``.
- The helper wraps providers in ``ServiceProviderContainer`` automatically.
- The helper wraps config objects in ``ConfigContainer`` automatically.
- Earlier initializers take precedence over later ones in aggregate resolution.

``ContainerInterface``
----------------------

``FastForward\Container\ContainerInterface`` extends the PSR-11 interface without changing
its contract. Use it when you want to type-hint the FastForward container consistently
inside the FastForward ecosystem while staying compatible with PSR-11 consumers.

``AggregateContainer``
----------------------

``AggregateContainer`` combines multiple PSR-11 containers and resolves them in order.

Built-in aliases:

- ``container``
- ``FastForward\Container\AggregateContainer``
- ``FastForward\Container\ContainerInterface``
- ``Psr\Container\ContainerInterface``

Resolution rules:

- If an entry was already resolved once, the cached value is returned.
- Otherwise, child containers are checked from first to last.
- If one child reports ``has($id)`` but throws a PSR not-found or container exception during ``get($id)``, the aggregate keeps checking later containers.

Useful methods:

- ``append()`` adds a container to the end of the resolution chain.
- ``prepend()`` adds a container to the beginning of the resolution chain.

Example:

.. code-block:: php

   $aggregate = new AggregateContainer($defaults);
   $aggregate->prepend($tests);

   $service = $aggregate->get('mailer');
   // The test container wins if it provides "mailer".

``AutowireContainer``
---------------------

``AutowireContainer`` wraps another PSR-11 container and appends an internal PHP-DI
container for autowiring.

Important behavior:

- If the delegate is not already an ``AggregateContainer``, it is wrapped in one.
- Explicit registrations are still checked before autowiring.
- ``has($id)`` is strict: it attempts resolution and returns ``false`` when resolution throws.

This strict ``has()`` behavior is useful because it prevents false positives for services
that are declared but not actually constructible.

``ServiceProviderContainer``
----------------------------

``ServiceProviderContainer`` turns a ``ServiceProviderInterface`` into a PSR-11 container.

Important behavior:

- Services are lazy and cached after the first ``get()``.
- If a resolved service ID differs from the service concrete class, the service is also cached under its class name.
- Extensions can target the original ID, the concrete class name, or both.
- Factories and extensions receive the wrapper container, which defaults to the provider container itself.

This wrapper behavior is especially useful when the provider should resolve dependencies
through a broader application container instead of through its own limited view.
