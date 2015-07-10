<?php

namespace Assimtech\DislogBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;
use InvalidArgumentException;

class AssimtechDislogExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

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
                $arguments = array(
                    $handlerConfig['resource'],
                    new Reference($handlerConfig['identity_generator']),
                    new Reference($handlerConfig['serializer']),
                );
                $definition = new Definition('Assimtech\Dislog\Handler\Stream', $arguments);
                break;
            case 'doctrine_object_manager':
                $arguments = array(
                    new Reference($handlerConfig['object_manager']),
                );
                $definition = new Definition('Assimtech\Dislog\Handler\DoctrineObjectManager', $arguments);
                break;
            case 'service':
                $container->setAlias(
                    $handlerServiceId,
                    $handlerConfig['name']
                );
                return $this;
        }

        $container->setDefinition($handlerServiceId, $definition);

        return $this;
    }

    protected function createLoggerDefinition($config, ContainerBuilder $container)
    {
        $arguments = array(
            new Reference('assimtech_dislog.api_call.factory'),
            new Reference('assimtech_dislog.handler'),
            $config['preferences'],
            new Reference($config['psr_logger'], array(), ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
        );

        $definition = new Definition('Assimtech\Dislog\ApiCallLogger', $arguments);

        $container->setDefinition('assimtech_dislog.logger', $definition);

        return $this;
    }
}
