<?php

namespace spec\Aspire\Di;

use Aspire\Di\ReflectingContainer;
use PhpSpec\ObjectBehavior;

class ReflectingContainerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ReflectingContainer::class);
    }
}
