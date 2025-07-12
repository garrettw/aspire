<?php /** @noinspection PhpIncompatibleReturnTypeInspection */

declare(strict_types=1);

namespace Outboard\Di;

use Psr\Container\ContainerInterface;
use Outboard\Di\Exception\ContainerException;

trait ParentContainerAware
{
    public readonly ?ContainerInterface $parent;

    /**
     * @throws ContainerException
     */
    public function setParent(ContainerInterface $container): void
    {
        if (isset($this->parent)) {
            throw new ContainerException('Parent container is already set.');
        }
        $this->parent = $container;
    }


    /**
     * This is intended to be used when the child container needs to fetch a dependency.
     * If this container was configured as a child of another one, it can defer to the
     * parent container's resolution process by calling `$this->parentOrSelf()->get($id)`
     * in place of `$this->get($id)`. The parent may end up calling this child, but it may not.
     */
    protected function parentOrSelf(): ContainerInterface
    {
        return $this->parent ?? $this;
    }
}
