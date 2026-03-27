Service Providers
================

Service providers are a powerful way to register and organize services and their extensions in the container. They allow you to group related factories and extensions, making your dependency configuration modular and maintainable.

What is a Service Provider?
--------------------------
A service provider is any object that implements the ``Interop\Container\ServiceProviderInterface``. It must provide two methods:

- ``getFactories()``: returns an associative array of service IDs to factory callables.
- ``getExtensions()``: returns an associative array of service IDs to extension callables (optional, for decorating services after creation).

.. toctree::
   :maxdepth: 2

   factories
   using-providers
   custom-provider
   using-factories