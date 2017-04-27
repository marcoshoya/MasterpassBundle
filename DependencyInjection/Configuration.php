<?php

namespace Hoya\MasterpassBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder
            ->root('hoya_masterpass')
            ->children()
                ->booleanNode('production_mode')->defaultFalse()->end()
                ->scalarNode('callback')->defaultNull()->end()
                ->scalarNode('origin_url')->defaultNull()->end()
                ->scalarNode('checkoutidentifier')->defaultNull()->end()
                ->arrayNode('keys')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('sandbox')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('consumerkey')->defaultNull()->end()
                                ->scalarNode('keystorepath')->defaultNull()->end()
                                ->scalarNode('keystorepassword')->defaultNull()->end()
                            ->end()
                        ->end()
                        ->arrayNode('production')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('consumerkey')->defaultNull()->end()
                                ->scalarNode('keystorepath')->defaultNull()->end()
                                ->scalarNode('keystorepassword')->defaultNull()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
