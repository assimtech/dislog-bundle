<?php

namespace spec\Assimtech\DislogBundle\Document;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ApiCallSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Assimtech\DislogBundle\Document\ApiCall');
    }
}
