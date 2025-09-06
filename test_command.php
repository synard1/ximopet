<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->handle(
    $input = new Symfony\Component\Console\Input\ArgvInput(['artisan', 'ai:chat-debug', 'Hello, how can you help me?']),
    new Symfony\Component\Console\Output\ConsoleOutput()
);

$kernel->terminate($input, $status);

exit($status);