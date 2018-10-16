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

        $container->setDefinition(
            'assimtech_dislog.api_call.factory',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();

        $container->setAlias(
            'Assimtech\Dislog\Factory\ApiCallFactory',
            'assimtech_dislog.api_call.factory'
        )->shouldBeCalled();

        $container->setDefinition(
            'assimtech_dislog.generator.unique_id',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();

        $container->setAlias(
            'Assimtech\Dislog\Identity\UniqueIdGenerator',
            'assimtech_dislog.generator.unique_id'
        )->shouldBeCalled();

        $container->setDefinition(
            'assimtech_dislog.serializer.string',
            Argument::type('Symfony\Component\DependencyInjection\Definition')
        )->shouldBeCalled();

        $container->setAlias(
            'Assimtech\Dislog\Serializer\StringSerializer',
            'assimtech_dislog.serializer.string'
        )->shouldBeCalled();
    }

    private function setupForLogger(
        ContainerBuilder $container,
        Definition $loggerDefinition
    ) {
        $container
            ->register('assimtech_dislog.logger', 'Assimtech\Dislog\ApiCallLogger')
            ->willReturn($loggerDefinition)
        ;

        $loggerDefinition->setArguments(array(
            new Reference('assimtech_dislog.api_call.factory'),
            new Reference('assimtech_dislog.handler'),
            array(
                'suppressHandlerExceptions' => true,
            ),
            new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
        ))->shouldBeCalled();

        $container->setAlias(
            'Assimtech\Dislog\ApiCallLoggerInterface',
            'assimtech_dislog.logger'
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
            ->register('assimtech_dislog.handler', 'Assimtech\Dislog\Handler\Stream')
            ->willReturn($handlerDefinition)
        ;
        $handlerDefinition->setArguments(array(
            $resource,
            new Reference('assimtech_dislog.generator.unique_id'),
            new Reference('assimtech_dislog.serializer.string'),
        ))->shouldBeCalled();

        $container->setAlias(
            'Assimtech\Dislog\Handler\HandlerInterface',
            'assimtech_dislog.handler'
        )->shouldBeCalled();

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
            ->register('assimtech_dislog.handler', 'Assimtech\Dislog\Handler\DoctrineObjectManager')
            ->willReturn($handlerDefinition)
        ;
        $handlerDefinition->setArguments(array(
            new Reference($objectManager),
        ))->shouldBeCalled();

        $container->setAlias(
            'Assimtech\Dislog\Handler\HandlerInterface',
            'assimtech_dislog.handler'
        )->shouldBeCalled();

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

        $container->setAlias('assimtech_dislog.handler', $serviceName)->shouldBeCalled();
        $container->setAlias(
            'Assimtech\Dislog\Handler\HandlerInterface',
            'assimtech_dislog.handler'
        )->shouldBeCalled();

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
