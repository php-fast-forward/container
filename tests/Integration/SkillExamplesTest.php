<?php

declare(strict_types=1);

namespace FastForward\Container\Tests\Integration;

use RuntimeException;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

use function FastForward\Container\container;

/**
 * Exercises the portable examples as consumer CLI programs.
 */
#[CoversNothing]
final class SkillExamplesTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}> executable example and expected result
     */
    public static function examples(): iterable
    {
        yield 'configuration' => ['config.php', 'configuration: ok'];
        yield 'composition' => ['composition.php', 'composition: ok'];
        yield 'autowiring' => ['autowiring.php', 'autowiring: ok'];
        yield 'provider corrections' => ['provider-resolution.php', 'provider-resolution: ok'];
        yield 'provider composition' => ['provider-composition.php', 'provider-composition: ok'];
    }

    /**
     * @param string $file the package example
     * @param string $expected its success message
     */
    #[DataProvider('examples')]
    public function testExampleAgainstConsumerAutoloader(string $file, string $expected): void
    {
        $root = \dirname(__DIR__, 2);
        $php = false === php_ini_loaded_file() ? [\PHP_BINARY, '-n'] : [\PHP_BINARY];
        $process = proc_open([
            ...$php,
            $root . '/resources/skills/fast-forward-container/examples/' . $file,
            $root . '/vendor/autoload.php',
        ], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, sys_get_temp_dir());
        self::assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        self::assertSame(0, proc_close($process), (string) $error);
        self::assertSame($expected . \PHP_EOL, $output);
    }

    /**
     * @return void
     */
    public function testProviderMetadataFailuresDoNotTryLaterSources(): void
    {
        $failure = new RuntimeException('Provider metadata failed.');
        $provider = self::createStub(ServiceProviderInterface::class);
        $provider->method('getFactories')
            ->willThrowException($failure);
        $fallbackUsed = false;
        $fallback = new ArrayServiceProvider([
            'entry' => static function () use (&$fallbackUsed): string {
                $fallbackUsed = true;

                return 'fallback';
            },
        ]);

        try {
            container($provider, $fallback)->get('entry');
            self::fail('Provider metadata failure must stop the lookup.');
        } catch (ContainerException $containerException) {
            self::assertSame($failure, $containerException->getPrevious());
        }

        self::assertFalse($fallbackUsed);
    }
}
