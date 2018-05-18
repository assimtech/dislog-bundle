<?php

namespace Assimtech\DislogBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AssimtechDislogExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this
            ->createHandlerDefinition($config, $container)
            ->createLoggerDefinition($config, $container)
        ;
    }

    protected function createHandlerDefinition($config, ContainerBuilder $container)
    {
        $handlerServiceId = 'assimtech_dislog.handler';

        $handlers = array_keys($config['handler']);
        $handlerType = $handlers[0];
        $handlerConfig = $config['handler'][$handlerType];

        switch ($handlerType) {
            case 'stream':
                $container
                    ->register($handlerServiceId, 'Assimtech\Dislog\Handler\Stream')
                    ->setArguments(array(
                        $handlerConfig['resource'],
                        new Reference($handlerConfig['identity_generator']),
                        new Reference($handlerConfig['serializer']),
                    ))
                ;
                break;
            case 'doctrine_object_manager':
                $container
                    ->register($handlerServiceId, 'Assimtech\Dislog\Handler\DoctrineObjectManager')
                    ->setArguments(array(
                        new Reference($handlerConfig['object_manager']),
                    ))
                ;
                break;
            case 'service':
                $container->setAlias(
                    $handlerServiceId,
                    $handlerConfig['name']
                );
                break;
        }

        return $this;
    }

    protected function createLoggerDefinition($config, ContainerBuilder $container)
    {
        $container
            ->register('assimtech_dislog.logger', 'Assimtech\Dislog\ApiCallLogger')
            ->setArguments(array(
                new Reference('assimtech_dislog.api_call.factory'),
                new Reference('assimtech_dislog.handler'),
                $config['preferences'],
                new Reference($config['psr_logger'], ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ))
        ;

        return $this;
    }
}
