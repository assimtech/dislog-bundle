<?php

namespace spec\Assimtech\DislogBundle\DependencyInjection;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class AssimtechDislogExtensionSpec extends ObjectBehavior
{
    private function setupContainer(ContainerBuilder $container)
    {
        $container->addResource(
            Argument::type('Symfony\Component\Config\Resource\FileResource')
        )->shouldBeCalled();

        $container->setParameter(
            'assimtech_dislog.api_call.factory.class',
            'Assimtech\Dislog\Factory\ApiCallFactory'
        )->shouldBeCalled();

        $container->setParameter(
            'assimtech_dislog.generator.unique_id.class',
            'Assimtech\Dislog\Identity\UniqueIdGenerator'
        )->shouldBeCalled();

        $container->setParameter(
            'assimtech_dislog.serializer.string.class',
            'Assimtech\Dislog\Serializer\StringSerializer'
        )->shouldBeCalled();

        $container->setParameter(
            'assimtech_dislog.processor.regex_replace.class',
            'Assimtech\Dislog\Processor\RegexReplace'
        )->shouldBeCalled();

        $container->setParameter(
            'assimtech_dislog.processor.string_replace.class',
            'Assimtech\Dislog\Processor\StringReplace'
        )->shouldBeCalled();

        $container->setDefinition(
            'assimtech_dislog.api_call.factory',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();

        $container->setDefinition(
            'assimtech_dislog.generator.unique_id',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();

        $container->setDefinition(
            'assimtech_dislog.serializer.string',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();

        $container->setDefinition(
            'assimtech_dislog.logger',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Assimtech\DislogBundle\DependencyInjection\AssimtechDislogExtension');
    }

    function it_cant_load_without_a_handler(ContainerBuilder $container)
    {
        $configs = array(
            'assimtech_dislog' => array(
                'handler' => array(
                ),
            ),
        );

        $this
            ->shouldThrow(new InvalidConfigurationException(
                'Invalid configuration for path "assimtech_dislog.handler": A single handler section must be configured'
            ))
            ->during('load', array(
                $configs,
                $container,
            ))
        ;
    }

    function it_can_load_stream(ContainerBuilder $container)
    {
        $this->setupContainer($container);

        $container->setDefinition(
            'assimtech_dislog.handler',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();

        $configs = array(
            'assimtech_dislog' => array(
                'handler' => array(
                    'stream' => array(
                        'resource' => 'php://temp',
                    ),
                ),
            ),
        );

        $this->load($configs, $container);
    }

    function it_can_load_doctrine_object_manager(ContainerBuilder $container)
    {
        $this->setupContainer($container);

        $container->setDefinition(
            'assimtech_dislog.handler',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();

        $configs = array(
            'assimtech_dislog' => array(
                'handler' => array(
                    'doctrine_object_manager' => array(
                        'object_manager' => 'doctrine.object.mnager',
                    ),
                ),
            ),
        );

        $this->load($configs, $container);
    }

    function it_can_load_service(ContainerBuilder $container)
    {
        $this->setupContainer($container);

        $serviceName = 'my.service';

        $container->setAlias('assimtech_dislog.handler', $serviceName)->shouldBeCalled();

        $configs = array(
            'assimtech_dislog' => array(
                'handler' => array(
                    'service' => array(
                        'name' => $serviceName,
                    ),
                ),
            ),
        );

        $this->load($configs, $container);
    }
}
