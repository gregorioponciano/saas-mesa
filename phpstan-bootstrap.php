<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

$app = require __DIR__.'/bootstrap/app.php';

/** @var Application $app */
$app->make(Kernel::class)->bootstrap();

return $app;
