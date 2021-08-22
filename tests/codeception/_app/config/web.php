<?php

$config = [
    'id' => 'yii2-wallet-test',
    'name' => 'yii2 Wallet Module (Testing)',
    'basePath'  => __DIR__ . '/..',
    'aliases' => [
        '@vendor' => VENDOR_DIR,
        '@bower'  => '@vendor/bower',
        '@tests'  => __DIR__ . '/../../..'
    ],
    'language' => 'en_US',
    'sourceLanguage' => 'en_US',
    'bootstrap' => ["asmbr\\wallet\\Bootstrap", "asmbr\\wallet\\tests\\codeception\\_app\\components\\Bootstrap"],
    'controllerNamespace' => "asmbr\\wallet\\tests\\codeception\\_app\\controllers",
    'modules' => [
        'wallet' => [
            'class' => "asmbr\\wallet\\Module"
        ],
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=test_db',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ],
        'request' => [
            'enableCsrfValidation'   => false,
            'enableCookieValidation' => false,
        ],
        'urlManager' => [
            'enablePrettyUrl' => false,
            'showScriptName' => true,
            'rules' => [
            ],
        ],
        'user' => [
            'identityClass' => "asmbr\\wallet\\tests\\codeception\\_app\\models\\User",
        ],
        'i18n'=>[
            'translations' => [
                'wlt*'=>['class' => 'yii\i18n\PhpMessageSource'],
            ]
        ],
        'log'   => null,
        'cache' => null,
    ],
];

return \yii\helpers\ArrayHelper::merge($config, require 'local.php');