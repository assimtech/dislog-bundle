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
    const API_CALL_FACTORY_ID = 'assimtech_dislog.api_call.factory';
    const HANDLER_ID = 'assimtech_dislog.handler';
    const LOGGER_ID = 'assimtech_dislog.logger';

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
        $handlers = array_keys($config['handler']);
        $handlerType = $handlers[0];
        $handlerConfig = $config['handler'][$handlerType];

        switch ($handlerType) {
            case 'stream':
                $container
                    ->register(self::HANDLER_ID, Dislog\Handler\Stream::class)
                    ->setArguments(array(
                        $handlerConfig['resource'],
                        new Reference($handlerConfig['identity_generator']),
                        new Reference($handlerConfig['serializer']),
                    ))
                ;
                break;
            case 'doctrine_object_manager':
                $container
                    ->register(self::HANDLER_ID, Dislog\Handler\DoctrineObjectManager::class)
                    ->setArguments(array(
                        new Reference($handlerConfig['object_manager']),
                    ))
                ;
                break;
            case 'service':
                $container->setAlias(
                    self::HANDLER_ID,
                    $handlerConfig['name']
                );
                break;
        }

        $container->setAlias(Dislog\Handler\HandlerInterface::class, self::HANDLER_ID);

        return $this;
    }

    protected function createLoggerDefinition($config, ContainerBuilder $container)
    {
        $container
            ->register(self::LOGGER_ID, Dislog\ApiCallLogger::class)
            ->setArguments(array(
                new Reference(self::API_CALL_FACTORY_ID),
                new Reference(self::HANDLER_ID),
                $config['preferences'],
                new Reference($config['psr_logger'], ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ))
        ;

        $container->setAlias(Dislog\ApiCallLoggerInterface::class, self::LOGGER_ID);

        return $this;
    }
}
