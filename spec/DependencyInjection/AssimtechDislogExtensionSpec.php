<?php

namespace spec\Assimtech\DislogBundle\DependencyInjection;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class AssimtechDislogExtensionSpec extends ObjectBehavior
{
    private function setupForLoad(ContainerBuilder $container)
    {
        $container
            ->fileExists(Argument::containingString('Resources/config/services.yaml'))
            ->willReturn(true)
        ;

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
            'Assimtech\Dislog\Factory\ApiCallFactory',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();

        $container->setAlias(
            'assimtech_dislog.api_call.factory',
            'Assimtech\Dislog\Factory\ApiCallFactory'
        )->shouldBeCalled();

        $container->setDefinition(
            'Assimtech\Dislog\Identity\UniqueIdGenerator',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();

        $container->setAlias(
            'assimtech_dislog.generator.unique_id',
            'Assimtech\Dislog\Identity\UniqueIdGenerator'
        )->shouldBeCalled();

        $container->setDefinition(
            'Assimtech\Dislog\Serializer\StringSerializer',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();

        $container->setAlias(
            'assimtech_dislog.serializer.string',
            'Assimtech\Dislog\Serializer\StringSerializer'
        )->shouldBeCalled();
    }

    private function setupForLogger(
        ContainerBuilder $container,
        Definition $loggerDefinition
    ) {
        $container
            ->register('Assimtech\Dislog\ApiCallLoggerInterface', 'Assimtech\Dislog\ApiCallLogger')
            ->willReturn($loggerDefinition)
        ;

        $loggerDefinition->setArguments(array(
            new Reference('Assimtech\Dislog\Factory\ApiCallFactory'),
            new Reference('Assimtech\Dislog\Handler\HandlerInterface'),
            array(
                'suppressHandlerExceptions' => true,
            ),
            new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
        ))->shouldBeCalled();

        $container->setAlias(
            'assimtech_dislog.logger',
            'Assimtech\Dislog\ApiCallLoggerInterface'
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

        $this->setupForLoad($container);

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

    function it_can_load_stream(
        ContainerBuilder $container,
        Definition $handlerDefinition,
        Definition $loggerDefinition
    ) {
        $resource = 'php://temp';

        $this->setupForLoad($container);
        $this->setupForLogger($container, $loggerDefinition);

        $container
            ->register('Assimtech\Dislog\Handler\HandlerInterface', 'Assimtech\Dislog\Handler\Stream')
            ->willReturn($handlerDefinition)
        ;

        $handlerDefinition->setArguments(array(
            $resource,
            new Reference('Assimtech\Dislog\Identity\UniqueIdGenerator'),
            new Reference('Assimtech\Dislog\Serializer\StringSerializer'),
        ))->shouldBeCalled();

        $configs = array(
            'assimtech_dislog' => array(
                'handler' => array(
                    'stream' => array(
                        'resource' => $resource,
                    ),
                ),
            ),
        );

        $this->load($configs, $container);
    }

    function it_can_load_doctrine_object_manager(
        ContainerBuilder $container,
        Definition $handlerDefinition,
        Definition $loggerDefinition
    ) {
        $objectManager = 'doctrine.object.mnager';

        $this->setupForLoad($container);
        $this->setupForLogger($container, $loggerDefinition);

        $container
            ->register('Assimtech\Dislog\Handler\HandlerInterface', 'Assimtech\Dislog\Handler\DoctrineObjectManager')
            ->willReturn($handlerDefinition)
        ;
        $handlerDefinition->setArguments(array(
            new Reference($objectManager),
        ))->shouldBeCalled();

        $configs = array(
            'assimtech_dislog' => array(
                'handler' => array(
                    'doctrine_object_manager' => array(
                        'object_manager' => $objectManager,
                    ),
                ),
            ),
        );

        $this->load($configs, $container);
    }

    function it_can_load_service(
        ContainerBuilder $container,
        Definition $loggerDefinition
    ) {
        $serviceName = 'my.service';

        $this->setupForLoad($container);
        $this->setupForLogger($container, $loggerDefinition);

        $container->setAlias('Assimtech\Dislog\Handler\HandlerInterface', $serviceName)->shouldBeCalled();

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
