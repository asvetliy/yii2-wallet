<?php

use yii\db\Migration;
use asmbr\wallet\models\Operation;

class m170616_122003_add_new_field_balance_into_operation extends Migration
{
    public function up()
    {
        $this->addColumn(Operation::tableName(), 'balance', $this->bigInteger(20)->notNull()->defaultValue(0));
    }

    public function down()
    {
        $this->dropColumn(Operation::tableName(), 'balance');
        return true;
    }
}
