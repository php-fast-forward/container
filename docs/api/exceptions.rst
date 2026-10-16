Exceptions API
==============

Overview
--------

===============================  ================================================  ===========================================
Exception class                  Relationship to PSR-11 exceptions                 Common use cases
===============================  ================================================  ===========================================
``NotFoundException``            Implements ``NotFoundExceptionInterface``          A service ID cannot be resolved
``ContainerException``           Implements ``ContainerExceptionInterface``         A provider factory throws a PSR container exception
``AutowiringException``          Implements ``ContainerExceptionInterface``         Required constructor arguments cannot be inferred
``InvalidArgumentException``     Extends PHP ``InvalidArgumentException``          Container composition receives an invalid class
``RuntimeException``             Extends PHP ``RuntimeException``                  Invalid extensions, invalid callable parameters, or non-public methods
===============================  ================================================  ===========================================

``NotFoundException``
---------------------

Used when a service identifier is unknown or cannot be found in the current container chain.

``ContainerException``
----------------------

Used by ``ServiceProviderContainer`` and the native autowiring resolver to wrap
execution failures while building a service. Also reports native dependency cycles.

This preserves the original exception as ``getPrevious()`` so callers can inspect the
underlying failure when needed.

``AutowiringException``
-----------------------

Signals a structurally unresolvable constructor graph, such as a required scalar or
an unregistered interface. Optional object arguments can use their declared defaults
for this condition. Constructor/factory execution errors use ``ContainerException``
and remain visible instead of selecting a default.

``InvalidArgumentException``
----------------------------

Used by ``container()`` and ``ContainerFactory::create()`` when a class-string
initializer cannot be resolved to a supported container source.

Common causes:

- passing an unknown class name
- passing an existing class, such as ``stdClass``, that implements none of the supported initializer contracts

Arrays and direct objects outside the public parameter types are rejected by PHP
with ``TypeError`` before composition starts.

``RuntimeException``
--------------------

Used for package-specific runtime problems such as:

- a ``CallableFactory`` parameter uses a builtin type instead of a class or interface
- an ``AggregateServiceProvider`` extension is not callable
- a ``MethodFactory`` target method is not public

See also :doc:`../advanced/error-reporting`.
