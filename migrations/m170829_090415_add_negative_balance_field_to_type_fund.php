<?php

use yii\db\Migration;
use asmbr\wallet\models\TypeFund;

class m170829_090415_add_negative_balance_field_to_type_fund extends Migration
{
    public function safeUp()
    {
        $this->addColumn(TypeFund::tableName(), 'negative_balance', $this->boolean()->defaultValue(0) . ' AFTER description');

        /** @var TypeFund[] $fundTypes */
        $fundTypes = TypeFund::find()->all();
        array_walk($fundTypes, function(&$value) { $value->updateSignatureOfData(); });
    }

    public function safeDown()
    {
        $this->dropColumn(TypeFund::tableName(), 'negative_balance');

        /** @var TypeFund[] $fundTypes */
        $fundTypes = TypeFund::find()->all();
        array_walk($fundTypes, function(&$value) { $value->updateSignatureOfData(); });

        return true;
    }
}
