<?php

use asmbr\wallet\migrations\Migration;
use asmbr\wallet\models\TypeFund;
use asmbr\wallet\models\TypeFundPriority;
use asmbr\wallet\models\TypeTransaction;

class m160712_101106_add_fund_type_priority_table extends Migration
{
    public function up()
    {
        $this->createTable(TypeFundPriority::tableName(), [
            'id' => $this->primaryKey(),
            'trn_type_id' => $this->integer()->defaultValue(null),
            'fund_type_id' => $this->integer()->notNull(),
            'value' => $this->integer()->notNull()
        ], $this->tableOptions);

        $types = TypeFund::find()->select([TypeFund::tableName() . '.id', TypeFund::tableName() . '.priority'])->asArray()->all();
        foreach ($types as $type) {
            $this->insert(TypeFundPriority::tableName(), [
                'fund_type_id' => $type['id'],
                'value' => $type['priority']
            ]);
        }

        $this->dropColumn(TypeFund::tableName(), 'priority');

        $this->addForeignKey('FK_type_fund_priority_has_trn_type', TypeFundPriority::tableName(), 'trn_type_id', TypeTransaction::tableName(), 'id');
        $this->addForeignKey('FK_type_fund_priority_has_type_fund', TypeFundPriority::tableName(), 'fund_type_id', TypeFund::tableName(), 'id');

        /** @var TypeFund[] $fundTypes */
        $fundTypes = TypeFund::find()->all();
        array_walk($fundTypes, function(&$value) { $value->updateSignatureOfData(); });
    }

    public function down()
    {
        $this->dropTable(TypeFundPriority::tableName());
        $this->addColumn(TypeFund::tableName(), 'priority', $this->integer()->defaultValue(0)->notNull() . ' AFTER description');

        return true;
    }
}
