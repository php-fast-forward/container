Error Reporting
===============

FastForward Container uses custom exception classes for error handling, all under the ``FastForward\Container\Exception`` namespace:

- ``ContainerException``: For general container errors (implements PSR-11 ``ContainerExceptionInterface``)
- ``NotFoundException``: Thrown when a service identifier is not found (implements PSR-11 ``NotFoundExceptionInterface``)
- ``InvalidArgumentException``: For invalid or unsupported arguments
- ``RuntimeException``: For runtime errors, such as non-callable extensions or non-public methods

Example:

.. code-block:: php

   use FastForward\Container\Exception\NotFoundException;

   try {
       $service = $container->get('unknown');
   } catch (NotFoundException $e) {
       // Handle missing service
   }
