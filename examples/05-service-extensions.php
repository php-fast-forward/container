<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/container.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2025-2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/container
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use Psr\Container\ContainerInterface;

use function FastForward\Container\container;

require __DIR__ . '/bootstrap.php';

final readonly class ApiToken
{
    /**
     * @param string $value
     */
    public function __construct(
        private string $value,
    ) {}

    /**
     * @return string
     */
    public function toBearer(): string
    {
        return 'Bearer ' . $this->value;
    }
}

final class ApiClient
{
    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * @param string $baseUrl
     */
    public function __construct(
        private readonly string $baseUrl,
    ) {}

    /**
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function addHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * @param int $seconds
     *
     * @return void
     */
    public function setTimeout(int $seconds): void
    {
        $this->headers['X-Timeout'] = (string) $seconds;
    }

    /**
     * @return array<string, mixed>
     */
    public function describe(): array
    {
        return [
            'baseUrl' => $this->baseUrl,
            'headers' => $this->headers,
        ];
    }
}

exampleTitle(
    '05 Service extensions',
    'Decorate a service after construction to attach runtime configuration and cross-cutting behavior.',
);

$provider = new ArrayServiceProvider(
    [
        ApiToken::class => static fn(): ApiToken => new ApiToken('demo-token'),
        ApiClient::class => static fn(): ApiClient => new ApiClient('https://api.fastforward.local'),
    ],
    [
        ApiClient::class => static function (ContainerInterface $container, ApiClient $client): ApiClient {
            $client->addHeader('Authorization', $container->get(ApiToken::class)->toBearer());
            $client->setTimeout(3);

            return $client;
        },
    ],
);

$container = container($provider);
$client = $container->get(ApiClient::class);

exampleValue('Configured client', $client->describe());
