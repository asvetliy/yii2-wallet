<?php

/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/7/16
 * Time: 3:31 PM
 */

namespace asmbr\wallet\tests\codeception\_fixtures;

use yii\test\ActiveFixture;

class WalletGroupFixture extends ActiveFixture
{
    public $modelClass = 'asmbr\wallet\models\WalletGroup';
    public $dataFile = '@tests/codeception/_fixtures/data/wallet_group.php';
}