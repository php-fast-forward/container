Autowire
========

``AutowireContainer`` builds classes from their constructor types. You can use it
on its own or pass an existing PSR-11 container whose entries are checked first.
No external autowiring engine is required.

How It Works
------------
- Already resolved entries are returned from the cache.
- If a delegate was supplied, its entries are looked up before autowiring.
- Concrete classes that still need to be resolved are built from their constructor types.
- Constructor dependencies follow this same order, including optional dependencies.
- Each standalone container has its own cache of successfully constructed services.

Autowiring without registrations
--------------------------------

When all dependencies are concrete classes with inferable arguments, no provider
or delegate is needed:

.. code-block:: php

   use FastForward\Container\AutowireContainer;

   final readonly class MessageSender
   {
       public function __construct(public string $channel = 'default') {}
   }

   final readonly class NotificationService
   {
       public function __construct(public MessageSender $sender) {}
   }

   $container = new AutowireContainer();
   $service = $container->get(NotificationService::class);

   echo $service->sender->channel; // default

Passing ``null`` explicitly has the same effect. Repeated lookups return the same
successfully constructed instance within that container.

Adding autowiring to an existing container
----------------------------------------

Pass any object implementing ``Psr\Container\ContainerInterface`` to reuse its
registrations. In this example, a provider-backed container supplies a configured
``MessageSender``:

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\ServiceProviderContainer;

   $sender = new MessageSender('configured');
   $existingContainer = new ServiceProviderContainer(new ArrayServiceProvider([
       MessageSender::class => static fn(): MessageSender => $sender,
   ]));

   $container = new AutowireContainer($existingContainer);
   $service = $container->get(NotificationService::class);

   var_dump($service->sender === $sender); // true
   var_dump($container->get(MessageSender::class) === $sender); // true

The configured sender wins even though its class could be autowired. The same
priority applies to direct lookups, nested dependencies and optional object
parameters. ``NotificationService`` is built automatically because the delegate
does not define it. Your framework's existing PSR-11 container can be passed in
the same way.

For several PSR-11 containers, pass ``new AggregateContainer($first, $second)``;
they are consulted in order before native autowiring. To combine providers,
configuration and containers directly, use ``container(...)``,
``ContainerFactory::create(...)`` or ``ContainerBuilder``; see
:doc:`container-helper`. An existing ``AggregateContainer`` is reused and extended
with autowiring.

Register sources before the first lookup: successful resolutions are cached.
If a delegate's ``get()`` throws a PSR-11 exception, aggregate fallback may try
another source. If every source fails, construction errors are preserved; see
:doc:`special-patterns` for the full fallback rules.

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
