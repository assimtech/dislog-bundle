<?php

namespace Assimtech\DislogBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Loads services tagged with name: assimtech_dislog.processor into the assimtech_dislog.logger
 */
class ProcessorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $loggerDefinition = $container->getDefinition(
            'Assimtech\Dislog\ApiCallLoggerInterface'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'assimtech_dislog.processor'
        );
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $loggerDefinition->addMethodCall(
                    'setAliasedProcessor',
                    array($attributes['alias'], new Reference($id))
                );
            }
        }
    }
}
