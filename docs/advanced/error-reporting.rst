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

Service-provider factories, aliases and extensions also detect circular resolution,
independently of native constructor autowiring. The exception cause includes the
active path, for example ``Circular dependency: a -> b -> a.``. Providers composed
through the same wrapper share that path. Re-entering the same provider and ID
fails promptly, while another provider may still resolve that ID through the
aggregate's normal fallback behavior.

Resolution paths are cleared after success or failure. Failed entries are not
cached, so a later request can retry construction after the application's
configuration or factory state has been corrected. Completed services retain the
usual per-container shared-instance behavior.

See :doc:`autowire` for the optional-dependency outcome table and guidance on explicit
factory recovery when the application intentionally tolerates a particular failure.

Example:

.. code-block:: php

   use Psr\Container\ContainerExceptionInterface;
   use Psr\Container\NotFoundExceptionInterface;

   try {
       $service = $container->get('service.id');
   } catch (NotFoundExceptionInterface $e) {
       // The entry is unknown.
   } catch (ContainerExceptionInterface $e) {
       // The entry is known, but resolution failed. Inspect the cause.
   }

``has()`` checks whether an entry is known; it does not guarantee that ``get()``
will succeed. A known class with uninferable arguments or a construction failure
can return ``true`` from ``has()`` and throw a construction exception from ``get()``.
An unknown entry returns ``false`` and ``get()`` throws ``NotFoundExceptionInterface``.
