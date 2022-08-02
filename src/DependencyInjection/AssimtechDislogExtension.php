<?php

declare(strict_types=1);

namespace Assimtech\DislogBundle\DependencyInjection;

use Assimtech\Dislog;
use Symfony\Component\Config;
use Symfony\Component\DependencyInjection;
use Symfony\Component\HttpKernel;

class AssimtechDislogExtension extends HttpKernel\DependencyInjection\Extension
{
    const API_CALL_FACTORY_ID = 'assimtech_dislog.api_call.factory';
    const HANDLER_ID = 'assimtech_dislog.handler';
    const LOGGER_ID = 'assimtech_dislog.logger';
    const LOGGING_HTTP_CLIENT_ID = 'assimtech_dislog.logging_http_client';

    public function load(
        array $configs,
        DependencyInjection\ContainerBuilder $container
    ): void {
        $loader = new DependencyInjection\Loader\YamlFileLoader(
            $container,
            new Config\FileLocator(__DIR__ . '/../Resources/config')
        );
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
        DependencyInjection\ContainerBuilder $container
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
                        new DependencyInjection\Reference($handlerConfig['identity_generator']),
                        new DependencyInjection\Reference($handlerConfig['serializer']),
                    ])
                ;
                break;
            case 'doctrine_document_manager':
                $container
                    ->register(self::HANDLER_ID, Dislog\Handler\DoctrineDocumentManager::class)
                    ->setArguments([
                        new DependencyInjection\Reference($handlerConfig['document_manager']),
                    ])
                ;
                break;
            case 'doctrine_entity_manager':
                $container
                    ->register(self::HANDLER_ID, Dislog\Handler\DoctrineEntityManager::class)
                    ->setArguments([
                        new DependencyInjection\Reference($handlerConfig['entity_manager']),
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
                        new DependencyInjection\Reference($handlerConfig['object_manager']),
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
                throw new DependencyInjection\Exception\InvalidArgumentException(
                    "Unsupported handler type: {$handlerType}"
                );
        }

        $container->setAlias(Dislog\Handler\HandlerInterface::class, self::HANDLER_ID);

        return $this;
    }

    protected function createLoggerDefinition(
        $config,
        DependencyInjection\ContainerBuilder $container
    ): self {
        $container
            ->register(self::LOGGER_ID, Dislog\ApiCallLogger::class)
            ->setArguments([
                new DependencyInjection\Reference(self::API_CALL_FACTORY_ID),
                new DependencyInjection\Reference(self::HANDLER_ID),
                $config['preferences'],
                new DependencyInjection\Reference(
                    $config['psr_logger'],
                    DependencyInjection\ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
            ])
        ;

        $container->setAlias(Dislog\ApiCallLoggerInterface::class, self::LOGGER_ID);

        return $this;
    }

    protected function configureCommands(
        $config,
        DependencyInjection\ContainerBuilder $container
    ): self {
        $container->getDefinition('assimtech_dislog.command.remove')
            ->setArgument('$maxAge', $config['max_age'])
        ;

        return $this;
    }
}
