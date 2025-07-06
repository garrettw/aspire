<?php

declare(strict_types=1);

namespace Aspire\Di;

use Aspire\Di\Exception\ContainerException;
use Aspire\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ExplicitContainer implements \Psr\Container\ContainerInterface, \ArrayAccess, ComposableContainer
{
    use ParentContainerAware;

    /**
     * @var array<string, mixed> $instances
     * An associative array to hold the instances by their string id.
     */
    protected array $instances = [];

    /**
     * @inheritDoc
     */
    #[\Override]
    public function has(string $id): bool
    {
        return isset($this->instances[$id]);
    }

    /**
     * @inheritDoc
     * @template T
     * @param string|class-string<T> $id Identifier of the entry to look for.
     * @return T|mixed|null
     */
    #[\Override]
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException("No entry was found for '$id'.");
        }
        return $this->instances[$id];
    }

    public function set(string $id, $instance)
    {
        $this->instances[$id] = $instance;
    }

    /**
     * @inheritDoc
     * @throws ContainerExceptionInterface
     */
    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset)) {
            throw new ContainerException('Container keys must be strings.');
        }
        return $this->has($offset);
    }

    /**
     * @inheritDoc
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        if (!is_string($offset)) {
            throw new NotFoundException('Container keys must be strings.');
        }
        return $this->get($offset);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Do not use.
     * @throws ContainerException
     */
    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        throw new ContainerException('Cannot unset an instance from the container.');
    }
}
