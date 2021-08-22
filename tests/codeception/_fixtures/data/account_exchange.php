<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/9/16
 * Time: 12:51 PM
 */

use asmbr\wallet\models\Currency;
use asmbr\wallet\models\Wallet;
use yii\helpers\ArrayHelper;

$time = time();
/** @var \asmbr\wallet\Module $module */
$module = Yii::$app->getModule('wallet');
$inspectedSalt = $module->modelInspectSalt;

/** @var Currency[] $currencies */
$currencies = ArrayHelper::index(Currency::find()->andWhere(['enabled' => 1])->all(), 'code');
/** @var Wallet[] $wallets */
$wallets = Wallet::find()->all();

$fixtures = [
    'W0USD' => [
        'id' => 99,
        'wallet_id' => 99,
        'currency_id' => $currencies['USD']->id,
    ],
    'W1EUR' => [
        'id' => 100,
        'wallet_id' => 100,
        'currency_id' => $currencies['EUR']->id,
    ],
    'W1USD' => [
        'id' => 101,
        'wallet_id' => 100,
        'currency_id' => $currencies['USD']->id,
    ],
    'W1USD-invalid' => [
        'id' => 102,
        'wallet_id' => 100,
        'currency_id' => $currencies['USD']->id,
    ],
    'W2EUR' => [
        'id' => 103,
        'wallet_id' => 101,
        'currency_id' => $currencies['EUR']->id,
    ],
    'W2USD' => [
        'id' => 104,
        'wallet_id' => 101,
        'currency_id' => $currencies['USD']->id,
    ],
    'WL1USD' => [
        'id' => 105,
        'wallet_id' => 103,
        'currency_id' => $currencies['USD']->id,
    ],
    'WL2USD' => [
        'id' => 106,
        'wallet_id' => 103,
        'currency_id' => $currencies['USD']->id,
    ],
    'WLX1USD' => [
        'id' => 107,
        'wallet_id' => 104,
        'currency_id' => $currencies['USD']->id,
    ],
    'WLX2USD' => [
        'id' => 108,
        'wallet_id' => 104,
        'currency_id' => $currencies['USD']->id,
    ]
];

foreach ($fixtures as $key => $fixture) {
    $fixtures[$key]['code'] = $time;
    $fixtures[$key]['created_at'] = $time;
    $time = $time + 1;
}

foreach ($fixtures as $key => $fixture) {
    $array = explode('-', $key);
    if(!isset($array[1]) || $array[1] !== 'invalid')
        $fixtures[$key]['inspected'] = md5(implode('', $fixture) . $inspectedSalt);

}

return $fixtures;
