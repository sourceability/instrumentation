<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class SourceabilityInstrumentationExtension extends ConfigurableExtension
{
    /**
     * @param array<mixed> $config
     */
    protected function loadInternal(array $config, ContainerBuilder $container): void
    {
        /** @var array{listeners: array<string, array{enabled: bool}>, profilers: array<string, array{enabled: bool}>} $config */
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        if ($config['listeners']['command']['enabled']) {
            $loader->load('listener_command.yaml');
        }

        if ($config['listeners']['messenger']['enabled']) {
            $loader->load('listener_messenger.yaml');
        }

        if ($config['profilers']['datadog']['enabled']) {
            $loader->load('profiler_datadog.yaml');
        }

        if ($config['profilers']['newrelic']['enabled']) {
            $loader->load('profiler_newrelic.yaml');
        }

        if ($config['profilers']['symfony']['enabled']) {
            $loader->load('profiler_symfony.yaml');
        }

        if ($config['profilers']['tideways']['enabled']) {
            $loader->load('profiler_tideways.yaml');
        }

        if ($config['profilers']['spx']['enabled']) {
            $loader->load('profiler_spx.yaml');
        }

        if (interface_exists('Symfony\\Component\\Messenger\\Middleware\\MiddlewareInterface')) {
            $loader->load('messenger_middleware.yaml');
        }
    }
}
