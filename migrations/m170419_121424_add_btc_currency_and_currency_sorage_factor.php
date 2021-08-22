<?php

use asmbr\wallet\migrations\Migration;
use asmbr\wallet\models\Currency;
use asmbr\wallet\models\Clarity;
use asmbr\wallet\models\Operation;
use asmbr\wallet\models\AccountFund;
use yii\db\Expression;

class m170419_121424_add_btc_currency_and_currency_sorage_factor extends Migration
{
    public function up()
    {
        $this->update(Clarity::tableName(), [
            'sum' => new Expression('`sum` * 100'),
            'inspected' => new Expression("MD5(CONCAT(`id`, `opr_id`, `fund_type_id`, TRIM(TRAILING '.' from TRIM(TRAILING 0 from `sum` / 100)), '{$this->module->modelInspectSalt}'))")
        ]);
        $this->alterColumn(Clarity::tableName(), 'sum', $this->bigInteger()->notNull()->defaultValue(0));

        $this->update(Operation::tableName(), [
            'sum' => new Expression('`sum` * 100'),
            'inspected' => new Expression("MD5(CONCAT(`id`, `trn_id`, COALESCE(`account_id`, ''), TRIM(TRAILING '.' from TRIM(TRAILING 0 from `sum` / 100)), COALESCE(`delayed_at`, ''), COALESCE(`enrolled_at`, ''), '{$this->module->modelInspectSalt}'))")
        ]);
        $this->alterColumn(Operation::tableName(), 'sum', $this->bigInteger()->notNull()->defaultValue(0));

        $this->update(AccountFund::tableName(), [
            'balance' => new Expression('`balance` * 100'),
            'inspected' => new Expression("MD5(CONCAT(`id`, `account_id`, `type_fund_id`, TRIM(TRAILING '.' from TRIM(TRAILING 0 from `balance` / 100)), `updated_at`, `created_at`, '{$this->module->modelInspectSalt}'))")
        ]);
        $this->alterColumn(AccountFund::tableName(), 'balance', $this->bigInteger()->notNull()->defaultValue(0));

        $this->addColumn(Currency::tableName(),'storage_factor', $this->integer()->notNull()->defaultValue(2));

        $this->insert(Currency::tableName(), [
            'code' => 'BTC',
            'name' => 'Bitcoin',
            'enabled' => 0,
            'storage_factor' => 8,
            'created_at' => time(),
            'updated_at' => time()
        ]);

        $this->update(Currency::tableName(), ['inspected' => new Expression("MD5(CONCAT(`id`, `code`, `name`, `enabled`, `created_at`, `updated_at`, `storage_factor`, '{$this->module->modelInspectSalt}'))")]);
    }

    public function down()
    {
        $this->alterColumn(AccountFund::tableName(), 'balance', $this->decimal($this->balance_length, $this->balance_after_point_length)->notNull());
        $this->update(AccountFund::tableName(), [
            'balance' => new Expression('`balance` / 100'),
            'inspected' => new Expression("MD5(CONCAT(`id`, `account_id`, `type_fund_id`, TRIM(TRAILING '.' from TRIM(TRAILING 0 from `balance`)), `updated_at`, `created_at`, '{$this->module->modelInspectSalt}'))")
        ]);

        $this->alterColumn(Operation::tableName(), 'sum', $this->decimal($this->balance_length, $this->balance_after_point_length)->notNull());
        $this->update(Operation::tableName(), [
            'sum' => new Expression('`sum` / 100'),
            'inspected' => new Expression("MD5(CONCAT(`id`, `trn_id`, COALESCE(`account_id`, ''), TRIM(TRAILING '.' from TRIM(TRAILING 0 from `sum`)), COALESCE(`delayed_at`, ''), COALESCE(`enrolled_at`, ''), '{$this->module->modelInspectSalt}'))")
        ]);

        $this->alterColumn(Clarity::tableName(), 'sum', $this->decimal($this->balance_length, $this->balance_after_point_length)->notNull());
        $this->update(Clarity::tableName(), [
            'sum' => new Expression('`sum` / 100'),
            'inspected' => new Expression("MD5(CONCAT(`id`, `opr_id`, `fund_type_id`, TRIM(TRAILING '.' from TRIM(TRAILING 0 from `sum`)), '{$this->module->modelInspectSalt}'))")
        ]);

        $this->delete(Currency::tableName(), ['code' => 'BTC']);
        $this->dropColumn(Currency::tableName(),'storage_factor');

        $this->update(Currency::tableName(), ['inspected' => new Expression("MD5(CONCAT(`id`, `code`, `name`, `enabled`, `created_at`, `updated_at`, '{$this->module->modelInspectSalt}'))")]);
        return true;
    }
}
