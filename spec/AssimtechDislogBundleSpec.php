<?php

namespace spec\Assimtech\DislogBundle;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Assimtech\DislogBundle\DependencyInjection\Compiler\ProcessorCompilerPass;

class AssimtechDislogBundleSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Assimtech\DislogBundle\AssimtechDislogBundle');
    }

    function it_can_build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ProcessorCompilerPass())->shouldBeCalled();

        $this->build($container);
    }
}
