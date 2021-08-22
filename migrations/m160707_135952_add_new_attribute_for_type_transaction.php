<?php

use yii\db\Migration;
use asmbr\wallet\models\TypeTransaction;

class m160707_135952_add_new_attribute_for_type_transaction extends Migration
{
    public function up()
    {
        $this->addColumn(TypeTransaction::tableName(), 'can_withdraw', $this->boolean()->defaultValue(0));
    }

    public function down()
    {
        $this->dropColumn(TypeTransaction::tableName(), 'can_withdraw');
        return true;
    }
}
