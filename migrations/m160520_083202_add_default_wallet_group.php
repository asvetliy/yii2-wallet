<?php

use asmbr\wallet\migrations\Migration;
use asmbr\wallet\models\WalletGroup;

class m160520_083202_add_default_wallet_group extends Migration
{
    public function up()
    {
        $columns = [
            'code' => WalletGroup::DEFAULT_CODE,
            'name' => 'Default Group',
            'created_at' => time(),
            'updated_at' => time()
        ];
        $this->insert(WalletGroup::tableName(), $columns);
        $this->update(WalletGroup::tableName(), ['inspected' => md5($this->db->lastInsertID . implode('', $columns) . $this->module->modelInspectSalt)], ['id' => $this->db->lastInsertID]);
    }

    public function down()
    {
        //$this->delete(WalletGroup::tableName(), ['code' => $this->defaultGroupCode]);
        return true;
    }
}
