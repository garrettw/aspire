<?php

declare(strict_types=1);

namespace Outboard\Di;

use Outboard\Di\Exception\NotFoundException;

class ExplicitContainer implements ComposableContainer, \ArrayAccess
{
    use ParentContainerAware;
    use ContainerCommon;

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
     */
    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }
}
