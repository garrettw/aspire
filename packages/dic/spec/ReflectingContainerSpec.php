<?php

namespace spec\Outboard\Di;

use Outboard\Di\ReflectingContainer;
use PhpSpec\ObjectBehavior;

class ReflectingContainerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ReflectingContainer::class);
    }
}
