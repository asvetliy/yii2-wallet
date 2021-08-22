<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/9/16
 * Time: 12:51 PM
 */

namespace asmbr\wallet\tests\codeception\_fixtures;

use yii\test\ActiveFixture;

class AccountExchangeFixture extends ActiveFixture
{
    public $modelClass = 'asmbr\wallet\models\AccountExchange';
    public $dataFile = '@tests/codeception/_fixtures/data/account_exchange.php';
    public $depends = ['asmbr\wallet\tests\codeception\_fixtures\WalletFixture'];
}