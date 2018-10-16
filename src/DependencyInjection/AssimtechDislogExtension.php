<?php

namespace Assimtech\DislogBundle\DependencyInjection;

use Assimtech\Dislog;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
        $handlerServiceId = Dislog\Handler\HandlerInterface::class;

        $handlers = array_keys($config['handler']);
        $handlerType = $handlers[0];
        $handlerConfig = $config['handler'][$handlerType];

        switch ($handlerType) {
            case 'stream':
                $container
                    ->register($handlerServiceId, Dislog\Handler\Stream::class)
                    ->setArguments(array(
                        $handlerConfig['resource'],
                        new Reference($handlerConfig['identity_generator']),
                        new Reference($handlerConfig['serializer']),
                    ))
                ;
                break;
            case 'doctrine_object_manager':
                $container
                    ->register($handlerServiceId, Dislog\Handler\DoctrineObjectManager::class)
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
            ->register('assimtech_dislog.logger', Dislog\ApiCallLogger::class)
            ->setArguments(array(
                new Reference(Dislog\Factory\ApiCallFactory::class),
                new Reference(Dislog\Handler\HandlerInterface::class),
                $config['preferences'],
                new Reference($config['psr_logger'], ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ))
        ;

        $container->setAlias(Dislog\ApiCallLoggerInterface::class, 'assimtech_dislog.logger');

        return $this;
    }
}
