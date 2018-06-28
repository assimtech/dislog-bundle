<?php

namespace Assimtech\DislogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('assimtech_dislog');

        $rootNode
            ->children()
                ->scalarNode('api_call_factory')
                    ->defaultValue('Assimtech\Dislog\Factory\ApiCallFactory')
                ->end()
                ->arrayNode('handler')
                    ->isRequired()
                    ->children()
                        // stream options
                        ->arrayNode('stream')
                            ->children()
                                ->scalarNode('resource')
                                    ->isRequired()
                                ->end()
                                ->scalarNode('identity_generator')
                                    ->defaultValue('Assimtech\Dislog\Identity\UniqueIdGenerator')
                                ->end()
                                ->scalarNode('serializer')
                                    ->defaultValue('Assimtech\Dislog\Serializer\StringSerializer')
                                ->end()
                            ->end()
                        ->end()

                        // doctrine_object_manager options
                        ->arrayNode('doctrine_object_manager')
                            ->children()
                                ->scalarNode('object_manager')
                                    ->isRequired()
                                ->end()
                            ->end()
                        ->end()

                        // service options
                        ->arrayNode('service')
                            ->children()
                                ->scalarNode('name')
                                    ->isRequired()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(function ($v) {
                            return count($v) !== 1;
                        })
                        ->thenInvalid('A single handler section must be configured')
                    ->end()
                ->end()
                ->arrayNode('preferences')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('suppressHandlerExceptions')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('psr_logger')
                    ->defaultValue('logger')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
