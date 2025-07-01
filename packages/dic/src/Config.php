<?php

declare(strict_types=1);

namespace Aspire\Di;

use Aspire\Di\Exception\ContainerException;

class Config
{
    /** @var Rule[] */
    protected $rules = [];

    /**
     * @param RuleProvider[] $ruleProviders
     * @param bool $autowiring
     */
    public function __construct(
        protected array $ruleProviders = [],
        protected bool $autowiring = true,
    ) {
        foreach ($ruleProviders as $provider) {
            if (!($provider instanceof RuleProvider)) {
                throw new \InvalidArgumentException(
                    'Invalid rule provider: ' . \get_class($provider)
                );
            }
        }
    }

    /**
     * Fetch rules from all registered providers, checking for collisions
     *
     * @return Config
     * @throws Exception\ContainerException on invalid providers/rules or rule collisions
     */
    public function load(): static
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
     * @return Rule that applies when instantiating the given name
     * @throws ContainerException
     */
    public function getRule(string $id): Rule
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
            if ($key === '*') {
                // skip the default rule, we'll return it at the end if nothing else matches
                continue;
            }
            $matches = [];
            if (
                (   // the rule can apply to subclasses
                    ($rule->inherit === true)
                    // and its name is a parent class of what we're looking for,
                    && \is_subclass_of($id, $key)
                )
                // or the rule is a regex and the id matches it
                || (static::isRegex($key) && \preg_match($key, $id, $matches))
            ) {
                $rule->matches = $matches;
                return $rule;
            }
        }
        // if we get here, return the default rule if it's set
        return $this->rules['*'] ?? new Rule('empty');
    }

    public function isAutowiring(): bool
    {
        return $this->autowiring;
    }

    /**
     * @param string $name
     * @return string lowercased classname without leading backslash
     */
    protected static function normalizeName($name)
    {
        return \strtolower(\ltrim($name, '\\'));
    }

    /**
     * @param string $name
     * @return bool
     */
    protected static function isRegex($name)
    {
        return $name[0] === '/' && $name[-1] === '/';
    }
}
