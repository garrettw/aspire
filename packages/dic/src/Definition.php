<?php

declare(strict_types=1);

namespace Aspire\Di;

class Definition
{
    /**
     * @param bool $singleton Whether this class instance should be unique in the container.
     * @param bool $strict Whether to prevent this rule from applying to child classes.
     * @param string|callable|object|null $substitute The FQCN of the actual class to instantiate,
     *  a factory callable to generate the instance, or a pre-existing instance.
     * @param array $withParams Parameters to pass to the constructor of the class.
     * @param array $singletonsInTree Classes whose instances are singletons only within the current object graph.
     * @param ?callable $call Method to call on the instance after construction. If an object is returned, this is
     *  considered to be a decorator and the instance will be replaced with the return value.
     */
    public function __construct(
        public bool $singleton = false,
        public bool $strict = false,
        public $substitute = null,
        public array $withParams = [],
        public array $singletonsInTree = [],
        public $call = null,
    ) {}
}
