<?php

namespace asmbr\wallet\tests\codeception\_fixtures;

use yii\test\ActiveFixture;

class ConfigFixture extends ActiveFixture
{
    public $modelClass = 'asmbr\wallet\models\Config';
    public $dataFile = '@tests/codeception/_fixtures/data/config.php';
    public $depends = [
        'asmbr\wallet\tests\codeception\_fixtures\WalletGroupFixture',
        'asmbr\wallet\tests\codeception\_fixtures\WalletFixture',
        'asmbr\wallet\tests\codeception\_fixtures\CurrencyFixture',
        'asmbr\wallet\tests\codeception\_fixtures\TypeTransactionFixture'
    ];
}