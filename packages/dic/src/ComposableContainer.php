<?php

declare(strict_types=1);

namespace Aspire\Di;

use Psr\Container\ContainerInterface;

interface ComposableContainer
{
    public function setParent(ContainerInterface $container): void;
}
