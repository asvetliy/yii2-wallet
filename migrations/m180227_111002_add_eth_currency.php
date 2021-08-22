<?php

use yii\db\Migration;
use asmbr\wallet\models\Currency;
use yii\db\Expression;

class m180227_111002_add_eth_currency extends Migration
{
    public function safeUp()
    {
        $currencyTable = Currency::tableName();
        $table = $this->db->getTableSchema($currencyTable);
        if (!isset($table->columns['isCrypto'])) {
            $this->addColumn($currencyTable, 'isCrypto', $this->boolean()->defaultValue(false));
            $this->update($currencyTable, ['isCrypto' => true], ['code' => 'BTC']);
        }

        $exists = Currency::find()->where(['code' => 'ETH'])->exists();
        if (!$exists) {
            /** @var \common\modules\wallet\Module $module */
            $module = Yii::$app->getModule('wallet');
            $time = time();

            $this->insert($currencyTable, [
                'code' => 'ETH',
                'name' => 'Ethereum',
                'enabled' => 0,
                'storage_factor' => 8,
                'created_at' => $time,
                'updated_at' => $time,
                'isCrypto' => true
            ]);

            $this->update($currencyTable, ['inspected' => new Expression("MD5(CONCAT(`id`, `code`, `name`, `enabled`, `created_at`, `updated_at`, `storage_factor`, `isCrypto`, '{$module->modelInspectSalt}'))")]);
        }
    }

    public function safeDown()
    {
        $currencyTable = Currency::tableName();
        $this->dropColumn($currencyTable, 'isCrypto');
        return true;
    }
}
