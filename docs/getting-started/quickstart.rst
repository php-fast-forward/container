Quickstart
==========

This quickstart shows the shortest path from installation to a useful container.

Start without registrations
---------------------------

If your classes only need other concrete classes with inferable arguments, start
with ``AutowireContainer`` alone:

.. code-block:: php

   use FastForward\Container\AutowireContainer;

   final class HealthCheck {}

   final readonly class Application
   {
       public function __construct(public HealthCheck $healthCheck) {}
   }

   $standalone = new AutowireContainer();
   $application = $standalone->get(Application::class);

Both classes are constructed automatically. Repeated lookups in the same container
return the same successfully constructed instances.

Add configured services
-----------------------

We will register one configured dependency explicitly and let the container resolve the
rest of the object graph:

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use function FastForward\Container\container;

   final readonly class ApplicationName
   {
       public function __construct(
           public string $value,
       ) {}
   }

   final readonly class Greeter
   {
       public function __construct(
           private ApplicationName $applicationName,
       ) {}

       public function greet(string $name): string
       {
           return sprintf('Hello %s, welcome to %s.', $name, $this->applicationName->value);
       }
   }

   $provider = new ArrayServiceProvider([
       ApplicationName::class => static fn(): ApplicationName => new ApplicationName('FastForward Container'),
   ]);

   $container = container($provider);
   $greeter = $container->get(Greeter::class);

   echo $greeter->greet('team');
   // Hello team, welcome to FastForward Container.

What just happened?
-------------------

1. ``ArrayServiceProvider`` defined how the configured ``ApplicationName`` should be created.
2. ``container($provider)`` wrapped the provider in a PSR-11 container and then enabled autowiring on top of it.
3. ``Greeter`` was autowired and received the explicitly registered ``ApplicationName``.
4. Later calls to ``$container->get(Greeter::class)`` return the same cached instance.

Reuse an existing container
--------------------------

If another part of your application already provides a PSR-11 container, pass it
directly to ``AutowireContainer``. Here a provider-backed container exposes the
configured ``ApplicationName`` from the previous example:

.. code-block:: php

   use FastForward\Container\ServiceProviderContainer;

   $existingContainer = new ServiceProviderContainer($provider);
   $withDelegate = new AutowireContainer($existingContainer);
   $greeter = $withDelegate->get(Greeter::class);

   var_dump(
       $withDelegate->get(ApplicationName::class)
       === $existingContainer->get(ApplicationName::class)
   ); // true

The delegate's entries take precedence over autowiring. ``Greeter`` is built
automatically and receives the configured ``ApplicationName``. The same order
applies to optional object dependencies. In a framework application, pass the
framework's PSR-11 container in place of ``$existingContainer``.

What to read next
-----------------

- Read :doc:`basic-usage` to mix providers, configuration, and existing PSR-11 containers.
- Read :doc:`../advanced/autowire` for complete standalone and delegated examples.
- Read :doc:`../providers/index` to choose the right provider and factory style.
- Read :doc:`../examples/index` when you want real-world composition patterns.
