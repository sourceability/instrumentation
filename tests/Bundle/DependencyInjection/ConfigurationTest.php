<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\Test\Bundle\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Sourceability\Instrumentation\Bundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

/**
 * @internal
 * @coversNothing
 */
final class ConfigurationTest extends TestCase
{
    public function testEmptyConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, []);

        static::assertArrayHasKey('profilers', $config);
        static::assertArrayHasKey('listeners', $config);

        static::assertArrayHasKey('tideways', $config['profilers']);
        static::assertArrayHasKey('enabled', $config['profilers']['tideways']);
        static::assertFalse($config['profilers']['tideways']['enabled']);

        static::assertArrayHasKey('newrelic', $config['profilers']);
        static::assertArrayHasKey('enabled', $config['profilers']['newrelic']);
        static::assertFalse($config['profilers']['newrelic']['enabled']);

        static::assertArrayHasKey('datadog', $config['profilers']);
        static::assertArrayHasKey('enabled', $config['profilers']['datadog']);
        static::assertFalse($config['profilers']['datadog']['enabled']);

        static::assertArrayHasKey('symfony', $config['profilers']);
        static::assertArrayHasKey('enabled', $config['profilers']['symfony']);
        static::assertFalse($config['profilers']['symfony']['enabled']);

        static::assertArrayHasKey('command', $config['listeners']);
        static::assertArrayHasKey('enabled', $config['listeners']['command']);
        static::assertFalse($config['listeners']['command']['enabled']);

        static::assertArrayHasKey('messenger', $config['listeners']);
        static::assertArrayHasKey('enabled', $config['listeners']['messenger']);
        static::assertFalse($config['listeners']['messenger']['enabled']);
    }
}
