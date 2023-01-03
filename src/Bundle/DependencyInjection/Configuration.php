<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('sourceability_instrumentation');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('profilers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('tideways')
                            ->canBeEnabled()
                            ->info(
                                <<<'CODE_SAMPLE'
See https://support.tideways.com/documentation/features/application-monitoring/application-performance-overview.html
CODE_SAMPLE
                            )
                        ->end()
                        ->arrayNode('newrelic')
                            ->canBeEnabled()
                            ->info(
                                <<<'CODE_SAMPLE'
See https://docs.newrelic.com/docs/agents/php-agent/getting-started/introduction-new-relic-php/
This requires https://github.com/ekino/EkinoNewRelicBundle
CODE_SAMPLE
                            )
                        ->end()
                        ->arrayNode('datadog')
                            ->canBeEnabled()
                            ->info(
                                <<<'CODE_SAMPLE'
See https://docs.datadoghq.com/tracing/setup_overview/setup/php/
CODE_SAMPLE
                            )
                        ->end()
                        ->arrayNode('symfony')
                            ->canBeEnabled()
                            ->info(
                                <<<'CODE_SAMPLE'
This "hacks" the symfony web profiler to create profiles in non web contexts like workers, commands.
This is really useful for development along with https://github.com/sourceability/console-toolbar-bundle
CODE_SAMPLE
                            )
                        ->end()
                        ->arrayNode('spx')
                            ->canBeEnabled()
                            ->info(<<<'CODE_SAMPLE'
See https://github.com/NoiseByNorthwest/php-spx
CODE_SAMPLE
                            )
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('listeners')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('command')
                            ->canBeEnabled()
                            ->info('Automatically instrument commands')
                        ->end()
                        ->arrayNode('messenger')
                            ->canBeEnabled()
                            ->info('Automatically instrument messenger workers')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
