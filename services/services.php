<?php

use Nails\DataMigration\Service;

return [
    'services' => [
        'DataMigration' => function (): Service\DataMigration {
            if (class_exists('\App\DataMigration\Service\DataMigration')) {
                return new \App\DataMigration\Service\DataMigration();
            } else {
                return new Service\DataMigration();
            }
        },
    ],
];
