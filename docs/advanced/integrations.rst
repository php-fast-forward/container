Integrations
============

FastForward Container is designed to sit in the middle of an application instead of forcing
you to replace every existing dependency registration approach.

PSR-11 containers
-----------------

Any object implementing ``Psr\Container\ContainerInterface`` can be passed directly to
``container()`` or appended to ``AggregateContainer``.

This is the easiest integration path when:

- your framework already provides a container
- you are gradually migrating from another dependency injection solution
- one subsystem already exposes services through PSR-11

FastForward Config
------------------

If you use ``fast-forward/config``, the package can expose configuration values through
``config.*`` service IDs and can also discover nested providers from the raw config data.

The common pattern is:

1. store nested providers under ``FastForward\Container\ContainerInterface::class`` in the raw config
2. access normal config values at runtime through IDs such as ``config.app.name``

See :doc:`container-helper` for the full flow.

Autowiring with PHP-DI
----------------------

``AutowireContainer`` uses `PHP-DI <https://php-di.org/>`_ internally to instantiate classes
that are not already available through the explicit registrations you provided.

This works best when:

- constructor dependencies are classes or interfaces
- scalar configuration values are supplied through normal provider registrations
- services that need runtime configuration are registered explicitly instead of relying on bare autowiring

Frameworks and application shells
---------------------------------

The package does not require a specific framework. You can use it in:

- console applications
- HTTP applications
- libraries that need an internal composition root
- framework adapters

For concrete examples, see:

- :doc:`../examples/frameworks-slim`
- :doc:`../examples/frameworks-laravel`

Practical advice
----------------

- Keep configured services explicit in providers.
- Let autowiring handle the classes whose dependencies are already known by type.
- Prefer passing existing PSR-11 containers directly instead of wrapping them manually.
- When onboarding new team members, document which services are explicit and which are intentionally autowired.
