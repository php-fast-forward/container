Autowire
========

FastForward Container implements constructor autowiring using native PHP reflection.
``AutowireContainer`` appends a reflection resolver to an aggregate container, after
the explicitly registered sources. No external autowiring engine is required.

How It Works
------------
- Services registered in the aggregate container are resolved first.
- If a service is not found, the reflection resolver attempts to autowire it.
- This allows seamless use of both explicit and autowired services.
- Nested constructor dependencies resolve through the complete aggregate, including
  other providers, configuration containers and explicit PSR-11 sources.
- Successful resolutions are cached; separate builder builds have separate caches.

Usage Example
-------------

.. code-block:: php

   use FastForward\Container\AutowireContainer;
   use FastForward\Container\AggregateContainer;

   $aggregate = new AggregateContainer($providerContainer);
   $container = new AutowireContainer($aggregate);

   $service = $container->get(MyService::class);

You can also use the ``container()`` helper to automatically build an autowire-enabled container from providers, configs, or other containers.

For constructor autowiring without explicit registrations, use
``new AutowireContainer(new AggregateContainer())``.

The reflection resolver is an internal implementation detail. ``AutowireContainer``
connects it to the same aggregate used for nested dependency lookups. This connection
allows recursive autowiring through the complete container composition.

Optional Dependencies
---------------------

Named object dependencies are looked up before their declared defaults, even when
the argument is optional:

.. code-block:: php

   use Psr\Log\LoggerInterface;

   final class Handler
   {
       public function __construct(public ?LoggerInterface $logger = null) {}
   }

If ``LoggerInterface::class`` is registered, its entry is injected. An optional
concrete class is recursively autowired when possible. If the dependency is absent
or its constructor graph requires arguments that cannot be inferred, the declared
default is used. This also applies when the default is an object instead of ``null``.

A registered ``null`` is a real entry and overrides an object default for a nullable
argument. A nullable argument without a default is still required: ``?LoggerInterface
$logger`` does not imply a fallback to ``null``.

Constructor/factory failures, incompatible registered values and dependency cycles
are errors. They do not trigger optional defaults. Cycles include the construction
path in the exception; failed resolutions are not cached and may be retried.

Supported Parameters
---------------------

- Required and optional named class/interface types resolve by their type identifier.
- ``self`` and ``parent`` resolve in the constructor's declaring-class scope,
  including inherited constructors.
- Scalar and untyped parameters use their declared defaults. Required values need
  an explicit factory for the containing service; parameter names are not service IDs.
- Union, intersection and DNF types use their defaults or require an explicit factory.
  The resolver does not guess which member of an ambiguous type to inject.
- Variadic parameters receive no implicit entries. Register an explicit factory
  when a service needs a collection.
- Non-instantiable classes, interfaces, traits and enums need explicit registrations.

Migration from Internal PHP-DI
------------------------------

The public construction APIs stay the same. Applications using PHP-DI directly
must declare their own Composer dependency; an installed PHP-DI container can still
be supplied as a normal PSR-11 source.

The intentional behavior changes are optional object injection before defaults and
preservation of the first delegated construction error when aggregate fallback is
exhausted. Previously those failures could appear as a fresh not-found exception.

``AutowireContainer::has()`` retains its existing eager behavior: it attempts
construction and returns ``false`` on failure. Calling it can run constructors and
factories. Internal constructor dependency resolution does not use this eager check.

The native resolver supports constructor injection. It does not implement PHP-DI's
definition DSL, attribute/property/setter injection, proxies or compilation.
