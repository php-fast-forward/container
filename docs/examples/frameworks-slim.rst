Integrating with Slim Framework
==============================

Slim 4 accepts FastForward Container through its PSR-11 interface. This example
uses Slim 4 with ``slim/psr7`` as its PSR-7 implementation. Install the framework
packages alongside ``fast-forward/container``:

.. code-block:: shell

   composer require slim/slim:^4 slim/psr7:^1 psr/log:^3

After loading Composer's autoloader:

.. code-block:: php

   use function FastForward\Container\container;
   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use Psr\Http\Message\ResponseInterface;
   use Psr\Http\Message\ServerRequestInterface;
   use Psr\Log\LoggerInterface;
   use Psr\Log\NullLogger;
   use Slim\Factory\AppFactory;

   $provider = new ArrayServiceProvider([
       LoggerInterface::class => fn() => new NullLogger(),
   ]);
   $container = container($provider);

   $app = AppFactory::createFromContainer($container);
   $app->get('/ping', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
       $logger = $this->get(LoggerInterface::class);
       $logger->info('Ping route called');
       $response->getBody()->write(json_encode(['pong' => true], JSON_THROW_ON_ERROR));

       return $response->withHeader('Content-Type', 'application/json');
   });

Slim binds the route closure to the configured container, making ``$this->get()``
available inside it. Replace ``NullLogger`` with your application's logger.
Call ``$app->run()`` from the HTTP entry point to handle the incoming request.
