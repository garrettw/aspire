<?php /** @noinspection PhpIncompatibleReturnTypeInspection */

declare(strict_types=1);

namespace Aspire\Di;

use Psr\Container\ContainerInterface;

trait ParentContainerAware
{
    protected ?ContainerInterface $parent = null;
    public function setParent(ContainerInterface $container): void
    {
        $this->parent = $container;
    }

    protected function parent(): ContainerInterface
    {
        return $this->parent;
    }

    protected function hasParent(): bool
    {
        return isset($this->parent);
    }

    protected function parentOrSelf(): ContainerInterface
    {
        return $this->hasParent() ? $this->parent : $this;
    }
}
