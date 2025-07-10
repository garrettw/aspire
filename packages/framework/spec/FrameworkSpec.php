<?php

namespace spec\Outboard;

use Outboard\Framework;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FrameworkSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Framework::class);
    }

    function it_takes_input()
    {
        $this->input($_SERVER, $_GET, $_POST, $_COOKIE, $_SESSION, $_FILES)->shouldReturn($this);
    }

    function it_runs()
    {
        $this->run()->shouldReturn(true);
    }
}
