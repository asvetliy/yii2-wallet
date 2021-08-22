<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/8/16
 * Time: 2:14 PM
 */

use asmbr\wallet\models\TypeFund;

$time = time();
/** @var \asmbr\wallet\Module $module */
$module = Yii::$app->getModule('wallet');
$inspectedSalt = $module->modelInspectSalt;

$fixtures = [
    'disabled' => [
        'id' => 99,
        'code' => 'FT_DISABLED',
        'name' => 'Disabled',
        'description' => '',
        'created_at' => $time,
        'updated_at' => $time
    ],
    'basic' => [
        'id' => 100,
        'code' => TypeFund::DEFAULT_CODE,
        'name' => 'Basic',
        'description' => '',
        'created_at' => $time,
        'updated_at' => $time
    ],
    'promo' => [
        'id' => 101,
        'code' => 'FT_PROMO',
        'name' => 'Promotional',
        'description' => '',
        'created_at' => $time,
        'updated_at' => $time
    ]
];

foreach ($fixtures as $key => $fixture) {
    $fixtures[$key]['inspected'] = md5(implode('', $fixture) . $inspectedSalt);
}

return $fixtures;