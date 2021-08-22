<?php
use asmbr\wallet\models\Currency;
use yii\helpers\ArrayHelper;
/**
 * @var array $fixtures
 * @var Currency[] $currencies
*/

$currencies = ArrayHelper::index(Currency::find()->andWhere(['enabled' => 1])->all(), 'code');
$time = time();

$fixtures = [
    'wallet.allNumberAccountsInWallet' => [
        'wallet_id' => 104,
        'currency_id' => null,
        'type_transaction_id' => null,
        'attribute' => 'Wallet::allNumberAccountsInWallet',
        'value' => 10,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'wallet.allActiveAccountInWallet' => [
        'wallet_id' => 104,
        'currency_id' => null,
        'type_transaction_id' => null,
        'attribute' => 'Wallet::allActiveAccountInWallet',
        'value' => 6,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'wallet.numberActiveAccount' => [
        'wallet_id' => 104,
        'currency_id' => $currencies['USD']->id,
        'type_transaction_id' => null,
        'attribute' => 'AccountExchange::numberActiveAccount',
        'value' => 3,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'wallet.countSenderTransactionPerDay' => [
        'wallet_id' => 104,
        'currency_id' => $currencies['USD']->id,
        'type_transaction_id' => 100,
        'attribute' => 'Transaction::countSenderTransactionPerDay',
        'value' => 3,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'wallet.minimumBalanceTransaction' => [
        'wallet_id' => 104,
        'currency_id' => $currencies['USD']->id,
        'type_transaction_id' => 100,
        'attribute' => 'Transaction::minimumBalanceTransaction',
        'value' => 100,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'wallet.maximumBalanceTransaction' => [
        'wallet_id' => 104,
        'currency_id' => $currencies['USD']->id,
        'type_transaction_id' => 100,
        'attribute' => 'Transaction::maximumBalanceTransaction',
        'value' => 450,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'wallet.minimumBalanceAccount' => [
        'wallet_id' => 104,
        'currency_id' => $currencies['USD']->id,
        'attribute' => 'AccountExchange::minimumBalanceAccount',
        'value' => 100,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'wallet.maximumBalanceAccount' => [
        'wallet_id' => 104,
        'currency_id' => $currencies['USD']->id,
        'attribute' => 'AccountExchange::maximumBalanceAccount',
        'value' => 5600,
        'created_at' => $time,
        'updated_at' => $time,
    ],

    'group.allNumberAccountsInWallet' => [
        'wallet_group_id' => 103,
        'currency_id' => null,
        'type_transaction_id' => null,
        'attribute' => 'Wallet::allNumberAccountsInWallet',
        'value' => 5,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'group.allActiveAccountInWallet' => [
        'wallet_group_id' => 103,
        'currency_id' => null,
        'type_transaction_id' => null,
        'attribute' => 'Wallet::allActiveAccountInWallet',
        'value' => 3,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'group.numberActiveAccount' => [
        'wallet_group_id' => 103,
        'currency_id' => $currencies['USD']->id,
        'type_transaction_id' => null,
        'attribute' => 'AccountExchange::numberActiveAccount',
        'value' => 3,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'group.countSenderTransactionPerDay' => [
        'wallet_group_id' => 103,
        'currency_id' => $currencies['USD']->id,
        'type_transaction_id' => 100,
        'attribute' => 'Transaction::countSenderTransactionPerDay',
        'value' => 3,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'group.minimumBalanceTransaction' => [
        'wallet_group_id' => 103,
        'currency_id' => $currencies['USD']->id,
        'type_transaction_id' => 100,
        'attribute' => 'Transaction::minimumBalanceTransaction',
        'value' => 100,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'group.maximumBalanceTransaction' => [
        'wallet_group_id' => 103,
        'currency_id' => $currencies['USD']->id,
        'type_transaction_id' => 100,
        'attribute' => 'Transaction::maximumBalanceTransaction',
        'value' => 450,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'group.minimumBalanceAccount' => [
        'wallet_group_id' => 103,
        'currency_id' => $currencies['USD']->id,
        'attribute' => 'AccountExchange::minimumBalanceAccount',
        'value' => 100,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'group.maximumBalanceAccount' => [
        'wallet_group_id' => 103,
        'currency_id' => $currencies['USD']->id,
        'attribute' => 'AccountExchange::maximumBalanceAccount',
        'value' => 30600,
        'created_at' => $time,
        'updated_at' => $time,
    ]
];

return $fixtures;