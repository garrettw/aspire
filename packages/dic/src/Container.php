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
        return $this->make($id);
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
                $this->closures[$id] = $this->makeClosure(
                    ltrim($id, '\\'),
                    $this->config->getRule($id)
                );
            }
            return $this->closures[$id]($params, $share);
        } catch (\Exception $e) {
            // we just want the exception to be our brand if anything happens
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Call any callable using the container's autowiring.
     *
     * @param callable $callable
     * @param array $params any params you need to explicitly specify
     * @return mixed the result from the callable
     */
    public function call(callable $callable, array $params = [])
    {
        if (!$this->autowiring) {
            return null;
        }

        // TODO: incomplete
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
            $closure = static function () {
                throw new \InvalidArgumentException('Cannot instantiate an interface');
            };
        } elseif ($paramsFunc) {
            // Get a closure based on the type of object being created: Shared, normal or constructorless
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
         */
        if (!empty($rule->shared)) {
            $closure = function (array $args, array $share) use ($id, $class, $constructor, $paramsFunc, $closure) {
                try {
                    // Create the instance without calling the constructor
                    $this->instances[$id] = $class->newInstanceWithoutConstructor();
                    // Now call the constructor after constructing all the dependencies.
                    // This avoids problems with cyclic references
                    $constructor?->invokeArgs($this->instances[$id], $paramsFunc($args, $share));
                } catch (\ReflectionException $e) {
                    // Class is internal and cannot be instantiated without calling the constructor
                    $this->instances[$class->name] = $closure($args, $share); // TODO: normalize name?
                }
                return $this->instances[$id];
            };
        }

        /**
         * Closure level 3
         */
        // If there are shared instances, create them and merge them with shared instances higher up the object graph
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
         */
        // When $rule->call is set, wrap the closure in another closure which will call the required methods
        // after constructing the object.
        // By putting this in a closure, the loop is never executed unless call is actually set.
        if ($rule->call) {
            $closure = function (array $args, array $share) use ($closure, $class, $rule, $id) {
                // Construct the object using the original closure
                $object = $closure($args, $share);

                foreach ($rule->call as $call) {
                    // Generate the method arguments using getParams() and call the returned closure
                    $params = $this->getParams(
                        $class->getMethod($call[0]),
                        ['shareInstances' => $rule->shareInstances]
                    )($this->expand($call[1] ?? []), $share);
                    $return = $object->{$call[0]}(...$params);
                    if (isset($call[2])) {
                        if ($call[2] === self::CHAIN_CALL) {
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
     * @param \ReflectionMethod $method The method for which to generate parameters
     * @param Rule $rule
     * @return \Closure A closure that uses the cached information to generate the arguments for the method
     */
    protected function getParams($method, $rule)
    {
        // Cache some information about the parameter in $paramInfo so reflection isn't needed every time
        $paramInfo = [];
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            $class = ($type instanceof \ReflectionNamedType && !$type->isBuiltIn()) ? $type->getName() : null;
            $paramInfo[] = [
                $class,
                $param,
                isset($rule['substitutions']) && array_key_exists($class, $rule['substitutions'])
            ];
        }

        // Return a closure that uses the cached information to generate the arguments for the method
        return function (array $args, array $share = []) use ($paramInfo, $rule) {
            // If the rule has constructParams set, construct any classes reference and use them as $args
            if ($rule->constructParams) {
                $args = array_merge($args, $this->expand($rule->constructParams, $share));
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
                            $parameters[] = $this->expand($rule['substitutions'][$class], $share, true);
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
                    $parameters[] = $this->expand(array_shift($args));
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
}
