<?php

namespace spec\Assimtech\DislogBundle\DependencyInjection\Compiler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ProcessorCompilerPassSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Assimtech\DislogBundle\DependencyInjection\Compiler\ProcessorCompilerPass');
    }

    function it_can_process(ContainerBuilder $container, Definition $loggerDefinition)
    {
        $alias = 'my.processor';
        $serviceId = 'my_bundle.dislog_processor';

        $container->getDefinition('Assimtech\Dislog\ApiCallLoggerInterface')->willReturn($loggerDefinition);

        $taggedServices = array(
            $serviceId => array(
                array(
                    'alias' => $alias,
                ),
            ),
        );

        $container->findTaggedServiceIds('assimtech_dislog.processor')->willReturn($taggedServices);
        $loggerDefinition->addMethodCall('setAliasedProcessor', array(
            $alias,
            new Reference($serviceId)
        ))->shouldBeCalled();

        $this->process($container);
    }
}
