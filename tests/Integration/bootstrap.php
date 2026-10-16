<?php

declare(strict_types=1);

$consumerAutoload = getenv('CONTAINER_CONSUMER_AUTOLOAD');

require false === $consumerAutoload ? dirname(__DIR__, 2) . '/vendor/autoload.php' : $consumerAutoload;
