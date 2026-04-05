FastForward Container
=====================

FastForward Container is a PSR-11 compliant composition layer for PHP applications.
It helps you combine service providers, existing PSR-11 containers, configuration-backed
containers, and autowiring behind one consistent entry point.

If you are new to the package, start with :doc:`getting-started/installation`,
then :doc:`getting-started/quickstart`, and finally :doc:`providers/index`.

Why teams usually adopt this package
------------------------------------

- You can assemble one application container from multiple sources instead of committing to a single registration style.
- You keep explicit registrations for configured services while still using autowiring for the rest of the object graph.
- You can plug in existing PSR-11 containers and FastForward configuration objects without custom adapters.

Useful links
------------

- `Repository <https://github.com/php-fast-forward/container>`_
- `Packagist <https://packagist.org/packages/fast-forward/container>`_
- `Issue tracker <https://github.com/php-fast-forward/container/issues>`_
- `API documentation build <https://php-fast-forward.github.io/container/docs/>`_

.. toctree::
   :maxdepth: 2
   :caption: Contents:

   getting-started/index
   providers/index
   examples/index
   advanced/index
   api/index
   links/index
   faq
   compatibility
