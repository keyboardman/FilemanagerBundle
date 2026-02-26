<?php

namespace Keyboardman\FilemanagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('keyboardman_filemanager');
        $root = $treeBuilder->getRootNode();

        $root
            ->children()

                ->arrayNode('disks')
                    ->info('Logical disks that map to Flysystem storages')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()

                            ->scalarNode('storage')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->info('Service id of Flysystem storage (e.g. local.storage)')
                            ->end()

                            ->scalarNode('label')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()

                            ->scalarNode('visibility')
                                ->defaultValue('public')
                                ->validate()
                                    ->ifNotInArray(['public', 'private'])
                                    ->thenInvalid('Visibility must be "public" or "private"')
                                ->end()
                            ->end()

                            ->booleanNode('signed_urls')
                                ->defaultFalse()
                            ->end()

                            ->scalarNode('default_uri')
                                ->defaultNull()
                            ->end()

                        ->end() // end children()
                    ->end()     // end arrayPrototype()
                ->end()         // end disks
                ->arrayNode('iframe')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->variableNode('tokens') // <--- change arrayNode -> variableNode
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
