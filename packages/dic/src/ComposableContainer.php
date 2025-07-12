<?php

declare(strict_types=1);

namespace Outboard\Di;

use Psr\Container\ContainerInterface;

interface ComposableContainer extends ContainerInterface
{
    public function setParent(ContainerInterface $container): void;
}
