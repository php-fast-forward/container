Exceptions API
==============

Overview
--------

===============================  ================================================  ===========================================
Exception class                  Relationship to PSR-11 exceptions                 Common use cases
===============================  ================================================  ===========================================
``NotFoundException``            Implements ``NotFoundExceptionInterface``          A service ID cannot be resolved
``ContainerException``           Implements ``ContainerExceptionInterface``         A provider factory throws a PSR container exception
``InvalidArgumentException``     Extends PHP ``InvalidArgumentException``          ``container()`` receives an unsupported initializer
``RuntimeException``             Extends PHP ``RuntimeException``                  Invalid extensions, invalid callable parameters, or non-public methods
===============================  ================================================  ===========================================

``NotFoundException``
---------------------

Used when a service identifier is unknown or cannot be found in the current container chain.

``ContainerException``
----------------------

Used by ``ServiceProviderContainer`` to wrap container-specific failures that happen while
building a service.

This preserves the original exception as ``getPrevious()`` so callers can inspect the
underlying failure when needed.

``InvalidArgumentException``
----------------------------

Used when ``container()`` receives a value it does not know how to turn into a container
source.

Common causes:

- passing arrays directly to ``container()``
- passing scalar values other than supported class strings

``RuntimeException``
--------------------

Used for package-specific runtime problems such as:

- a ``CallableFactory`` parameter uses a builtin type instead of a class or interface
- an ``AggregateServiceProvider`` extension is not callable
- a ``MethodFactory`` target method is not public

See also :doc:`../advanced/error-reporting`.
