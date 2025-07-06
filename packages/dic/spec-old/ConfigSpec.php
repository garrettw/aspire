<?php

namespace spec\Aspire\DIC;

use Aspire\DIC\Config;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConfigSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Config::class);
    }

    public function it_sets_default_rule()
    {
        $defaultBehavior = ['shared' => true, 'newInstances' => ['Foo', 'Bar']];

        $this->beConstructedWith($defaultBehavior);
        //$this->addRule('*', $defaultBehaviour);

        $newDefault = $this->getWrappedObject()->getRule('*');
        if ($newDefault['shared'] != true
            || array_diff($newDefault['newInstances'], ['Foo', 'Bar'])
        ) {
            throw new \Exception("can't set default rule");
        }

    }

    public function it_default_rule_works()
    {
        $defaultBehavior = ['shared' => true];
        $this->beConstructedWith($defaultBehavior);

        $this->getRule('\spec\Aspire\DIC\A')['shared']->shouldBe(true);
    }

    public function it_namespaces_rules()
    {
        $rule = [];
        $this->addRule('spec\Aspire\DIC\B', $rule, false);

        $this->getRule('spec\Aspire\DIC\B')->shouldEqual($this->getRule('*'));
    }

}
