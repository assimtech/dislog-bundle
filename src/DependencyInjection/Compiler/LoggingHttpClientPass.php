<?php

declare(strict_types=1);

namespace Assimtech\DislogBundle\DependencyInjection\Compiler;

use Assimtech\Dislog;
use Assimtech\DislogBundle\DependencyInjection\AssimtechDislogExtension;
use Psr\Http;
use Symfony\Component\DependencyInjection;

class LoggingHttpClientPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    public function process(DependencyInjection\ContainerBuilder $container)
    {
        if ((
                !$container->hasDefinition(Http\Client\ClientInterface::class)
                && !$container->hasAlias(Http\Client\ClientInterface::class)
            )
            || !\class_exists(\GuzzleHttp\Psr7\Message::class)
        ) {
            return $this;
        }

        $container
            ->register(AssimtechDislogExtension::LOGGING_HTTP_CLIENT_ID, Dislog\LoggingHttpClient::class)
            ->setArguments([
                new DependencyInjection\Reference(Http\Client\ClientInterface::class),
                new DependencyInjection\Reference(AssimtechDislogExtension::LOGGER_ID),
            ])
        ;

        $container->setAlias(Dislog\LoggingHttpClientInterface::class, AssimtechDislogExtension::LOGGING_HTTP_CLIENT_ID);
    }
}
