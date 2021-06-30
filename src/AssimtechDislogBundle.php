<?php

declare(strict_types=1);

namespace Assimtech\DislogBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

class AssimtechDislogBundle extends Bundle
{
    public function build(
        ContainerBuilder $container
    ): void {
        parent::build($container);

        $container->addCompilerPass(
            new DependencyInjection\Compiler\LoggingHttpClientPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION
        );
    }
}
