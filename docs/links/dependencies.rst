Dependencies
============

Runtime dependencies
--------------------

======================================  ========================  ===========================================
Package                                 Version                   Why this package is used
======================================  ========================  ===========================================
``php``                                 ``^8.3``                  Language baseline used by the package
``psr/container``                       ``^1.0 || ^2.0``          PSR-11 interfaces
``container-interop/service-provider``  ``^0.4.1``                Service provider contract used by built-in providers
``fast-forward/config``                 ``^1.1``                  Config-backed container integration
``php-di/php-di``                       ``^7.0``                  Autowiring engine used by ``AutowireContainer``
======================================  ========================  ===========================================

Development dependency
----------------------

===============================  ========================  ===========================================
Package                          Version                   Why it is used
===============================  ========================  ===========================================
``fast-forward/dev-tools``       ``dev-main``              Shared project tooling for quality checks
===============================  ========================  ===========================================

Related standards and libraries
-------------------------------

- `PSR-11 Container Interface <https://www.php-fig.org/psr/psr-11/>`_
- `PHP-DI <https://php-di.org/>`_
- `container-interop/service-provider on Packagist <https://packagist.org/packages/container-interop/service-provider>`_
- `fast-forward/config <https://packagist.org/packages/fast-forward/config>`_
