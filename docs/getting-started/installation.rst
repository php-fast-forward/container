Installation
============

Install the package with Composer:

.. code-block:: bash

   composer require fast-forward/container

Requirements
------------

- PHP 8.3 or higher
- Composer

What Composer installs for you
------------------------------

FastForward Container depends on a small set of libraries that each solve one part of
the overall container workflow:

======================================  ================================================
Package                                 Why it is needed
======================================  ================================================
``psr/container``                       Common PSR-11 interfaces used across the package
``php-di/php-di``                       Autowiring engine used by ``AutowireContainer``
``container-interop/service-provider``  Provider contract used by built-in providers
``fast-forward/config``                 Optional configuration-backed container support
======================================  ================================================

You do not need to configure all of these pieces on day one. A beginner-friendly
starting point is to use ``ArrayServiceProvider`` together with the ``container()``
helper, then add configuration and external containers later.

After installation
------------------

Your next step is usually :doc:`quickstart`.

If you already know you want to build the container from configuration, also read
:doc:`basic-usage` after the quickstart because it explains the difference between:

- the raw keys stored in a ``ConfigInterface`` implementation
- the ``config.*`` service IDs exposed by ``ConfigContainer``

For more details, see the `Packagist page <https://packagist.org/packages/fast-forward/container>`_.
