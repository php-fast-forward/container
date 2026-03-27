Autowire
========

FastForward Container supports autowiring via integration with `PHP-DI <https://php-di.org/>`. The ``AutowireContainer`` wraps an aggregate container and appends a PHP-DI container for automatic dependency resolution.

How It Works
------------
- Services registered in the aggregate container are resolved first.
- If a service is not found, the PHP-DI container attempts to autowire it.
- This allows seamless use of both explicit and autowired services.

Usage Example
-------------

.. code-block:: php

   use FastForward\Container\AutowireContainer;
   use FastForward\Container\AggregateContainer;

   $aggregate = new AggregateContainer($providerContainer);
   $container = new AutowireContainer($aggregate);

   $service = $container->get(MyService::class);

You can also use the ``container()`` helper to automatically build an autowire-enabled container from providers, configs, or other containers.