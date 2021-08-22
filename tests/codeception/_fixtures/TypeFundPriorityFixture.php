<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 7/12/16
 * Time: 12:29 PM
 */

namespace asmbr\wallet\tests\codeception\_fixtures;

use yii\test\ActiveFixture;

class TypeFundPriorityFixture extends ActiveFixture
{
    public $modelClass = 'asmbr\wallet\models\TypeFundPriority';
    public $dataFile = '@tests/codeception/_fixtures/data/type_fund_priority.php';
    public $depends = [
        'asmbr\wallet\tests\codeception\_fixtures\TypeFundFixture',
        'asmbr\wallet\tests\codeception\_fixtures\TypeTransactionFixture'
    ];
}