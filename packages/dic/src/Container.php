<?php

/**
 * @description Aspire's Inversion-of-Control dependency injection container
 *
 * @author      Garrett Whitehorn http://garrettw.net/
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 */

namespace Aspire\Di;

class Container implements \Psr\Container\ContainerInterface
{
    /** @var Config */
    protected $config;

    /** @var callable[] functions that make objects */
    protected $closures = [];

    /** @var object[] shared objects we've created */
    protected $instances = [];

    /**
     * @param Config|null $config
     */
    public function __construct(Config $config = null)
    {
        $this->config = $config;
    }

    /**
     * The basic PSR-11 function to retrieve a thing from the container.
     *
     * If you need to pass one-time parameters to the object's constructor,
     * use make() instead.
     *
     * @param string $id the name of the thing you want to get
     * @return mixed the thing you wanted
     * @throws Exception\NotFoundException if we don't have your thing
     * @throws Exception\ContainerException if anything else went wrong
     */
    public function get(string $id)
    {
        return $this->make($id);
    }

    public function has(string $id): bool
    {
        return \class_exists($id)
            || (isset($this->config)
                && $this->config->getRule($id) != $this->config->getRule('*'));
        // TODO: do a better job of resolving the id using rules before checking the final time
    }

    /**
     * Our beefed-up get() function where you can pass in construct-time params.
     *
     * @param string $id the name of the thing you want to get
     * @param array $params optional constructor params
     * @return mixed the thing you wanted
     * @throws Exception\NotFoundException if we don't have your thing
     * @throws Exception\ContainerException if anything else went wrong
     */
    public function make(string $id, array $params = [])
    {
        if (empty($params) && !empty($this->instances[$id])) {
            // we've already created a shared instance so return it to save the closure call.
            return $this->instances[$id];
        }

        if (!$this->has($id)) {
            throw new Exception\NotFoundException(
                "Could not instantiate $id: class/rule not found"
            );
        }

        try {
            return $this->make($id);
        } catch (\Exception $e) {
            // we just want the exception to be our brand
            throw new Exception\ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Call any callable utilizing the container's autowiring.
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
}
