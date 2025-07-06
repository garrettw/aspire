<?php

namespace spec\Aspire\Di;

use Aspire\Di\CompositeContainer;
use Aspire\Di\Config;
use Aspire\Di\Definition;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ContainerSpec extends ObjectBehavior
{
    function it_is_initializable_with_config(Config $config)
    {
        $this->beConstructedWith($config);

        $this->shouldHaveType(CompositeContainer::class);
    }

    public function it_creates_a_basic_object(Config $config)
    {
        $this->beConstructedWith($config);

        $a = $this->get('spec\Aspire\Di\NoConstructor');

        $a->shouldBeAnInstanceOf('spec\Aspire\Di\NoConstructor');
    }

    public function it_instantiates_internal_class()
    {
        $rule = new Definition(constructParams: ['.']);
        $config = (new Config())->addRule('DirectoryIterator', $rule);
        $this->beConstructedWith($config);

        $dir = $this->get('DirectoryIterator');

        $dir->shouldBeAnInstanceOf('DirectoryIterator');
    }

    public function it_instantiates_extended_internal_class()
    {
        $rule = ['constructParams' => ['.']];
        $config = (new Config())->addRule('spec\Aspire\DIC\MyDirectoryIterator', $rule);
        $this->beConstructedWith($config);

        $dir = $this->get('spec\Aspire\DIC\MyDirectoryIterator');

        $dir->shouldBeAnInstanceOf('spec\Aspire\DIC\MyDirectoryIterator');
    }

    public function it_instantiates_extended_internal_class_with_constructor()
    {
        $rule = ['constructParams' => ['.']];
        $config = (new Config())->addRule('spec\Aspire\DIC\MyDirectoryIterator2', $rule);
        $this->beConstructedWith($config);

        $dir = $this->get('spec\Aspire\DIC\MyDirectoryIterator2');

        $dir->shouldBeAnInstanceOf('spec\Aspire\DIC\MyDirectoryIterator2');
    }

    public function it_no_more_assign()
    {
        $rule = ['substitutions' => ['spec\Aspire\DIC\Bar77' => ['instance' => function() {
            return \spec\Aspire\DIC\Baz77::create();
        }]]];
        $config = (new Config())->addRule('spec\Aspire\DIC\Foo77', $rule);
        $this->beConstructedWith($config);

        $foo = $this->get('spec\Aspire\DIC\Foo77');

        $foo->bar->shouldBeAnInstanceOf('spec\Aspire\DIC\Bar77');
        $foo->bar->a->shouldEqual('Z');
    }

    public function it_consumes_args()
    {
        $rule = ['constructParams' => ['A']];
        $config = (new Config())->addRule('spec\Aspire\DIC\ConsumeArgsSub', $rule);
        $this->beConstructedWith($config);

        $foo = $this->get('spec\Aspire\DIC\ConsumeArgsTop', ['B']);

        $foo->a->s->shouldEqual('A');
    }

    public function it_assigns_shared_named()
    {
        $rule = ['shared' => true, 'instanceOf' => function() {
            return \spec\Aspire\DIC\Baz77::create();
        }];
        $config = (new Config())->addRule('$SharedBaz', $rule);

        //$rule2
    }

    public function it_creates()
    {
        $myobj = $this->get('stdClass');

        $myobj->shouldBeAnInstanceOf('stdClass');
    }

    public function it_cant_create_invalid()
    {
        //"can't expect default exception". Not sure why.
        $this->shouldThrow('\Aspire\DIC\Exception\NotFoundException')->duringGet('SomeClassThatDoesNotExist');
    }

    /*
     * Object graph creation cannot be tested with mocks because the constructor needs to be tested.
     * You can't set 'expects' on the objects which are created making them redundant for that as well
     * Need real classes to test with unfortunately.
     */
    public function it_creates_object_graph()
    {
        $a = $this->get('spec\Aspire\DIC\A');

        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\B');
        $a->b->c->shouldBeAnInstanceOf('spec\Aspire\DIC\C');
        $a->b->c->d->shouldBeAnInstanceOf('spec\Aspire\DIC\D');
        $a->b->c->e->shouldBeAnInstanceOf('spec\Aspire\DIC\E');
        $a->b->c->e->d->shouldBeAnInstanceOf('spec\Aspire\DIC\D');
    }

    public function it_assigns_default_null()
    {
        $rule = ['constructParams' => [['instance' => 'spec\Aspire\DIC\A'], null]];
        $config = (new Config())->addRule('spec\Aspire\DIC\MethodWithDefaultNull', $rule);
        $this->beConstructedWith($config);

        $obj = $this->get('spec\Aspire\DIC\MethodWithDefaultNull');

        $obj->b->shouldEqual(null);
    }

    public function it_substitutes_null()
    {
        $rule = ['substitutions' => ['spec\Aspire\DIC\B' => null]];
        $config = (new Config())->addRule('spec\Aspire\DIC\MethodWithDefaultNull', $rule);
        $this->beConstructedWith($config);

        $obj = $this->get('spec\Aspire\DIC\MethodWithDefaultNull');

        $obj->b->shouldEqual(null);
    }

    public function it_shared_named()
    {
        $rule = ['shared' => true, 'instanceOf' => 'spec\Aspire\DIC\A'];

        $config = (new Config())->addRule('[A]', $rule);
        $this->beConstructedWith($config);

        $a1 = $this->get('[A]');
        $a2 = $this->get('[A]');

        $a1->shouldEqual($a2);
    }

    public function it_shares()
    {
        $shared = ['shared' => true];
        $config = (new Config())->addRule('spec\Aspire\DIC\MyObj', $shared);
        $this->beConstructedWith($config);

        $obj = $this->get('spec\Aspire\DIC\MyObj');
        $obj2 = $this->get('spec\Aspire\DIC\MyObj');

        $obj->shouldBeAnInstanceOf('spec\Aspire\DIC\MyObj');
        $obj2->shouldBeAnInstanceOf('spec\Aspire\DIC\MyObj');

        $obj->shouldEqual($obj2);

        //This check isn't strictly needed but it's nice to have that safety measure!
        $obj->setFoo('bar');
        $obj->getFoo()->shouldEqual($obj2->getFoo());
        $obj->getFoo()->shouldEqual('bar');
        $obj2->getFoo()->shouldEqual('bar');
    }

    public function it_substitutes_text()
    {
        $rule = ['substitutions' => ['spec\Aspire\DIC\B' => ['instance' => 'spec\Aspire\DIC\ExtendedB']]];
        $config = (new Config())->addRule('spec\Aspire\DIC\A', $rule);
        $this->beConstructedWith($config);

        $a = $this->get('spec\Aspire\DIC\A');

        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\ExtendedB');
    }

    public function it_substitutes_mixed_case_text()
    {
        $rule = ['substitutions' => ['spec\Aspire\DIC\B' => ['instance' => 'spec\Aspire\DIC\exTenDedb']]];
        $config = (new Config())->addRule('spec\Aspire\DIC\A', $rule);
        $this->beConstructedWith($config);

        $a = $this->get('spec\Aspire\DIC\A');

        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\ExtendedB');
    }

    public function it_substitutes_callback()
    {
        $this->beConstructedWith(new Config());
        $injection = $this->getWrappedObject();
        $rule = ['substitutions' => ['spec\Aspire\DIC\B' => ['instance' =>
            function() use ($injection) {
                return $injection->get('spec\Aspire\DIC\ExtendedB');
            }
        ]]];
        $this->config()->addRule('spec\Aspire\DIC\A', $rule);

        $a = $this->get('spec\Aspire\DIC\A');

        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\ExtendedB');
    }

    public function it_substitutes_object()
    {
        $this->beConstructedWith(new Config());
        $rule = ['substitutions' => ['spec\Aspire\DIC\B' =>
            $this->getWrappedObject()->get('spec\Aspire\DIC\ExtendedB')
        ]];
        $this->config()->addRule('spec\Aspire\DIC\A', $rule);

        $a = $this->get('spec\Aspire\DIC\A');

        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\ExtendedB');
    }

    public function it_substitutes_string()
    {
        $rule = ['substitutions' => ['spec\Aspire\DIC\B' =>
            ['instance' => 'spec\Aspire\DIC\ExtendedB']
        ]];
        $config = (new Config())->addRule('spec\Aspire\DIC\A', $rule);
        $this->beConstructedWith($config);

        $a = $this->get('spec\Aspire\DIC\A');

        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\ExtendedB');
    }

    public function it_constructs_with_params()
    {
        $rule = ['constructParams' => ['foo', 'bar']];
        $config = (new Config())->addRule('spec\Aspire\DIC\RequiresConstructorArgsA', $rule);
        $this->beConstructedWith($config);

        $obj = $this->get('spec\Aspire\DIC\RequiresConstructorArgsA');

        $obj->foo->shouldEqual('foo');
        $obj->bar->shouldEqual('bar');
    }

    public function it_constructs_with_nested_params()
    {
        $rule = ['constructParams' => ['foo', 'bar']];
        $config = (new Config())->addRule('spec\Aspire\DIC\RequiresConstructorArgsA', $rule);
        $rule = ['shareInstances' => ['spec\Aspire\DIC\D']];
        $config->addRule('spec\Aspire\DIC\ParamRequiresArgs', $rule);
        $this->beConstructedWith($config);

        $obj = $this->get('spec\Aspire\DIC\ParamRequiresArgs');

        $obj->a->foo->shouldEqual('foo');
        $obj->a->bar->shouldEqual('bar');
    }

    public function it_constructs_with_mixed_params()
    {
        $rule = ['constructParams' => ['foo', 'bar']];
        $config = (new Config())->addRule('spec\Aspire\DIC\RequiresConstructorArgsB', $rule);
        $this->beConstructedWith($config);

        $obj = $this->get('spec\Aspire\DIC\RequiresConstructorArgsB');

        $obj->foo->shouldEqual('foo');
        $obj->bar->shouldEqual('bar');
        $obj->a->shouldBeAnInstanceOf('spec\Aspire\DIC\A');
    }

    public function it_constructs_with_args()
    {
        $obj = $this->get('spec\Aspire\DIC\RequiresConstructorArgsA', ['foo', 'bar']);

        $obj->foo->shouldEqual('foo');
        $obj->bar->shouldEqual('bar');
    }

    public function it_constructs_with_mixed_args()
    {
        $obj = $this->get('spec\Aspire\DIC\RequiresConstructorArgsB', ['foo', 'bar']);

        $obj->foo->shouldEqual('foo');
        $obj->bar->shouldEqual('bar');
        $obj->a->shouldBeAnInstanceOf('spec\Aspire\DIC\A');
    }

    public function it_creates_with_1_arg()
    {
        $a = $this->get('spec\Aspire\DIC\A', [$this->get('spec\Aspire\DIC\ExtendedB')]);

        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\ExtendedB');
    }

    public function it_creates_with_2_args()
    {
        $a2 = $this->get('spec\Aspire\DIC\A2', [$this->get('spec\Aspire\DIC\ExtendedB'), 'Foo']);

        $a2->b->shouldBeAnInstanceOf('spec\Aspire\DIC\B');
        $a2->c->shouldBeAnInstanceOf('spec\Aspire\DIC\C');
        $a2->foo->shouldEqual('Foo');
    }

    public function it_creates_with_2_reversed_args()
    {
        //reverse order args. It should be smart enough to handle this.
        $a2 = $this->get('spec\Aspire\DIC\A2', ['Foo', $this->get('spec\Aspire\DIC\ExtendedB')]);

        $a2->b->shouldBeAnInstanceOf('spec\Aspire\DIC\B');
        $a2->c->shouldBeAnInstanceOf('spec\Aspire\DIC\C');
        $a2->foo->shouldEqual('Foo');
    }

    public function it_creates_with_2_other_args()
    {
        $a2 = $this->get('spec\Aspire\DIC\A3', ['Foo', $this->get('spec\Aspire\DIC\ExtendedB')]);

        $a2->b->shouldBeAnInstanceOf('spec\Aspire\DIC\B');
        $a2->c->shouldBeAnInstanceOf('spec\Aspire\DIC\C');
        $a2->foo->shouldEqual('Foo');
    }

    public function it_shares_multiple_instances_by_name_mixed()
    {
        $rule = ['shared' => true, 'constructParams' => ['FirstY']];
        $config = (new Config())->addRule('spec\Aspire\DIC\Y', $rule);

        $rule = ['shared' => true, 'constructParams' => ['SecondY'],
            'instanceOf' => 'spec\Aspire\DIC\Y', 'inherit' => false
        ];
        $config->addRule('[Y2]', $rule);

        $rule = ['constructParams' =>
            [['instance' => 'spec\Aspire\DIC\Y'], ['instance' => '[Y2]']]
        ];
        $config->addRule('spec\Aspire\DIC\HasTwoSameDependencies', $rule);
        $this->beConstructedWith($config);

        $z = $this->get('spec\Aspire\DIC\HasTwoSameDependencies');

        $z->ya->name->shouldEqual('FirstY');
        $z->yb->name->shouldEqual('SecondY');
    }

    public function it_non_shared_component_by_name()
    {
        $rule = ['instanceOf' => 'spec\Aspire\DIC\Y3', 'constructParams' => ['test']];
        $config = (new Config())->addRule('$Y2', $rule);
        $rule = ['constructParams' => [['instance' => '$Y2']]];
        $config->addRule('spec\Aspire\DIC\Y1', $rule);
        $this->beConstructedWith($config);

        $y2 = $this->get('$Y2');
        //echo $y2->name;
        $y2->shouldBeAnInstanceOf('spec\Aspire\DIC\Y3');

        $y1 = $this->get('spec\Aspire\DIC\Y1');
        $y1->y->shouldBeAnInstanceOf('spec\Aspire\DIC\Y3');
    }

    public function it_non_shared_component_by_name_a()
    {
        $rule = ['instanceOf' => 'spec\Aspire\DIC\ExtendedB'];
        $config = (new Config())->addRule('$B', $rule);

        $rule = ['constructParams' => [['instance' => '$B']]];
        $config->addRule('spec\Aspire\DIC\A', $rule);
        $this->beConstructedWith($config);

        $a = $this->get('spec\Aspire\DIC\A');

        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\ExtendedB');
    }

    public function it_substitutes_by_name()
    {
        $rule = ['instanceOf' => 'spec\Aspire\DIC\ExtendedB'];
        $config = (new Config())->addRule('$B', $rule);

        $rule = ['substitutions' => ['spec\Aspire\DIC\B' => ['instance' => '$B']]];
        $config->addRule('spec\Aspire\DIC\A', $rule);
        $this->beConstructedWith($config);

        $a = $this->get('spec\Aspire\DIC\A');

        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\ExtendedB');
    }

    public function it_does_multiple_substitutions()
    {
        $rule = ['instanceOf' => 'spec\Aspire\DIC\Y', 'constructParams' => ['first']];
        $config = (new Config())->addRule('$YA', $rule);

        $rule = ['instanceOf' => 'spec\Aspire\DIC\Y', 'constructParams' => ['second']];
        $config->addRule('$YB', $rule);

        $rule = ['constructParams' => [['instance' => '$YA'], ['instance' => '$YB']]];
        $config->addRule('spec\Aspire\DIC\HasTwoSameDependencies', $rule);
        $this->beConstructedWith($config);

        $twodep = $this->get('spec\Aspire\DIC\HasTwoSameDependencies');

        $twodep->ya->name->shouldEqual('first');
        $twodep->yb->name->shouldEqual('second');
    }

    public function it_calls()
    {
        $rule = ['call' => [['callMe', []]]];
        $config = (new Config())->addRule('spec\Aspire\DIC\TestCall', $rule);
        $this->beConstructedWith($config);

        $object = $this->get('spec\Aspire\DIC\TestCall');

        $object->isCalled->shouldBe(true);
    }

    public function it_calls_with_parameters()
    {
        $rule = ['call' => [['callMe', ['one', 'two']]]];
        $config = (new Config())->addRule('spec\Aspire\DIC\TestCall2', $rule);
        $this->beConstructedWith($config);

        $object = $this->get('spec\Aspire\DIC\TestCall2');

        $object->foo->shouldEqual('one');
        $object->bar->shouldEqual('two');
    }

    public function it_calls_with_instance()
    {
        $rule = ['call' => [['callMe', [['instance' => 'spec\Aspire\DIC\A']]]]];
        $config = (new Config())->addRule('spec\Aspire\DIC\TestCall3', $rule);
        $this->beConstructedWith($config);

        $object = $this->get('spec\Aspire\DIC\TestCall3');

        $object->a->shouldBeAnInstanceOf('spec\Aspire\DIC\a');
    }

    public function it_calls_with_raw_instance()
    {
        $this->beConstructedWith(new Config());
        $rule = ['call' => [['callMe',
            [$this->getWrappedObject()->get('spec\Aspire\DIC\A')]
        ]]];
        $this->config()->addRule('spec\Aspire\DIC\TestCall3', $rule);

        $object = $this->get('spec\Aspire\DIC\TestCall3');

        $object->a->shouldBeAnInstanceOf('spec\Aspire\DIC\A');
    }

    public function it_calls_with_raw_instance_and_matches_on_inheritance()
    {
        $this->beConstructedWith(new Config());
        $rule = ['call' => [['callMe',
            [$this->getWrappedObject()->get('spec\Aspire\DIC\A')]
        ]]];
        $this->config()->addRule('spec\Aspire\DIC\TestCall3', $rule);

        $object = $this->get('spec\Aspire\DIC\TestCall3');

        $object->a->shouldBeAnInstanceOf('spec\Aspire\DIC\A');
    }

    public function it_can_use_interface_rules()
    {
        $rule = ['shared' => true];
        $config = (new Config())->addRule('spec\Aspire\DIC\TestInterface', $rule);
        $this->beConstructedWith($config);

        $one = $this->get('spec\Aspire\DIC\InterfaceTestClass');
        $two = $this->get('spec\Aspire\DIC\InterfaceTestClass');

        $one->shouldImplement('spec\Aspire\DIC\TestInterface');
        $one->shouldEqual($two);
    }

    public function it_applies_rules_to_child_classes()
    {
        $rule = ['call' => [['stringset', ['test']]]];
        $config = (new Config())->addRule('spec\Aspire\DIC\B', $rule);
        $this->beConstructedWith($config);

        $xb = $this->get('spec\Aspire\DIC\ExtendedB');

        $xb->s->shouldEqual('test');
    }

    public function it_matches_best()
    {
        $bestMatch = $this->get('spec\Aspire\DIC\BestMatch', ['foo', $this->get('spec\Aspire\DIC\A')]);

        $bestMatch->string->shouldEqual('foo');
        $bestMatch->a->shouldBeAnInstanceOf('spec\Aspire\DIC\A');
    }

    public function it_shares_instances()
    {
        $rule = ['shareInstances' => ['spec\Aspire\DIC\Shared']];
        $config = (new Config())->addRule('spec\Aspire\DIC\TestSharedInstancesTop', $rule);
        $this->beConstructedWith($config);

        $shareTest = $this->get('spec\Aspire\DIC\TestSharedInstancesTop');

        $shareTest->shouldBeAnInstanceOf('spec\Aspire\DIC\TestSharedInstancesTop');
        $shareTest->share1->shouldBeAnInstanceOf('spec\Aspire\DIC\SharedInstanceTest1');
        $shareTest->share2->shouldBeAnInstanceOf('spec\Aspire\DIC\SharedInstanceTest2');
        $shareTest->share1->shared->uniq->shouldEqual($shareTest->share2->shared->uniq);
    }

    public function it_shares_named_instances()
    {
        $rule = ['instanceOf' => 'spec\Aspire\DIC\Shared'];
        $config = (new Config())->addRule('$Shared', $rule);
        $rule = ['shareInstances' => ['$Shared']];
        $config->addRule('spec\Aspire\DIC\TestSharedInstancesTop', $rule);
        $this->beConstructedWith($config);

        $shareTest = $this->get('spec\Aspire\DIC\TestSharedInstancesTop');
        $shareTest2 = $this->get('spec\Aspire\DIC\TestSharedInstancesTop');

        $shareTest->shouldBeAnInstanceOf('spec\Aspire\DIC\TestSharedInstancesTop');
        $shareTest->share1->shouldBeAnInstanceOf('spec\Aspire\DIC\SharedInstanceTest1');
        $shareTest->share2->shouldBeAnInstanceOf('spec\Aspire\DIC\SharedInstanceTest2');
        $shareTest->share1->shared->uniq->shouldEqual($shareTest->share2->shared->uniq);
        $shareTest2->share1->shared->shouldNotEqual($shareTest->share2->shared);
    }

    public function it_shares_nested_instances()
    {
        $rule = ['shareInstances' => ['spec\Aspire\DIC\D']];
        $config = (new Config())->addRule('spec\Aspire\DIC\A4',$rule);
        $this->beConstructedWith($config);

        $a = $this->get('spec\Aspire\DIC\A4');

        $a->e->d->shouldEqual($a->m2->e->d);
    }

    public function it_shares_multiple_instances()
    {
        $rule = ['shareInstances' => ['spec\Aspire\DIC\Shared']];
        $config = (new Config())->addRule('spec\Aspire\DIC\TestSharedInstancesTop', $rule);
        $this->beConstructedWith($config);

        $shareTest = $this->get('spec\Aspire\DIC\TestSharedInstancesTop');
        $shareTest2 = $this->get('spec\Aspire\DIC\TestSharedInstancesTop');

        $shareTest->shouldBeAnInstanceOf('spec\Aspire\DIC\TestSharedInstancesTop');
        $shareTest->share1->shouldBeAnInstanceOf('spec\Aspire\DIC\SharedInstanceTest1');
        $shareTest->share2->shouldBeAnInstanceOf('spec\Aspire\DIC\SharedInstanceTest2');
        $shareTest->share1->shared->uniq->shouldEqual($shareTest->share2->shared->uniq);
        $shareTest2->share1->shared->uniq->shouldEqual($shareTest2->share2->shared->uniq);
        $shareTest->share1->shared->uniq->shouldNotEqual($shareTest2->share2->shared->uniq);
    }

    public function it_namespaces_with_slash()
    {
        $a = $this->get('\spec\Aspire\DIC\NoConstructor');

        $a->shouldBeAnInstanceOf('\spec\Aspire\DIC\NoConstructor');
    }

    public function it_applies_rules_to_namespaces_with_slash()
    {
        $rule = ['substitutions' => ['spec\Aspire\DIC\B' => ['instance' => 'spec\Aspire\DIC\ExtendedB']]];
        $config = (new Config())->addRule('\spec\Aspire\DIC\A', $rule);
        $this->beConstructedWith($config);

        $a = $this->get('\spec\Aspire\DIC\A');
        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\ExtendedB');
    }

    // public function testNamespaceTypeHint

    public function it_injects_namespaces()
    {
        $a = $this->get('spec\Aspire\DIC\A');

        $a->shouldBeAnInstanceOf('spec\Aspire\DIC\A');
        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\B');
    }

    /* public function it_handles_cyclic_references()
    {
        $rule = new \Dice\Rule;
        $rule->shared = true;
        $this->addRule('spec\Aspire\DIC\CyclicB', $rule);

        $a = $this->get('spec\Aspire\DIC\CyclicA');

        $a->b->shouldBeAnInstanceOf('spec\Aspire\DIC\CyclicB');
        $a->b->a->shouldBeAnInstanceOf('spec\Aspire\DIC\CyclicA');

        $a->b->shouldEqual($a->b->a->b);
    } */

    public function it_shared_class_with_trait_extends_internal_class()
    {
        $rule = ['shared' => true, 'constructParams' => ['.']];
        $config = (new Config())->addRule('spec\Aspire\DIC\MyDirectoryIteratorWithTrait', $rule);
        $this->beConstructedWith($config);

        $dir = $this->get('spec\Aspire\DIC\MyDirectoryIteratorWithTrait');

        $dir->shouldBeAnInstanceOf('spec\Aspire\DIC\MyDirectoryIteratorWithTrait');
    }

    public function it_handles_precedence_of_construct_params()
    {
        $rule = ['constructParams' => ['A', 'B']];
        $config = (new Config())->addRule('spec\Aspire\DIC\RequiresConstructorArgsA', $rule);
        $this->beConstructedWith($config);

        $a1 = $this->get('spec\Aspire\DIC\RequiresConstructorArgsA');
        $a2 = $this->get('spec\Aspire\DIC\RequiresConstructorArgsA', ['C', 'D']);

        $a1->foo->shouldEqual('A');
        $a1->bar->shouldEqual('B');
        $a2->foo->shouldEqual('C');
        $a2->bar->shouldEqual('D');
    }

    public function it_handles_null_scalar()
    {
        $rule = ['constructParams' => [null]];
        $config = (new Config())->addRule('spec\Aspire\DIC\NullScalar', $rule);
        $this->beConstructedWith($config);

        $obj = $this->get('spec\Aspire\DIC\NullScalar');

        $obj->string->shouldEqual(null);
    }

    public function it_handles_nested_null_scalars()
    {
        $rule = ['constructParams' => [null]];
        $config = (new Config())->addRule('spec\Aspire\DIC\NullScalar', $rule);
        $this->beConstructedWith($config);

        $obj = $this->get('spec\Aspire\DIC\NullScalarNested');

        $obj->nullScalar->string->shouldEqual(null);
    }
}

class A {
    public $b;
    public function __construct(B $b) {
        $this->b = $b;
    }
}

class A2 {
	public $b;
	public $c;
	public $foo;
	public function __construct(B $b, C $c, $foo) {
		$this->b = $b;
		$this->foo = $foo;
		$this->c = $c;
	}
}

class A3 {
    public $b;
    public $c;
    public $foo;
    public function __construct(C $c, $foo, B $b) {
        $this->b = $b;
        $this->foo = $foo;
        $this->c = $c;
    }
}

class A4 {
    public $e;
    public $m2;
    public function __construct(E $e, M2 $m2) {
        $this->e = $e;
        $this->m2 = $m2;
    }
}

class B {
    public $c;
    public $s = '';
    public function __construct(C $c) {
        $this->c = $c;
    }

    public function stringset($str) {
        $this->s = $str;
    }
}

class Bar77 {
    public $a;
    public function __construct($a) {
        $this->a = $a;
    }
}

class Baz77 {
    public static function create() {
        return new Bar77('Z');
    }
}

class BestMatch {
    public $a;
    public $string;
    public $b;

    public function __construct($string, A $a, B $b) {
        $this->a = $a;
        $this->string = $string;
        $this->b = $b;
    }
}

class C {
    public $d;
    public $e;
    public function __construct(D $d, E $e) {
        $this->d = $d;
        $this->e = $e;
    }
}

class ConsumeArgsSub {
    public $s;
    public function __construct($s) {
        $this->s = $s;
    }
}

class ConsumeArgsTop {
    public $s;
    public $a;
    public function __construct(ConsumeArgsSub $a, $s) {
        $this->a = $a;
        $this->s = $s;
    }
}

class CyclicA {
    public $b;

    public function __construct(CyclicB $b) {
        $this->b = $b;
    }
}

class CyclicB {
    public $a;

    public function __construct(CyclicA $a) {
        $this->a = $a;
    }
}

class D {}

class E {
    public $d;
    public function __construct(D $d) {
        $this->d = $d;
    }
}

class ExtendedB extends B {}

class Foo77 {
    public $bar;
    public function __construct(Bar77 $bar) {
        $this->bar = $bar;
    }
}

class HasTwoSameDependencies {
    public $ya;
    public $yb;

    public function __construct(Y $ya, Y $yb) {
        $this->ya = $ya;
        $this->yb = $yb;
    }
}

class InterfaceTestClass implements TestInterface {}

class M2 {
	public $e;
	public function __construct(E $e) {
		$this->e = $e;
	}
}

class MethodWithDefaultNull {
    public $a;
    public $b;
    public function __construct(A $a, B $b = null) {
        $this->a = $a;
        $this->b = $b;
    }
}

class MethodWithDefaultValue {
    public $a;
    public $foo;

    public function __construct(A $a, $foo = 'bar') {
        $this->a = $a;
        $this->foo = $foo;
    }
}

class MyDirectoryIterator extends \DirectoryIterator {}

class MyDirectoryIterator2 extends \DirectoryIterator {
    public function __construct($f) {
        parent::__construct($f);
    }
}

trait MyTrait {
    public function foo() {}
}

class MyDirectoryIteratorWithTrait extends \DirectoryIterator {
    use MyTrait;
}

class MyObj {
    private $foo;
    public function setFoo($foo) {
        $this->foo = $foo;
    }
    public function getFoo() {
        return $this->foo;
    }
}

class NoConstructor {
    public $a = 'b';
}

class NullScalar {
    public $string;
    public function __construct($string = null) {
        $this->string = $string;
    }
}

class NullScalarNested {
    public $nullScalar;
    public function __construct(NullScalar $nullScalar) {
        $this->nullScalar = $nullScalar;
    }
}

class ParamRequiresArgs {
    public $a;

    public function __construct(D $d, RequiresConstructorArgsA $a) {
        $this->a = $a;
    }
}

class RequiresConstructorArgsA {
    public $foo;
    public $bar;
    public function __construct($foo, $bar) {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}

class RequiresConstructorArgsB {
    public $a;
    public $foo;
    public $bar;
    public function __construct(A $a, $foo, $bar) {
        $this->a = $a;
        $this->foo = $foo;
        $this->bar = $bar;
    }
}

class Shared {
    public $uniq;

    public function __construct() {
        $this->uniq = uniqid();
    }
}

class SharedInstanceTest1 {
    public $shared;

    public function __construct(Shared $shared) {
        $this->shared = $shared;
    }
}

class SharedInstanceTest2 {
    public $shared;
    public function __construct(Shared $shared) {
        $this->shared = $shared;
    }
}

class TestCall {
    public $isCalled = false;

    public function callMe() {
        $this->isCalled = true;
    }
}

class TestCall2 {
    public $foo;
    public $bar;
    public function callMe($foo, $bar) {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}

class TestCall3 {
    public $a;
    public function callMe(A $a) {
        $this->a = $a;
    }
}

interface TestInterface {}

class TestSharedInstancesTop {
    public $share1;
    public $share2;

    public function __construct(SharedInstanceTest1 $share1, SharedInstanceTest2 $share2) {
        $this->share1 = $share1;
        $this->share2 = $share2;
    }
}

class Y {
    public $name;
    public function __construct($name) {
        $this->name = $name;
    }
}

class Y1 {
    public $y;

    public function __construct(Y $y) {
        $this->y = $y;
    }
}

class Y3 extends Y {}
