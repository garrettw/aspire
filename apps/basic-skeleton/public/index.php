<?php

declare(strict_types=1);

require_once '../vendor/autoload.php';

// Technically, any PSR-11-compatible DI container can be used,
// but implementing a different one is on you.
new Outboard\Di\Container(new Outboard\Di\Config([
    new Outboard\Framework\ConfigProvider(),
    new App\ConfigProvider(),
]))->get(Outboard\Framework\Application::class)();
