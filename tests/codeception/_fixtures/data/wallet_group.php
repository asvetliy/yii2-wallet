<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/7/16
 * Time: 3:34 PM
 */

use asmbr\wallet\models\WalletGroup;

$time = time();
/** @var \asmbr\wallet\Module $module */
$module = Yii::$app->getModule('wallet');
$inspectedSalt = $module->modelInspectSalt;

$fixtures = [
    'default' => [
        'id' => 100,
        'code' => WalletGroup::DEFAULT_CODE,
        'name' => 'Default Group',
        'description' => '',
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'valid' => [
        'id' => 101,
        'code' => 'WGRP_VALID',
        'name' => 'Valid Group',
        'description' => '',
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'invalid' => [
        'id' => 102,
        'code' => 'WGRP_INVALID',
        'name' => 'Invalid Group',
        'description' => '',
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'config' => [
        'id' => 103,
        'code' => 'CNF',
        'name' => 'Group for Configure',
        'description' => '',
        'created_at' => $time,
        'updated_at' => $time,
    ]
];

foreach ($fixtures as $key => $fixture) {
    $fixtures[$key]['inspected'] = md5(implode('', $fixture) . $inspectedSalt);
}

$fixtures['invalid']['inspected'] = null;

return $fixtures;