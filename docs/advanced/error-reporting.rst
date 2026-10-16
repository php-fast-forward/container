Error Reporting
===============

FastForward Container uses custom exception classes for error handling, all under the ``FastForward\Container\Exception`` namespace:

- ``ContainerException``: For general container errors (implements PSR-11 ``ContainerExceptionInterface``)
- ``AutowiringException``: Required constructor arguments cannot be inferred (implements PSR-11 ``ContainerExceptionInterface``)
- ``NotFoundException``: Thrown when a service identifier is not found (implements PSR-11 ``NotFoundExceptionInterface``)
- ``InvalidArgumentException``: For invalid or unsupported arguments
- ``RuntimeException``: For runtime errors, such as non-callable extensions or non-public methods

An aggregate continues trying later sources after a delegated PSR-11 failure. If no
source succeeds, it rethrows the first construction failure instead of replacing it
with a not-found exception. If all sources are absent, it throws ``NotFoundException``.

Optional constructor arguments use their declared defaults only for missing entries
or structurally unresolvable dependency graphs. Runtime constructor/factory errors,
invalid injected types and cycles remain failures. Their original causes are retained.

For example, a missing optional logger may use its declared ``null`` default. A logger
factory that throws during construction propagates a ``ContainerException`` with its
cause preserved when no other source can resolve it. A dependency cycle also propagates
a ``ContainerException`` with the cycle path. Choosing a default does not recover from
these execution failures.

See :doc:`autowire` for the optional-dependency outcome table and guidance on explicit
factory recovery when the application intentionally tolerates a particular failure.

Example:

.. code-block:: php

   use FastForward\Container\Exception\NotFoundException;

   try {
       $service = $container->get('unknown');
   } catch (NotFoundException $e) {
       // Handle missing service
   }
