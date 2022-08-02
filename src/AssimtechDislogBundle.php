<?php

declare(strict_types=1);

namespace Assimtech\DislogBundle;

use Symfony\Component\HttpKernel;
use Symfony\Component\DependencyInjection;

class AssimtechDislogBundle extends HttpKernel\Bundle\Bundle
{
    public function build(
        DependencyInjection\ContainerBuilder $container
    ): void {
        parent::build($container);

        $container->addCompilerPass(
            new DependencyInjection\Compiler\LoggingHttpClientPass(),
            DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION
        );
    }
}
