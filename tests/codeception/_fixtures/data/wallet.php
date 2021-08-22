<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/8/16
 * Time: 2:14 PM
 */

$time = time();
/** @var \asmbr\wallet\Module $module */
$module = Yii::$app->getModule('wallet');
$inspectedSalt = $module->modelInspectSalt;

$fixtures = [
    'system' => [
        'id' => 99,
        'wallet_group_id' => 100,
        'entity_id' => 0,
        'created_at' => $time,
    ],
    'default' => [
        'id' => 100,
        'wallet_group_id' => 100,
        'entity_id' => 1,
        'created_at' => $time,
    ],
    'custom' => [
        'id' => 101,
        'wallet_group_id' => 100,
        'entity_id' => 2,
        'created_at' => $time,
    ],
    'config.group' => [
        'id' => 103,
        'wallet_group_id' => 103,
        'entity_id' => 5,
        'created_at' => $time,
    ],
    'extension' => [
        'id' => 104,
        'wallet_group_id' => 100,
        'entity_id' => 6,
        'created_at' => $time,
    ]
];

foreach ($fixtures as $key => $fixture) {
    $fixtures[$key]['inspected'] = md5(implode('', $fixture) . $inspectedSalt);
}

return $fixtures;