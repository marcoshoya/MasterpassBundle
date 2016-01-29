<?php

namespace Hoya\MasterpassBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    protected static $ck = 'cLb0tKkEJhGTITp_6ltDIibO5Wgbx4rIldeXM_jRd4b0476c!414f4859446c4a366c726a327474695545332b353049303d';
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder
            ->root('hoya_masterpass')
            ->children()
                ->scalarNode('checkoutidentifier')->defaultValue('a4a6x1ywxlkxzhensyvad1hepuouaesuv')->end()
                ->arrayNode('sandbox')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('consumerkey')->defaultValue(self::$ck)->end()
                        ->scalarNode('password')->defaultValue('changeit')->end()
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
