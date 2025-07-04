<?php

declare(strict_types=1);

namespace Aspire\Di;

class Rule
{
    /** @var array Just to hold preg match info for regex rules */
    public array $matches = [];

    /**
     * @param string $id The ID of the rule, which can be a FQCN, a regex, or '*' to match all classes.
     * @param ?string $className The FQCN of the actual class to instantiate.
     *  Used with interfaces and named instances. If null, $id is assumed to be the desired class.
     * @param bool $shared Whether this class instance should be shared (singleton).
     * @param array $constructParams Parameters to pass to the constructor of the class.
     * @param array $substitutions Maps interfaces or parent class names to an actual class that should be passed.
     * @param array $shareInstances Instances that are singletons only within the current object graph.
     * @param array $call Methods to call on the instance after construction, in the form of ['methodName' => [args]].
     * @param bool $inherit Whether to allow this rule to apply to child classes.
     */
    public function __construct(
        public string $id,
        public ?string $className = null,
        public bool $shared = false,
        public array $constructParams = [],
        public array $substitutions = [],
        public array $shareInstances = [],
        public array $call = [],
        public bool $inherit = true,
    ) {}
}
