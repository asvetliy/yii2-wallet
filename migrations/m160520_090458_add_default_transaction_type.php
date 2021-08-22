<?php

use asmbr\wallet\migrations\Migration;
use asmbr\wallet\models\TypeTransaction;

class m160520_090458_add_default_transaction_type extends Migration
{
    public function up()
    {
        $this->insert(TypeTransaction::tableName(), [
            'code' => TypeTransaction::DEFAULT_CODE,
            'name' => 'Transfer',
            'enabled' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    public function down()
    {
        //$this->delete(TypeTransaction::tableName(), ['code' => $this->defaultTypeCode]);
        return true;
    }
}
