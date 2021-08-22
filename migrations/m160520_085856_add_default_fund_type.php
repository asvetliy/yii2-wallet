<?php

use asmbr\wallet\migrations\Migration;
use asmbr\wallet\models\TypeFund;

class m160520_085856_add_default_fund_type extends Migration
{
    public function up()
    {
        $this->insert(TypeFund::tableName(), [
            'code' => TypeFund::DEFAULT_CODE,
            'name' => 'Basic',
            'priority' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    public function down()
    {
        $this->delete(TypeFund::tableName(), ['code' => TypeFund::DEFAULT_CODE]);
        return true;
    }
}
