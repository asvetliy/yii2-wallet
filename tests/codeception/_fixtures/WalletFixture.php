<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/8/16
 * Time: 2:13 PM
 */

namespace asmbr\wallet\tests\codeception\_fixtures;

use yii\test\ActiveFixture;

class WalletFixture extends ActiveFixture
{
    public $modelClass = 'asmbr\wallet\models\Wallet';
    public $dataFile = '@tests/codeception/_fixtures/data/wallet.php';
    public $depends = [
        'asmbr\wallet\tests\codeception\_fixtures\WalletGroupFixture'
    ];
}