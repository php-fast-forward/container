Compatibility
=============

Runtime compatibility
---------------------

===============================  ================================================
Item                             Compatibility
===============================  ================================================
PHP                              ``^8.3``
PSR containers                   ``psr/container`` ``^1.1`` or ``^2.0``
Service providers                ``container-interop/service-provider`` ``^0.4.1``
Autowiring engine                Native PHP reflection (included)
FastForward configuration        ``fast-forward/config`` ``^1.6``
===============================  ================================================

What this means in practice
---------------------------

- The package is designed for modern PHP projects running PHP 8.3 or newer.
- You can compose it with PSR-11 compatible containers from other libraries and frameworks.
- Constructor autowiring is built in and requires no external DI engine.
- Configuration-backed setups work out of the box with ``fast-forward/config``.

The minimum dependency versions are intentional. PSR container 1.0 has incompatible
method signatures. FastForward Config 1.6 provides the ``config.*`` entry namespace
and configuration-driven provider lists used here, with native configuration
storage that no longer depends on ``dflydev/dot-access-data``.
Production consumer checks exercise the minimum versions and current dependencies
without installing development tools into the consumer.

Integration guidance
--------------------

- Prefer explicit provider registrations for services that need scalar configuration.
- Prefer autowiring for classes whose dependencies are already known by type.
- Prefer passing external PSR-11 containers directly instead of adapting them manually unless your project needs custom behavior.
