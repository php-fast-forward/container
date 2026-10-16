Compatibility
=============

Runtime compatibility
---------------------

===============================  ================================================
Item                             Compatibility
===============================  ================================================
PHP                              ``^8.3``
PSR containers                   ``psr/container`` ``^1.0`` or ``^2.0``
Service providers                ``container-interop/service-provider`` ``^0.4.1``
Autowiring engine                Native PHP reflection (included)
FastForward configuration        ``fast-forward/config`` ``^1.1``
===============================  ================================================

What this means in practice
---------------------------

- The package is designed for modern PHP projects running PHP 8.3 or newer.
- You can compose it with PSR-11 compatible containers from other libraries and frameworks.
- Constructor autowiring is built in and requires no external DI engine.
- Configuration-backed setups work out of the box with ``fast-forward/config``.

Integration guidance
--------------------

- Prefer explicit provider registrations for services that need scalar configuration.
- Prefer autowiring for classes whose dependencies are already known by type.
- Prefer passing external PSR-11 containers directly instead of adapting them manually unless your project needs custom behavior.
