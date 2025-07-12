<?php

namespace Outboard\Di;

use Outboard\Di\Exception\ContainerException;
use Outboard\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait ContainerCommon
{
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
     * Do not use.
     * @throws ContainerException
     */
    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        throw new ContainerException('Cannot unset an instance from the container.');
    }
}
