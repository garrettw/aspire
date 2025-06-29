<?php

/**
 * @description Aspire's Inversion-of-Control dependency injection container, based on Dice
 *
 * @author      Tom Butler tom@r.je
 * @author      Garrett Whitehorn http://garrettw.net/
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 */

namespace Aspire\Di;

class OldContainer implements \Psr\Container\ContainerInterface
{
    private $config = null;
    private $closures = [];
    private $instances = [];

    public function __construct(Config $config = null)
    {
        $this->config = $config;
    }

    /**
     * Provides a way to get at the Config object after container creation,
     * in case you didn't save it to a variable before instantiating the container.
     * The use case for this is when rules need to be adjusted later in execution.
     *
     * @return Config
     */
    public function config(): Config
    {
        return $this->config;
    }

    /**
     * This is a function defined by the PSR interface where we can check if
     * a given object can theoretically be provided by the container or not.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return (\class_exists($id)
            || ($this->hasConfig()
                && $this->config->getRule($id) != $this->config->getRule('*'))
        );
    }

    /**
     * Answers the question "do we have a configuration object?"
     *
     * @return bool
     */
    public function hasConfig(): bool
    {
        return isset($this->config);
    }

    /**
     * This is the main object creation method you would call, and it is named
     * "get()" to align with the PSR interface.
     * Returns a fully constructed object based on $id, whether one exists already or not
     * @todo method signature does not match PSR. $args will have to be supplied some other way.
     *
     * @param string $id
     * @throws Exception\NotFoundException if there's no way create the desired object
     * @throws Exception\ContainerException if instantiation fails for some other reason
     * @return object
     */
    public function get(string $id, array $args = []): object
    {
        if (!$this->has($id)) {
            throw new Exception\NotFoundException(
                "Could not instantiate $id: class/rule not found"
            );
        }

        if (empty($args) && !empty($this->instances[$id])) {
            // we've already created a shared instance so return it to save the closure call.
            return $this->instances[$id];
        }

        try {
            return $this->make($id, $args);
        } catch (\Exception $e) {
            // we just want the exception to be our brand
            throw new Exception\ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Constructs and returns an object based on $id using $args and $share as constructor arguments
     *
     * @param string $id The name of the class to instantiate
     * @param array $args An array with any additional arguments to be passed into the constructor
     * @param array $share A list of defined in shareInstances for objects higher up the object graph; should only be used internally
     * @return object A fully constructed object based on the specified input arguments
     */
    public function make(string $id, array $args = [], array $share = [])
    {
        // we either need a new instance or just don't have one stored
        // but if we have the closure stored that creates it, call that
        if (!empty($this->closures[$id])) {
            return $this->closures[$id]($args, $share);
        }

        $rule = ($this->hasConfig()) ? $this->config->getRule($id) : [];
        $class = new \ReflectionClass(isset($rule['instanceOf']) ? $rule['instanceOf'] : $id);

        $closure = $this->prepare(\ltrim($id, '\\'), $rule, $class);

        if (isset($rule['shareInstances'])) {
            $closure = function(array $args, array $share) use ($closure, $rule) {
                foreach ($rule['shareInstances'] as $instance) {
                    $share[] = $this->get($instance, [], $share);
                }
                return $closure($args, $share);
            };
        }

        // When $rule['call'] is set, wrap the closure in another closure which calls the required methods after constructing the object.
        // By putting this in a closure, the loop is never executed unless call is actually set.
        if (isset($rule['call'])) {
            $closure = function(array $args, array $share) use ($closure, $class, $rule, $id) {
                // Construct the object using the original closure
                $object = $closure($args, $share);
                foreach ($rule['call'] as $call) {
                    // Generate the method arguments using prepareParams() and call the returned closure
                    // (in php7 it will be ()() rather than __invoke)
                    $shareRule = ['shareInstances' => isset($rule['shareInstances']) ? $rule['shareInstances'] : []];
                    $callMeMaybe = isset($call[1]) ? $call[1] : [];
                    $params = $this->prepareParams($class->getMethod($call[0]), $shareRule)(($this->expand($callMeMaybe)), $share);
                    $return = $object->{$call[0]}(...$params);
                    if (isset($call[2])) {
                        if ($call[2] === Config::CHAIN_CALL) {
                            if (!empty($rule['shared'])) {
                                $this->instances[$id] = $return;
                            }
                            if (\is_object($return)) {
                                $class = new \ReflectionClass(\get_class($return));
                            }
                            $object = $return;
                        } elseif (\is_callable($call[2])) {
                            \call_user_func($call[2], $return);
                        }
                    }
                }
                return $object;
            };
        }

        $this->closures[$id] = $closure;
        return $this->closures[$id]($args, $share);
    }

    /**
     * Makes a closure that can generate a fresh instance of $id later.
     */
    private function prepare(string $id, array $rule, \ReflectionClass $class)
    {
        $constructor = $class->getConstructor();
        // Create parameter-generating closure in order to cache reflection on the parameters.
        // This way $reflectmethod->getParameters() only ever gets called once.
        $params = ($constructor) ? $this->prepareParams($constructor, $rule) : null;

        // PHP throws a fatal error rather than an exception when trying to instantiate an interface.
        // Detect it and throw an exception instead.
        if ($class->isInterface()) {
            $closure = function () {
                throw new \InvalidArgumentException('Cannot instantiate interface');
            };
        }
        // Get a closure based on the type of object being created: shared, normal, or constructorless
        elseif ($params) {
            // This class has dependencies, call the $params closure to generate them based on $args and $share
            $closure = function(array $args, array $share) use ($class, $params) {
                $cn = $class->name;
                return new $cn(...$params($args, $share));
            };
        } else {
            $closure = function() use ($class) {
                // No constructor arguments, just instantiate the class
                $cn = $class->name;
                return new $cn();
            };
        }

        if (isset($rule['shared']) && $rule['shared'] === true) {
            $closure = function(array $args, array $share) use ($id, $class, $constructor, $params, $closure) {
                if ($class->isInternal()) {
                    // Internal classes may not be able to be constructed without calling the constructor
                    // and will not suffer from issue #7 so construct them normally.
                    $this->instances[$class->name] = $this->instances['\\' . $class->name] = $closure($args, $share);
                } else {
                    // Shared instance: create without calling constructor (and write to \$id and $id, see issue #68)
                    $this->instances[$id] = $this->instances['\\' . $id] = $class->newInstanceWithoutConstructor();
                    // Now call constructor after constructing all dependencies. Avoids problems with cyclic references (issue #7)
                    if ($constructor) {
                        $constructor->invokeArgs($this->instances[$id], $params($args, $share));
                    }
                }
                return $this->instances[$id];
            };
        }
        return $closure;
    }

    private function prepareParams(\ReflectionMethod $method, array $rule)
    {
        $paramInfo = []; // Caches some information about the parameter so (slow) reflection isn't needed every time
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            // get the class hint of each param, if there is one
            $class = ($type instanceof \ReflectionNamedType && !$type->isBuiltIn())
                ? $type->getName()
                : null;
            // determine if the param can be null, if we need to substitute a
            // different class, or if we need to force a new instance for it
            $paramInfo[] = [
                $class,
                $param,
                isset($rule['substitutions']) && \array_key_exists($class, $rule['substitutions']),
            ];
        }

        // TODO: https://github.com/Level-2/Dice/commit/bb424179f4fc331d989e6b2145a33dc37cc7daff
        // Return a closure that uses the cached information to generate the arguments for the method
        return function(array $args, array $share = []) use ($paramInfo, $rule) {
            // Now merge all the possible parameters: user-defined in the rule via constructParams,
            // shared instances, and the $args argument from $this->make()
            if (!empty($share) || isset($rule['constructParams'])) {
                $args = \array_merge(
                    $args,
                    (isset($rule['constructParams'])) ? $this->expand($rule['constructParams'], $share) : []
                );
            }
            $parameters = [];
            // Now find a value for each method parameter
            foreach ($paramInfo as $pi) {
                list($class, $param, $sub) = $pi;
                // First, loop through $args and see if each value can match the current parameter based on type hint
                if (!empty($args)) { // This if statement actually gives a ~10% speed increase when $args isn't set
                    foreach ($args as $i => $arg) {
                        // TODO: above and then https://github.com/Level-2/Dice/commit/8cd036faa9d3bc3c2dffa56aff08e978e2985bb5
                        // For variadic parameters, provide remaining $args
                        if ($param->isVariadic()) {
                            $parameters = \array_merge($parameters, $args);
                            continue 2;
                        }
                        if ($class !== null
                            && ($arg instanceof $class || ($arg === null && $param->allowsNull()))
                        ) {
                            // The argument matches, store and remove from $args so it won't wrongly match another parameter
                            $parameters[] = \array_splice($args, $i, 1)[0];
                            continue 2; //Move on to the next parameter
                        }
                    }
                }
                if (!empty($share)) {
                    foreach ($share as $i => $arg) {
                        if ($class && ($arg instanceof $class || ($arg === null && $param->allowsNull()))) {
                            // The argument matched, store it and remove it from $args so it won't wrongly match another parameter
                            $parameters[] = array_splice($share, $i, 1)[0];
                            // Move on to the next parameter
                            continue 2;
                        }
                    }
                }
                // When nothing from $args matches but a class is type hinted, create an instance to use, using a substitution if set
                if ($class !== null) {
                    try {
                        if ($sub) {
                            $parameters[] = $this->expand($rule['substitutions'][$class], $share, true);
                        } else {
                            $parameters[] = (!$param->allowsNull()) ? $this->get($class, [], $share) : null;
                        }
                    } catch (\InvalidArgumentException $e) {}
                    continue;
                }

                // There is no type hint, so take the next available value from $args (and remove from $args to stop it being reused)
                // Also support PHP 7 scalar type hinting -- is_a('string', 'foo') doesn't work so this is a hacky workaround
                if (!empty($args)) {
                    if ($param->getType()) {
                        for ($i = 0, $count = \count($args); $i < $count; $i++) {
                            if (\call_user_func('is_' . $param->getType(), $args[$i])) {
                                $parameters[] = \array_splice($args, $i, 1)[0];
                                break;
                            }
                        }
                        continue;
                    }
                    $parameters[] = $this->expand(\array_shift($args));
                    continue;
                }
                // There's no type hint and nothing left in $args, so provide the default value or null
                $parameters[] = ($param->isDefaultValueAvailable()) ? $param->getDefaultValue() : null;
            }
            return $parameters;
        };
    }

    /**
     * Looks for Config::INSTANCE, Config::GLOBAL or Config::CONSTANT array keys in $param, and when found, returns an object based on the value.
     * See {@link https://r.je/dice.html#example3-1}
     *
     * @param string|array $param
     * @param array $share Array of instances from 'shareInstances'; required for calls to `create`
     * @param bool $createFromString
     * @return mixed
     */
    private function expand($param, array $share = [], bool $createFromString = false)
    {
        if (!\is_array($param)) {
            // doesn't need any processing
            return (\is_string($param) && $createFromString) ? $this->get($param) : $param;
        }
        if (!isset($param[Config::INSTANCE])) {
            if (isset($param[Config::GLOBAL])) {
                return $GLOBALS[$param[Config::GLOBAL]];
            }
            if (isset($param[Config::CONSTANT])) {
                return \constant($param[Config::CONSTANT]);
            }
            // not a lazy instance, so recursively search for any Config::INSTANCE keys on deeper levels
            foreach ($param as $name => $value) {
                $param[$name] = $this->expand($value, $share);
            }
            return $param;
        }
        if ($param[Config::INSTANCE] === Config::SELF) {
            return $this;
        }
        $args = isset($param['params']) ? $this->expand($param['params']) : [];
        // for [Config::INSTANCE => ['className', 'methodName'] construct the instance before calling it
        if (\is_array($param[Config::INSTANCE])) {
            $param[Config::INSTANCE][0] = $this->expand($param[Config::INSTANCE][0], $share, true);
        }
        if (\is_callable($param[Config::INSTANCE])) {
            // it's a lazy instance formed by a function. Call or return the value stored under the key Config::INSTANCE
            if (isset($param['params'])) {
                return \call_user_func_array($param[Config::INSTANCE], $args);
            }
            return \call_user_func($param[Config::INSTANCE]);
        }
        if (\is_string($param[Config::INSTANCE])) {
            // it's a lazy instance's class name string
            return $this->get($param[Config::INSTANCE], \array_merge($args, $share));
        }
        // if it's not a string, it's malformed. *shrug*
    }
}
