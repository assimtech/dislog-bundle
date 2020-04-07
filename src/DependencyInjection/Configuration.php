<?php

declare(strict_types=1);

namespace Assimtech\DislogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('assimtech_dislog');

        $rootNode
            ->children()
                ->scalarNode('api_call_factory')
                    ->defaultValue('assimtech_dislog.api_call.factory')
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
                                    ->defaultValue('assimtech_dislog.generator.unique_id')
                                ->end()
                                ->scalarNode('serializer')
                                    ->defaultValue('assimtech_dislog.serializer.string')
                                ->end()
                            ->end()
                        ->end()

                        // doctrine_document_manager options
                        ->arrayNode('doctrine_document_manager')
                            ->children()
                                ->scalarNode('document_manager')
                                    ->isRequired()
                                ->end()
                            ->end()
                        ->end()

                        // doctrine_entity_manager options
                        ->arrayNode('doctrine_entity_manager')
                            ->children()
                                ->scalarNode('entity_manager')
                                    ->isRequired()
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
                ->integerNode('max_age')
                    ->defaultValue(60 * 60 * 24 * 30) // 30 days
                ->end()
                ->arrayNode('preferences')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('suppress_handler_exceptions')
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
