<?php

namespace spec\Aspire\Di;

use Aspire\Di\ExplicitContainer;
use PhpSpec\ObjectBehavior;

class ExplicitContainerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ExplicitContainer::class);
    }
}
