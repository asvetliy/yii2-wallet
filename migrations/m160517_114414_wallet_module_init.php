<?php

use asmbr\wallet\migrations\Migration;
use asmbr\wallet\models\Wallet;
use asmbr\wallet\models\WalletGroup;
use asmbr\wallet\models\Currency;
use asmbr\wallet\models\TypeFund;
use asmbr\wallet\models\TypeTransaction;
use asmbr\wallet\models\AccountExchange;
use asmbr\wallet\models\AccountFund;
use asmbr\wallet\models\Transaction;
use asmbr\wallet\models\Operation;
use asmbr\wallet\models\Clarity;
use asmbr\wallet\models\Config;
use asmbr\wallet\models\DependentTransaction;

class m160517_114414_wallet_module_init extends Migration
{
    public function up()
    {
        $this->createTable(WalletGroup::tableName(), [
            'id' => $this->primaryKey(),
            'code' => $this->string($this->small_str_length)->unique()->notNull(),
            'name' => $this->string($this->medium_str_length),
            'description' => $this->string($this->large_str_length),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'inspected' => $this->string($this->md5_length)
        ], $this->tableOptions);

        $this->createTable(Currency::tableName(), [
            'id' => $this->primaryKey(),
            'code' => $this->string(3)->unique()->notNull(),
            'name' => $this->string($this->medium_str_length),
            'enabled' => $this->boolean()->defaultValue(0)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'inspected' => $this->string($this->md5_length)
        ], $this->tableOptions);

        $this->createTable(TypeFund::tableName(), [
            'id' => $this->primaryKey(),
            'code' => $this->string($this->small_str_length)->unique()->notNull(),
            'name' => $this->string($this->medium_str_length),
            'description' => $this->string($this->large_str_length),
            'priority' => $this->integer()->defaultValue(0)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'inspected' => $this->string($this->md5_length)
        ], $this->tableOptions);

        $this->createTable(TypeTransaction::tableName(), [
            'id' => $this->primaryKey(),
            'parent_id' => $this->integer(),
            'code' => $this->string($this->small_str_length)->unique()->notNull(),
            'name' => $this->string($this->medium_str_length),
            'description' => $this->string($this->large_str_length),
            'required_sender' => $this->boolean()->defaultValue(1)->notNull(),
            'enabled' => $this->boolean()->defaultValue(0)->notNull(),
            'depth' => $this->integer()->defaultValue(0)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $this->tableOptions);

        $this->createTable(Wallet::tableName(), [
            'id' => $this->primaryKey(),
            'wallet_group_id' => $this->integer()->notNull(),
            'entity_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'inspected' => $this->string($this->md5_length)
        ], $this->tableOptions);

        $this->createTable(AccountExchange::tableName(), [
            'id' => $this->primaryKey(),
            'wallet_id' => $this->integer()->notNull(),
            'currency_id' => $this->integer()->notNull(),
            'code' => $this->string($this->small_str_length)->unique()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'deleted_at' => $this->integer()->defaultValue(null),
            'inspected' => $this->string($this->md5_length),
        ], $this->tableOptions);

        $this->createTable(AccountFund::tableName(), [
            'id' => $this->primaryKey(),
            'account_id' => $this->integer()->notNull(),
            'type_fund_id' => $this->integer()->notNull(),
            'balance' => $this->decimal($this->balance_length, $this->balance_after_point_length)->defaultValue(0)->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'inspected' => $this->string($this->md5_length),
        ], $this->tableOptions);

        $this->createTable(Transaction::tableName(), [
            'id' => $this->primaryKey(),
            'pid' => $this->integer()->defaultValue(null),
            'code' => $this->string($this->small_str_length)->unique()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'sender_id' => $this->integer(),
            'recipient_id' => $this->integer()->notNull(),
            'type_id' => $this->integer()->notNull(),
            'description' => $this->string($this->medium_str_length),
            'created_at' => $this->integer()->notNull(),
            'inspected' => $this->string($this->md5_length),
        ], $this->tableOptions);

        $this->createTable(Operation::tableName(), [
            'id' => $this->primaryKey(),
            'trn_id' => $this->integer()->notNull(),
            'account_id' => $this->integer(),
            'sum' => $this->decimal($this->balance_length, $this->balance_after_point_length)->notNull(),
            'delayed_at' => $this->integer(),
            'enrolled_at' => $this->integer(),
            'inspected' => $this->string($this->md5_length),
        ], $this->tableOptions);
        
        $this->createTable(Clarity::tableName(), [
            'id' => $this->primaryKey(),
            'opr_id' => $this->integer()->notNull(),
            'fund_type_id' => $this->integer()->notNull(),
            'sum' => $this->decimal($this->balance_length, $this->balance_after_point_length)->notNull(),
            'inspected' => $this->string($this->md5_length),
        ], $this->tableOptions);

        $this->createTable(DependentTransaction::tableName(), [
            'id' => $this->primaryKey(),
            'wallet_group_id' => $this->integer()->notNull(),
            'currency_id' => $this->integer()->notNull(),
            'owner_type_trn_id' => $this->integer(),
            'sender_id' => $this->integer()->notNull(),
            'recipient_id' => $this->integer()->notNull(),
            'type_trn_id' => $this->integer()->notNull(),
            'opr_type' => $this->boolean()->notNull(),
            'percent' => $this->float()->defaultValue(0)->notNull(),
            'static' => $this->decimal($this->balance_length, $this->balance_after_point_length)->defaultValue(0)->notNull(),
            'trn_desc' => $this->string($this->large_str_length),
            'desc' => $this->string($this->large_str_length),
            'enabled' => $this->boolean()->defaultValue(0)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull()
        ], $this->tableOptions);

        $this->createTable(Config::tableName(), [
            'id' => $this->primaryKey(),
            'wallet_id' => $this->integer(),
            'wallet_group_id' => $this->integer(),
            'currency_id' => $this->integer(),
            'type_transaction_id' => $this->integer(),
            'attribute' => $this->string($this->small_str_length)->notNull(),
            'value' => $this->string($this->small_str_length)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull()
        ], $this->tableOptions);

        $this->createFK();
    }

    protected function createFK()
    {
        $this->addForeignKey('FK_wallet_has_wallet_group', Wallet::tableName(), 'wallet_group_id', WalletGroup::tableName(), 'id');
        $this->addForeignKey('FK_account_exchange_has_wallet', AccountExchange::tableName(), 'wallet_id', Wallet::tableName(), 'id');
        $this->addForeignKey('FK_account_exchange_has_currency', AccountExchange::tableName(), 'currency_id', Currency::tableName(), 'id');
        $this->addForeignKey('FK_account_fund_has_account_exchange', AccountFund::tableName(), 'account_id', AccountExchange::tableName(), 'id');
        $this->addForeignKey('FK_account_fund_has_type_fund', AccountFund::tableName(), 'type_fund_id', TypeFund::tableName(), 'id');
        $this->addForeignKey('FK_type_transaction_has_type_transaction', TypeTransaction::tableName(), 'parent_id', TypeTransaction::tableName(), 'id');
        $this->addForeignKey('FK_transaction_has_account_exchange_sender', Transaction::tableName(), 'sender_id', AccountExchange::tableName(), 'id');
        $this->addForeignKey('FK_transaction_has_account_exchange_recipient', Transaction::tableName(), 'recipient_id', AccountExchange::tableName(), 'id');
        $this->addForeignKey('FK_transaction_has_type_transaction', Transaction::tableName(), 'type_id', TypeTransaction::tableName(), 'id');
        $this->addForeignKey('FK_operation_has_transaction', Operation::tableName(), 'trn_id', Transaction::tableName(), 'id');
        $this->addForeignKey('FK_operation_has_account_exchange', Operation::tableName(), 'account_id', AccountExchange::tableName(), 'id');
        $this->addForeignKey('FK_clarity_has_operation', Clarity::tableName(), 'opr_id', Operation::tableName(), 'id');
        $this->addForeignKey('FK_clarity_has_type_fund', Clarity::tableName(), 'fund_type_id', TypeFund::tableName(), 'id');
        $this->addForeignKey('FK_dependent_transaction_has_wallet_group', DependentTransaction::tableName(), 'wallet_group_id', WalletGroup::tableName(), 'id');
        $this->addForeignKey('FK_dependent_transaction_has_currency', DependentTransaction::tableName(), 'currency_id', Currency::tableName(), 'id');
        $this->addForeignKey('FK_dependent_transaction_has_type_transaction_owner', DependentTransaction::tableName(), 'owner_type_trn_id', TypeTransaction::tableName(), 'id');
        $this->addForeignKey('FK_dependent_transaction_has_type_transaction', DependentTransaction::tableName(), 'type_trn_id', TypeTransaction::tableName(), 'id');
        $this->addForeignKey('FK_config_has_wallet_group', Config::tableName(), 'wallet_group_id', WalletGroup::tableName(), 'id');
        $this->addForeignKey('FK_config_has_wallet_exception', Config::tableName(), 'wallet_id', Wallet::tableName(), 'id');
        $this->addForeignKey('FK_config_has_currency', Config::tableName(), 'currency_id', Currency::tableName(), 'id');
        $this->addForeignKey('FK_config_has_type_transaction', Config::tableName(), 'type_transaction_id', TypeTransaction::tableName(), 'id');
    }

    public function down()
    {
        $this->dropTable(Config::tableName());
        $this->dropTable(DependentTransaction::tableName());
        $this->dropTable(Clarity::tableName());
        $this->dropTable(Operation::tableName());
        $this->dropTable(Transaction::tableName());
        $this->dropTable(TypeTransaction::tableName());
        $this->dropTable(AccountFund::tableName());
        $this->dropTable(TypeFund::tableName());
        $this->dropTable(AccountExchange::tableName());
        $this->dropTable(Currency::tableName());
        $this->dropTable(Wallet::tableName());
        $this->dropTable(WalletGroup::tableName());
        return true;
    }
}
