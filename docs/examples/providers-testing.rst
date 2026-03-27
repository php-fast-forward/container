Testing with Providers
====================

This example shows how to use providers to swap implementations for testing.

.. code-block:: php

   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\Container\container;

   class DummyMailer {
       public function send($to, $msg) { /* test logic */ }
   }

   $testProvider = new ArrayServiceProvider([
       'mailer' => fn() => new DummyMailer(),
   ]);

   $container = container($testProvider);
   $mailer = $container->get('mailer');
   $mailer->send('test@example.com', 'Hello!');
