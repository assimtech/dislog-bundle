<?php

declare(strict_types=1);

namespace Assimtech\DislogBundle\DependencyInjection;

use Assimtech\Dislog;
use Assimtech\DislogBundle\Command;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class AssimtechDislogExtension extends Extension
{
    const API_CALL_FACTORY_ID = 'assimtech_dislog.api_call.factory';
    const HANDLER_ID = 'assimtech_dislog.handler';
    const LOGGER_ID = 'assimtech_dislog.logger';

    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this
            ->createHandlerDefinition($config, $container)
            ->createLoggerDefinition($config, $container)
            ->configureCommands($config, $container)
        ;
    }

    protected function createHandlerDefinition(
        $config,
        ContainerBuilder $container
    ): self {
        $handlers = array_keys($config['handler']);
        $handlerType = $handlers[0];
        $handlerConfig = $config['handler'][$handlerType];

        switch ($handlerType) {
            case 'stream':
                $container
                    ->register(self::HANDLER_ID, Dislog\Handler\Stream::class)
                    ->setArguments([
                        $handlerConfig['resource'],
                        new Reference($handlerConfig['identity_generator']),
                        new Reference($handlerConfig['serializer']),
                    ])
                ;
                break;
            case 'doctrine_document_manager':
                $container
                    ->register(self::HANDLER_ID, Dislog\Handler\DoctrineDocumentManager::class)
                    ->setArguments([
                        new Reference($handlerConfig['document_manager']),
                    ])
                ;
                break;
            case 'doctrine_entity_manager':
                $container
                    ->register(self::HANDLER_ID, Dislog\Handler\DoctrineEntityManager::class)
                    ->setArguments([
                        new Reference($handlerConfig['entity_manager']),
                    ])
                ;
                break;
            case 'doctrine_object_manager':
                \trigger_error(
                    'DoctrineObjectManager is deprecated, use DoctrineDocumentManager or DoctrineEntityManager instead',
                    E_USER_DEPRECATED
                );
                $container
                    ->register(self::HANDLER_ID, Dislog\Handler\DoctrineObjectManager::class)
                    ->setArguments([
                        new Reference($handlerConfig['object_manager']),
                    ])
                ;
                break;
            case 'service':
                $container->setAlias(
                    self::HANDLER_ID,
                    $handlerConfig['name']
                );
                break;
            default:
                throw new InvalidArgumentException('Unsupported handler type: ' . $handlerType);
        }

        $container->setAlias(Dislog\Handler\HandlerInterface::class, self::HANDLER_ID);

        return $this;
    }

    protected function createLoggerDefinition(
        $config,
        ContainerBuilder $container
    ): self {
        $container
            ->register(self::LOGGER_ID, Dislog\ApiCallLogger::class)
            ->setArguments([
                new Reference(self::API_CALL_FACTORY_ID),
                new Reference(self::HANDLER_ID),
                $config['preferences'],
                new Reference($config['psr_logger'], ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ])
        ;

        $container->setAlias(Dislog\ApiCallLoggerInterface::class, self::LOGGER_ID);

        return $this;
    }

    protected function configureCommands(
        $config,
        ContainerBuilder $container
    ): self {
        $container->getDefinition('assimtech_dislog.command.remove')
            ->setArgument('$maxAge', $config['max_age'])
        ;

        return $this;
    }
}
