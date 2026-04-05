Service Providers
================

Service providers are the main way to organize explicit service registrations in
FastForward Container. They let you group related factories and service extensions so
your container configuration stays modular and easy to evolve.

What is a Service Provider?
--------------------------

A service provider is any object that implements the
``Interop\Container\ServiceProviderInterface``. It must provide two methods:

- ``getFactories()``: returns an associative array of service IDs to factory callables.
- ``getExtensions()``: returns an associative array of service IDs to extension callables (optional, for decorating services after creation).

Choosing a provider composition style
-------------------------------------

FastForward supports two different ways to combine providers, and they are intentionally
different:

===============================  ==========================================================
Approach                         Resolution behavior
===============================  ==========================================================
``container($providerA, $providerB)``  The first provider whose container has the ID wins
``new AggregateServiceProvider(...)``  Factories are merged first, so later providers overwrite earlier keys
===============================  ==========================================================

Use ``container($providerA, $providerB)`` when you want layered fallback or overrides.
Use ``AggregateServiceProvider`` when you want to publish one merged provider as a single unit.

.. toctree::
   :maxdepth: 2

   factories
   using-providers
   custom-provider
   using-factories
