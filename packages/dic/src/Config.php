<?php

namespace Aspire\Di;

class Config
{
    /** @var RuleProvider[] */
    protected $ruleProviders;

    /** @var bool */
    protected $autowiring;

    /** @var Rule[] */
    protected $rules = [];

    public function __construct(array $ruleProviders = [], bool $autowiring = true)
    {
        $this->ruleProviders = $ruleProviders;
        $this->autowiring = $autowiring;
    }

    /**
     * Fetch rules from all registered providers, checking for collisions
     *
     * @return Config
     * @throws Exception\ContainerException on invalid providers/rules or rule collisions
     */
    public function load()
    {
        foreach ($this->ruleProviders as $provider) {
            $class = \get_class($provider);
            if (!($provider instanceof RuleProvider)) {
                throw new Exception\ContainerException("Invalid rule provider: instance of $class");
            }

            foreach ($provider->rules() as $rule) {
                $ruleId = static::isRegex($rule->id)
                    ? $rule->id // don't touch it
                    : static::normalizeName($rule->id);

                if (isset($this->rules[$ruleId])) {
                    throw new Exception\ContainerException("Rule collision in $class: $ruleId is already defined");
                }
                $this->rules[$ruleId] = $rule;
            }
        }

        return $this;
    }

    /**
     * Returns the rule that will be applied to the class $id during make().
     *
     * @param string $id The name of the ruleset to get - can be a class or not
     * @return array|Rule Rule that applies when instantiating the given name
     */
    public function getRule(string $id)
    {
        if (!$this->rules) {
            $this->load();
        }

        // first, check for exact match
        $normalname = self::normalizeName($id);
        if (isset($this->rules[$normalname])) {
            return $this->rules[$normalname];
        }
        // next, look for a rule where:
        foreach ($this->rules as $key => $rule) {
            $matches = [];
            if ($key !== '*' // it's not the default rule,
                && (
                    ( // its name is a parent class of what we're looking for,
                        \is_subclass_of($id, $key)
                        // and it applies to subclasses
                        && (!isset($rule->inherit) || $rule->inherit === true)
                    )
                    // or the id is a matching regex
                    || (static::isRegex($key) && \preg_match($key, $id, $matches))
                )
            ) {
                return $matches ? ['rule' => $rule, 'matches' => $matches] : $rule;
            }
        }
        // if we get here, return the default rule if it's set
        return (isset($this->rules['*'])) ? $this->rules['*'] : [];
    }

    /**
     * @param string $name
     * @return string lowercased classname without leading backslash
     */
    protected static function normalizeName(string $name): string
    {
        return \strtolower(\ltrim($name, '\\'));
    }

    /**
     * @param string $name
     * @return bool
     */
    protected static function isRegex(string $name): bool
    {
        return ($name[0] === '/' && $name[-1] === '/');
    }
}
