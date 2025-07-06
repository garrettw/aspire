<?php

namespace spec\Aspire\Di;

use Aspire\Di\CompositeContainer;
use Aspire\Di\Exception\ContainerException;
use Aspire\Di\Exception\NotFoundException;
use PhpSpec\ObjectBehavior;

class CompositeContainerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(CompositeContainer::class);
    }

    public function it_contains_objects()
    {
        $this->set('Exception', new \Exception());

        $this->has('Exception')->shouldReturn(true);
    }

    public function it_supplies_objects()
    {
        $this->set('Exception', new \Exception());

        $this->get('Exception')->shouldHaveType('Exception'); // method access
        $this['Exception']->shouldHaveType('Exception'); // array access
    }

    public function it_can_tell_when_it_doesnt_contain_objects()
    {
        $this->has('NonExistent')->shouldReturn(false);
    }

    public function it_errors_when_retrieving_non_existent_objects()
    {
        $this->shouldThrow(NotFoundException::class)
            ->during('get', ['NonExistent']);

        $this->shouldThrow(NotFoundException::class)
            ->during('offsetGet', ['NonExistent']);
    }

    public function it_errors_when_accessing_non_string_key_as_array_offset()
    {
        $this->shouldThrow(NotFoundException::class)
            ->during('offsetGet', [123]);

        $this->shouldThrow(ContainerException::class)
            ->during('offsetExists', [123]);
    }
}
