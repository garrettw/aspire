<?php

declare(strict_types=1);

namespace Aspire\Di;

use Aspire\Di\Exception\ContainerException;
use Aspire\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class CompositeContainer implements ContainerInterface, \ArrayAccess
{
    /**
     * @param ContainerInterface[] $containers An array of containers to be used sequentially for resolving dependencies.
     */
    public function __construct(protected array $containers = [])
    {
        if (empty($this->containers)) {
            throw new \InvalidArgumentException('At least one container must be provided to the composite container.');
        }

        foreach ($this->containers as $container) {
            if ($container instanceof ComposableContainer) {
                $container->setParent($this);
            }
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function has(string $id): bool
    {
        return \array_any($this->containers, fn($container) => $container->has($id));
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
        $foundInContainer = \array_find($this->containers, fn($container) => $container->has($id));
        if ($foundInContainer !== null) {
            return $foundInContainer->get($id);
        }
        throw new NotFoundException("No entry was found for '$id'.");
    }

    /**
     * @inheritDoc
     * @throws ContainerExceptionInterface
     */
    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        if (!\is_string($offset)) {
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
        if (!\is_string($offset)) {
            throw new NotFoundException('Container keys must be strings.');
        }
        return $this->get($offset);
    }

    /**
     * Do not use.
     * @throws ContainerException
     */
    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new ContainerException('Cannot set an instance on a composite container.');
    }

    /**
     * Do not use.
     * @throws ContainerException
     */
    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        throw new ContainerException('Cannot unset an instance from a composite container.');
    }
}
