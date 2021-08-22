<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/8/16
 * Time: 3:57 PM
 */

namespace asmbr\wallet\tests\codeception\_fixtures;

use yii\test\ActiveFixture;

class TypeTransactionFixture extends ActiveFixture
{
    public $modelClass = 'asmbr\wallet\models\TypeTransaction';
    public $dataFile = '@tests/codeception/_fixtures/data/type_transaction.php';
}