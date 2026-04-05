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

use FastForward\Container\Factory\AliasFactory;
use FastForward\Container\Factory\CallableFactory;
use FastForward\Container\Factory\InvokableFactory;
use FastForward\Container\Factory\MethodFactory;
use FastForward\Container\Factory\ServiceFactory;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;

use function FastForward\Container\container;

require __DIR__ . '/bootstrap.php';

final readonly class ChannelName
{
    /**
     * @param string $value
     */
    public function __construct(
        public string $value,
    ) {}
}

final readonly class DeploymentStage
{
    /**
     * @param string $value
     */
    public function __construct(
        public string $value,
    ) {}
}

final readonly class FixedClock
{
    /**
     * @param string $timestamp
     */
    public function __construct(
        private string $timestamp,
    ) {}

    /**
     * @return string
     */
    public function now(): string
    {
        return $this->timestamp;
    }
}

final readonly class ReportGenerator
{
    /**
     * @param FixedClock $clock
     * @param ChannelName $channel
     */
    public function __construct(
        private FixedClock $clock,
        private ChannelName $channel,
    ) {}

    /**
     * @return string
     */
    public function summary(): string
    {
        return sprintf('%s report generated at %s', $this->channel->value, $this->clock->now());
    }
}

final readonly class DashboardWidget
{
    /**
     * @param string $reportSummary
     * @param string $generatedAt
     */
    public function __construct(
        private string $reportSummary,
        private string $generatedAt,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'reportSummary' => $this->reportSummary,
            'generatedAt' => $this->generatedAt,
        ];
    }
}

final readonly class DeploymentMetadata
{
    /**
     * @param string $stage
     * @param string $releaseChannel
     */
    public function __construct(
        private string $stage,
        private string $releaseChannel,
    ) {}

    /**
     * @param DeploymentStage $stage
     *
     * @return self
     */
    public static function fromStage(DeploymentStage $stage): self
    {
        return new self($stage->value, 'stable');
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'stage' => $this->stage,
            'releaseChannel' => $this->releaseChannel,
        ];
    }
}

exampleTitle(
    '06 Factory helpers',
    'Use built-in factory helpers to alias services, wrap instances, invoke constructors, call methods, and build services from typed callables.',
);

$provider = new ArrayServiceProvider([
    FixedClock::class => new ServiceFactory(new FixedClock('2026-04-04T12:00:00-03:00')),
    ChannelName::class => new ServiceFactory(new ChannelName('daily-ops')),
    DeploymentStage::class => new ServiceFactory(new DeploymentStage('production')),
    ReportGenerator::class => new InvokableFactory(ReportGenerator::class, FixedClock::class, ChannelName::class),
    'primary.report' => new AliasFactory(ReportGenerator::class),
    DashboardWidget::class => new CallableFactory(
        static fn(FixedClock $clock, ReportGenerator $report): DashboardWidget => new DashboardWidget(
            $report->summary(),
            $clock->now(),
        ),
    ),
    DeploymentMetadata::class => new MethodFactory(DeploymentMetadata::class, 'fromStage', DeploymentStage::class),
]);

$container = container($provider);
$report = $container->get('primary.report');

exampleValue('Alias returns same instance', $report === $container->get(ReportGenerator::class));
exampleValue('Report summary', $report->summary());
exampleValue('Widget', $container->get(DashboardWidget::class)->toArray());
exampleValue('Deployment metadata', $container->get(DeploymentMetadata::class)->toArray());
