<?php

declare(strict_types=1);

require_once '../vendor/autoload.php';

// Technically, any PSR-11-compatible DI container can be used,
// but implementing a different one is on you.
new Aspire\Di\Container(new Aspire\Di\Config([
    new Aspire\Framework\ConfigProvider(),
    new App\ConfigProvider(),
]))->get(Aspire\Framework\Application::class)();
