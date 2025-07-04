<?php

declare(strict_types=1);

/**
 * @description Aspire's Inversion-of-Control dependency injection container
 *
 * @author      Garrett Whitehorn http://garrettw.net/
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 */

namespace Aspire\Di;

use Aspire\Di\Exception\ContainerException;
use Aspire\Di\Exception\NotFoundException;

class Container implements \Psr\Container\ContainerInterface
{
    /** @var callable[] functions that make objects */
    protected $closures = [];

    /** @var object[] shared objects we've created */
    protected $instances = [];

    public function __construct(protected Config $config) {}

    /**
     * The basic PSR-11 function to retrieve a thing from the container.
     *
     * If you need to pass one-time parameters to the object's constructor,
     * use make() instead.
     *
     * @param string $id the name of the thing you want to get
     * @return mixed the thing you wanted
     * @throws NotFoundException if we don't have your thing
     * @throws ContainerException if anything else went wrong
     */
    public function get(string $id)
    {
        return $this->make(ltrim($id, '\\'));
    }

    /**
     * Since we're not a Service Locator, we've implemented this function a bit differently.
     * Its purpose here is actually to tell whether we can make the thing you want or not.
     * @throws ContainerException
     */
    public function has(string $id): bool
    {
        return ($this->config->isAutowiring() && \class_exists($id))
            || !in_array($this->config->getRule($id)->id, ['*', 'empty']);
    }

    /**
     * Our beefed-up get() function where you can pass in construct-time params.
     *
     * @param string $id the name of the thing you want to get
     * @param array $params optional constructor params
     * @return mixed the thing you wanted
     * @throws NotFoundException if we don't have your thing
     * @throws ContainerException if anything else went wrong
     */
    public function make(string $id, array $params = [], array $share = []): mixed
    {
        if (empty($params) && !empty($this->instances[$id])) {
            // we've already created a shared instance so return it to save the closure call.
            return $this->instances[$id];
        }

        if (!$this->has($id)) {
            throw new NotFoundException(
                "Could not instantiate $id: class/rule not found"
            );
        }

        try {
            if (!isset($this->closures[$id])) {
                $this->closures[$id] = $this->makeClosure($id, $this->config->getRule($id));
            }
            return $this->closures[$id]($params, $share);
        } catch (\Exception $e) {
            // we just want the exception to be our brand if anything happens
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Call any callable using the container's dependency resolution.
     *
     * @param callable $callable
     * @param array $params any params you need to explicitly specify
     * @param ?Rule $rule a rule to apply during parameter resolution
     * @return mixed the result from calling the callable
     * @throws ContainerException
     */
    public function call(callable $callable, array $params = [], ?Rule $rule = null): mixed
    {
        try {
            $reflection = new \ReflectionFunction($callable);
            $paramsFunc = $this->getParams($reflection, $rule ?? new Rule(\Closure::class));
            return $callable(...$paramsFunc($params, []));
        } catch (\ReflectionException $e) {
            throw new ContainerException("Could not inspect callable: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $id
     * @param Rule $rule
     * @return \Closure A closure that will create the object when called
     * @throws \ReflectionException
     */
    protected function makeClosure($id, $rule)
    {
        // Reflect the class and constructor; this should only ever be done once per class and get cached
        $class = new \ReflectionClass($rule->className ?? $id);
        $constructor = $class->getConstructor();
        // Create a parameter-generating function to cache reflection on the parameters.
        // This way $reflect->getParameters() only ever gets called once
        $paramsFunc = $constructor ? $this->getParams($constructor, $rule) : null;

        /**
         * Closure level 1
         */
        // PHP throws a fatal error when trying to instantiate an interface; detect it and throw an exception instead
        if ($class->isInterface()) {
            $closure = static function () use ($class) {
                throw new \InvalidArgumentException(
                    "Cannot instantiate interface {$class->name}. "
                    . "Did you forget to configure a concrete implementation?"
                );
            };
        } elseif ($paramsFunc) {
            $closure = static function (array $args, array $share) use ($class, $paramsFunc) {
                // This class has dependencies, call $paramsFunc to generate them based on $args and $share
                return new $class->name(...$paramsFunc($args, $share));
            };
        } else {
            $closure = static function () use ($class) {
                // No constructor arguments, just instantiate the class
                return new $class->name;
            };
        }

        /**
         * Closure level 2
         *
         * If this object is to be a singleton and is not an internal class, skip closure 1
         * and quietly create the instance without invoking the constructor until dependencies are ready
         * to avoid cyclic references. But if it is internal, we have to call closure 1 like normal.
         */
        if (!empty($rule->shared)) {
            $closure = function (array $args, array $share) use ($id, $class, $constructor, $paramsFunc, $closure) {
                if ($id === $this::class) {
                    // If we're trying to instantiate the container itself, and the container has a shared rule,
                    // assume we want $this rather than a new container just for the object graph
                    return $this;
                }
                try {
                    // Create the instance without calling the constructor
                    $this->instances[$id] = $class->newInstanceWithoutConstructor();
                    // Now call the constructor after constructing all the dependencies.
                    // This avoids problems with cyclic references
                    $constructor?->invokeArgs($this->instances[$id], $paramsFunc($args, $share));
                } catch (\ReflectionException $e) {
                    // Class is internal and therefore cannot be instantiated without calling the constructor
                    $this->instances[$class->name] = $closure($args, $share);
                }
                return $this->instances[$id];
            };
        }

        /**
         * Closure level 3
         *
         * If there are shared instances, create them
         * and add them to the list of shared instances coming from higher up the object graph;
         * then call the previous closure
         */
        if ($rule->shareInstances) {
            $closure = function (array $args, array $share) use ($closure, $rule) {
                foreach ($rule->shareInstances as $instance) {
                    $share[] = $this->make($instance, [], $share);
                }
                return $closure($args, $share);
            };
        }

        /**
         * Closure level 4
         *
         * If there are post-construct calls to make, do them after constructing the object
         */
        if ($rule->call) {
            $closure = function (array $args, array $share) use ($closure, $class, $rule, $id) {
                // Construct the object using the original closure
                $object = $closure($args, $share);

                foreach ($rule->call as $call) {
                    // Generate the method arguments using getParams() and call the returned closure
                    $params = $this->getParams(
                        $class->getMethod($call[0]),
                        new Rule(id: '', shareInstances: $rule->shareInstances)
                    )($this->expandParams($call[1] ?? []), $share);
                    $return = $object->{$call[0]}(...$params);
                    if (isset($call[2])) {
                        if ($call[2] === self::CHAIN_CALL) { // TODO: implement this
                            if (!empty($rule['shared'])) {
                                $this->instances[$id] = $return;
                            }
                            if (is_object($return)) {
                                $class = new \ReflectionClass(get_class($return));
                            }
                            $object = $return;
                        }
                        elseif (is_callable($call[2])) {
                            call_user_func($call[2], $return);
                        }
                    }
                }
                return $object;
            };
        }
        return $closure;
    }

    /**
     * Returns a closure that generates arguments for $method based on $rule and any $params passed into the closure
     *
     * @param \ReflectionFunctionAbstract $function The function or method for which to generate parameters
     * @param Rule $rule
     * @return \Closure A closure that uses the cached information to generate the arguments for the method
     */
    protected function getParams($function, $rule)
    {
        // Cache some information about the parameter in $paramInfo so reflection isn't needed every time
        $paramInfo = [];
        foreach ($function->getParameters() as $param) {
            $type = $param->getType();
            $class = ($type instanceof \ReflectionNamedType && !$type->isBuiltIn()) ? $type->getName() : null;
            $paramInfo[] = [
                $class,
                $param,
                isset($rule->substitutions) && array_key_exists($class, $rule->substitutions)
            ];
        }

        // Return a closure that uses the cached information to generate the arguments for the method
        return function (array $args, array $share = []) use ($paramInfo, $rule) {
            // If the rule has constructParams set, construct any classes referenced and use them as $args
            if ($rule->constructParams) {
                $args = array_merge($args, $this->expandParams($rule->constructParams, $share));
            }

            // Array of matched parameters
            $parameters = [];

            // Fnd a value for each method argument
            foreach ($paramInfo as [$class, $param, $sub]) {
                /**
                 * @var ?string $class
                 * @var \ReflectionParameter $param
                 * @var bool $sub
                 */
                // Loop through $args and see whether each value can match the current parameter based on type hint
                if ($args && ($match = $this->matchParam($param, $class, $args)) !== false) {
                    $parameters[] = $match;
                }
                // Do the same with $share
                elseif (($copy = $share) && ($match = $this->matchParam($param, $class, $copy)) !== false) {
                    $parameters[] = $match;
                }
                // When nothing from $args or $share matches but a class is type hinted,
                // create an instance to use, using a substitution if set
                elseif ($class) {
                    try {
                        if ($sub) {
                            $parameters[] = $this->expandSub($class, $rule->substitutions[$class], $share);
                        } else {
                            $parameters[] = !$param->allowsNull() ? $this->make($class, [], $share) : null;
                        }
                    } catch (\InvalidArgumentException $e) {
                        // Silence this exception
                    }
                }
                // Support PHP 7+ scalar type hinting
                // is_a('string', 'foo') doesn't work so our hacky workaround is call_user_func('is_' . $type, '')
                // Find a match in $args for scalar types
                elseif ($args && $param->getType()) {
                    foreach ($args as $i => $arg) {
                        if (call_user_func('is_' . $param->getType()->getName(), $arg)) {
                            $parameters[] = array_splice($args, $i, 1)[0];
                            break;
                        }
                    }
                }
                elseif ($args) {
                    $parameters[] = $this->expandParams(array_shift($args));
                }
                // For variadic parameters, provide remaining $args
                elseif ($param->isVariadic()) {
                    $parameters = array_merge($parameters, $args);
                }
                // There's no type hint and nothing left in $args, provide the default value or null
                else {
                    $parameters[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                }
            }
            return $parameters;
        };
    }

    /**
     * Looks for Dice::INSTANCE, Dice::GLOBAL or Dice::CONSTANT array keys in $param and when found returns an object based on the value see {@link https:// r.je/dice.html#example3-1}
     * @param mixed $param Strings and arrays will be processed, everything else is passed through
     * @param array $share Array of instances from 'shareInstances', required for calls to `make`
     * @param bool $createFromString
     * @return mixed
     */
    protected function expandParams($param, $share = [], $createFromString = false) {
        if (is_array($param)) {
            // If a rule specifies Dice::INSTANCE, look up the relevant instance
            if (isset($param[self::INSTANCE])) {
                // Check for 'params' which allows parameters to be sent to the instance when it's created
                // Either as a callback method or to the constructor of the instance
                $args = isset($param['params']) ? $this->expandParams($param['params']) : [];

                // Support Dice::INSTANCE by creating/fetching the specified instance
                if (is_array($param[self::INSTANCE])) {
                    $param[self::INSTANCE][0] = $this->expandParams($param[self::INSTANCE][0], $share, true);
                }
                if (is_callable($param[self::INSTANCE])) {
                    return call_user_func($param[self::INSTANCE], ...$args);
                }
                return $this->make($param[self::INSTANCE], array_merge($args, $share));
            }

            if (isset($param[self::GLOBAL])) {
                return $GLOBALS[$param[self::GLOBAL]];
            }

            if (isset($param[self::CONSTANT])) {
                return constant($param[self::CONSTANT]);
            }

            foreach ($param as $name => $value) {
                $param[$name] = $this->expandParams($value, $share);
            }
        }

        return (is_string($param) && $createFromString) ? $this->make($param) : $param;
    }

    /**
     * @param string $class
     * @param callable|string|object $substitution
     * @param $share
     * @return mixed
     * @throws ContainerException
     * @throws NotFoundException
     * @throws \ReflectionException
     */
    protected function expandSub($class, $substitution, $share)
    {
        if (is_callable($substitution)) {
            // If the substitution is a callable, first we should reflect on its return type to make sure it matches the class
            if (is_string($substitution) && str_contains($substitution, '::')) {
                $substitution = explode('::', $substitution, 2);
            }
            if (is_array($substitution)) {
                $reflection = new \ReflectionMethod($substitution[0], $substitution[1]);
            } else {
                $reflection = new \ReflectionFunction($substitution);
            }

            $returnType = $reflection->getReturnType();
            if ($returnType && !$returnType->isBuiltin() && $returnType->getName() !== $class) {
                throw new \InvalidArgumentException(
                    "Substitution callable must return an instance of $class, "
                    . "but it returns an instance of {$returnType->getName()} instead."
                );
            }
            // We've done as many safety checks as we can, so call it
            $result = $this->call($substitution, [], null, $share);

            // If there was no return type to check but we got back something bad, throw an exception
            if (!is_object($result) || !is_a($result, $class)) {
                throw new \InvalidArgumentException(
                    "Substitution callable must return an instance of $class, "
                    . "but it returned an instance of " . get_debug_type($result) . " instead."
                );
            }
        }

        if (is_string($substitution) && $this->has($substitution)) {
            // it's a class name or named instance
            return $this->make($substitution, [], $share);
        }

        // it's not something we need to handle, so pass through
        return $substitution;
    }

    /**
     * Looks through the array $search for any object that can be used to fulfil $param
     * The original array $search is modified so must be passed by reference.
     * @param \ReflectionParameter $param The parameter to match against
     * @param ?string $class The class name to match against, or null if no class is type hinted
     * @param array $search The array of arguments to search through
     */
    protected function matchParam($param, $class, &$search) {
        foreach ($search as $i => $arg) {
            if ($class && ($arg instanceof $class || ($arg === null && $param->allowsNull()))) {
                // The argument matched, return it and remove it from $search so it won't wrongly match another parameter
                return array_splice($search, $i, 1)[0];
            }
        }
        return false;
    }
}
