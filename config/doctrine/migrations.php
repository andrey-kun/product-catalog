<?php

declare(strict_types=1);

use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;

return new ConfigurationArray([
    'migrations_paths' => [
        'migrations' => __DIR__ . '/../../migrations'
    ]
]);
