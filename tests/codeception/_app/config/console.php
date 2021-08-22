<?php

$web = require 'web.php';

return [
    'id' => 'yii2-wallet-test-console',
    'basePath' => dirname(__DIR__),
    'aliases' => ['@vendor' => VENDOR_DIR],
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => '@vendor/asmbr/yii2-wallet/migrations',
        ],
    ],
    'components' => [
        'log'   => null,
        'cache' => null,
        'db'    => $web['components']['db'],
    ],
    'modules' => [
        'wallet' => "asmbr\\wallet\\Module",
    ],
];