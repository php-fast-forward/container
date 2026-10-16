FAQ
===

Where should I start if I am completely new to this package?
------------------------------------------------------------

Start with :doc:`getting-started/quickstart`. It introduces standalone autowiring,
then adds configured services through a provider.
See :doc:`advanced/autowire` for reusing an existing PSR-11 container.

Can I use autowiring without a provider or another container?
-----------------------------------------------------------

Yes. ``new AutowireContainer()`` recursively builds concrete classes whose
constructor arguments can be inferred. Passing ``null`` explicitly has the same
effect. Interfaces need explicit bindings; services with required scalar arguments
need an explicit factory. See :doc:`advanced/autowire` for a complete example.

Do existing registrations take precedence over autowiring?
--------------------------------------------------------

Yes. ``new AutowireContainer($existingContainer)`` checks the delegate before
autowiring. This applies both to direct lookups and to constructor dependencies,
including optional object parameters. A configured instance can therefore override
a class that could otherwise be built automatically. When combining several sources
with ``container(...)``, earlier sources take precedence. Successful resolutions are
cached; configure sources before resolving services. See :doc:`advanced/special-patterns`
for fallback behavior when a delegated lookup fails.

Do I have to register every class manually?
-------------------------------------------

No. You usually register only the parts that need runtime configuration or special
construction logic. The final container is autowire-enabled, so classes whose constructor
dependencies are already known by type can often be created automatically.

When should I use a provider instead of an existing PSR-11 container?
---------------------------------------------------------------------

Use a provider when you want to declare services in package or application code. Use an
existing PSR-11 container when another framework or subsystem already owns those services
and you want FastForward Container to compose with it instead of replacing it.

Why do config values use ``config.*`` but provider registration uses ``ContainerInterface::class``?
----------------------------------------------------------------------------------------------------

The raw configuration object and the container view are different layers.

- In the raw config object, nested providers are stored under ``FastForward\Container\ContainerInterface::class``.
- Once the config is wrapped by ``ConfigContainer``, normal config values are exposed through service IDs such as ``config.app.name``.

This is why you do not store raw values under ``config.*`` keys yourself.

Why does ``CallableFactory`` throw an exception when my closure expects a string?
---------------------------------------------------------------------------------

``CallableFactory`` resolves parameters by class or interface type. Builtin types such as
``string``, ``int``, and ``array`` are not container-resolvable in that factory, so it throws
``RuntimeException``. If you need scalar values, use ``InvokableFactory``, ``ServiceFactory``,
or a callable that reads those values from another typed service.

How do I expose the same service under two IDs?
-----------------------------------------------

Use ``AliasFactory`` when both IDs should resolve to the same object. If you already created
the object yourself and want multiple IDs, you can also register the same instance through
``ServiceFactory`` in more than one place, but ``AliasFactory`` usually keeps the intent clearer.

How do I decorate a service after it is created?
------------------------------------------------

Add an extension in your provider. Extensions receive the container and the previously
created service instance, which lets you attach collaborators, runtime options, or
cross-cutting behavior after construction.

How do collisions work when multiple providers define the same ID?
------------------------------------------------------------------

It depends on how you combine them:

- ``container($providerA, $providerB)``: the first matching provider wins
- ``new AggregateServiceProvider($providerA, $providerB)``: later factory keys overwrite earlier ones

This difference is intentional, so choose the model that matches your override strategy.

Can I resolve the container itself from inside a factory?
---------------------------------------------------------

Yes. ``AggregateContainer`` registers itself under several aliases, including ``container``,
``FastForward\Container\ContainerInterface``, and ``Psr\Container\ContainerInterface``.

When should I use ``prepend()`` or ``append()`` on ``AggregateContainer``?
---------------------------------------------------------------------------

Use ``prepend()`` when you want a new container to override existing registrations. Use
``append()`` when you want a fallback container that is only consulted after the current
resolution chain is exhausted.

Why does ``AutowireContainer::has()`` sometimes return ``false`` even when a delegate says ``true``?
-----------------------------------------------------------------------------------------------------

``AutowireContainer`` performs a stricter check than a plain delegated ``has()`` call. It tries
to resolve the service and returns ``false`` if construction throws. This avoids reporting a
service as available when it is declared but not actually buildable.

Why is my optional dependency injected instead of staying null?
----------------------------------------------------------------

The native constructor resolver tries registered entries and concrete-class
autowiring before selecting an optional default. A default is used only when the
entry or inferable dependency graph is unavailable. Construction failures and
cycles still throw. See :doc:`advanced/autowire` for the complete rules.

What is the safest beginner-friendly default for a new project?
---------------------------------------------------------------

Start with one ``ArrayServiceProvider``, build the container through ``container($provider)``,
register scalar configuration explicitly, and allow autowiring to fill in the rest. This
approach keeps the learning curve low while still matching the package's long-term design.
