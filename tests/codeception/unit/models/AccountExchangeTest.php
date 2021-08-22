<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/8/16
 * Time: 12:57 PM
 */

namespace asmbr\wallet\tests\codeception\unit\models;

use asmbr\wallet\tests\codeception\_fixtures\TypeFundPriorityFixture;
use yii;
use asmbr\wallet\tests\codeception\unit\TestCase;

use asmbr\wallet\models\Transaction;
use asmbr\wallet\models\AccountExchange;
use asmbr\wallet\models\Wallet;
use asmbr\wallet\models\Currency;
use asmbr\wallet\tests\codeception\_fixtures\WalletFixture;
use asmbr\wallet\tests\codeception\_fixtures\TypeFundFixture;
use asmbr\wallet\tests\codeception\_fixtures\TypeTransactionFixture;
use asmbr\wallet\tests\codeception\_fixtures\ConfigFixture;
use asmbr\wallet\tests\codeception\_fixtures\CurrencyFixture;

class AccountExchangeTest extends TestCase
{
    public function fixtures()
    {
        return [
            'wallets' => [
                'class' => WalletFixture::className(),
                'dataFile' => '@tests/codeception/_fixtures/data/wallet.php'
            ],
            'currency' => [
                'class' => CurrencyFixture::className()
            ],
            'funds' => [
                'class' => TypeFundFixture::className(),
                'dataFile' => '@tests/codeception/_fixtures/data/type_fund.php'
            ],
            'types' => [
                'class' => TypeTransactionFixture::className(),
                'dataFile' => '@tests/codeception/_fixtures/data/type_transaction.php'
            ],
            'fundsPriority' => [
                'class' => TypeFundPriorityFixture::className(),
                'dataFile' => '@tests/codeception/_fixtures/data/type_fund_priority.php'
            ],
            'configs' => [
                'class' => ConfigFixture::className(),
                'dataFile' => '@tests/codeception/_fixtures/data/config.php'
            ]
        ];
    }

    public function testAccountExchange()
    {
        $this->specify('account should be saved', function () {
            /** @var Wallet $wallet */
            $wallet = $this->getFixture('wallets')->getModel('default');
            /** @var Currency $currency */
            $currency = Currency::find()->one();

            $account = new AccountExchange;
            $account->wallet_id = $wallet->id;
            $account->currency_id = $currency->id;
            expect($account->save())->true();
            expect($account->dataIsValid)->true();
        });

        $this->specify('account should not be saved, when currency is disabled', function() {});

        $this->specify('account should not be saved, when wallet data is not valid', function() {});

        $this->specify('account code should be unique', function () {
            /** @var Wallet $wallet */
            $wallet = $this->getFixture('wallets')->getModel('default');
            /** @var Currency $currency */
            $currency = Currency::find()->andWhere(['enabled' => 1])->one();
            /** @var AccountExchange $accountExist */
            $accountExist = AccountExchange::find()->one();

            $account = new AccountExchange;
            $account->wallet_id = $wallet->id;
            $account->currency_id = $currency->id;
            $account->code = $accountExist->code;
            expect($account->save())->false();
            expect($account->getErrors())->hasKey('code');
        });

        $this->specify('account code should be changed', function () {
            /** @var AccountExchange $account */
            $account = AccountExchange::find()->one();
            $account->code = strval(time());
            expect($account->save())->true();
            expect($account->dataIsValid)->true();
        });

        $this->specify('account currency should not be changed', function () {
            /** @var AccountExchange $account */
            $account = AccountExchange::find()->one();
            /** @var Currency $currency */
            $currency = Currency::find()->andWhere(['enabled' => 1])->andWhere(['<>', 'id', $account->currency_id])->one();
            $account->currency_id = $currency->id;
            expect($account->save())->false();
            expect($account->getErrors())->hasKey('currency_id');
        });

        $this->specify('account wallet should not be changed', function () {
            /** @var Wallet $wallet */
            $wallet = $this->getFixture('wallets')->getModel('custom');
            /** @var AccountExchange $account */
            $account = AccountExchange::find()->one();
            $account->wallet_id = $wallet->id;
            expect($account->save())->false();
            expect($account->getErrors())->hasKey('wallet_id');
        });

        $this->specify('account balance should be equal funds sum', function () {
            /** @var AccountExchange $account */
            $account = AccountExchange::find()->one();
            expect($account->funds)->isEmpty();
            expect($account->balance)->equals(0);
            
            expect((new Transaction([
                'recipientAccount' => $account, 
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => [500, $this->getFixture('funds')->getModel('basic')]
            ]))->apply())->true();

            $account->refresh();
            expect(count($account->funds))->equals(1);
            expect($account->funds[0]->type->name)->equals('Basic');
            expect($account->balance)->equals(500);

            expect((new Transaction([
                'recipientAccount' => $account,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => [50, $this->getFixture('funds')->getModel('basic')]
            ]))->apply())->true();

            $account->refresh();
            expect(count($account->funds))->equals(1);
            expect($account->funds[0]->type->name)->equals('Basic');
            expect($account->balance)->equals(550);

            expect((new Transaction([
                'recipientAccount' => $account,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => [200, $this->getFixture('funds')->getModel('promo')]
            ]))->apply())->true();

            $account->refresh();
            expect(count($account->funds))->equals(2);
            expect($account->funds[0]->type->name)->equals('Basic');
            expect($account->funds[1]->type->name)->equals('Promotional');
            expect($account->balance)->equals(750);
        });

        $this->specify('account when deleting it remains in the database', function () {});

        $this->specify('account should not be changed from the outside', function () {
            /** @var AccountExchange $account */
            $account = AccountExchange::find()->one();
            /** @var Currency $currency */
            $currency = Currency::find()->andWhere(['enabled' => 1])->andWhere(['<>', 'id', $account->currency_id])->one();
            expect($account->dataIsValid)->true();
            expect($account->currency_id)->notEquals($currency->id);
            Yii::$app->db->createCommand()->update(AccountExchange::tableName(), ['currency_id' => $currency->id], ['id' => $account->id])->execute();
            $account->refresh();
            expect($account->validate())->false();
            expect($account->dataIsValid)->false();
        });
    }

    public function testDelete()
    {
        $this->specify('account create and delete, and check validation', function(){
            /** @var Wallet $wallet */
            $wallet = $this->getFixture('wallets')->getModel('default');
            /** @var Currency $currency */
            $currency = $this->getFixture('currency')->getModel('USD');

            $account = new AccountExchange;
            $account->wallet_id = $wallet->id;
            $account->code = microtime();
            $account->currency_id = $currency->id;
            expect($account->save())->true();
            expect($account->delete())->true();
            expect($account->validate())->false();
        });
    }

    public function testCreateWithGroupLimitAllNumber()
    {
        $this->specify('account should not be saved because of limit for group `All Number Accounts In Wallet`', function() {
            /** @var \asmbr\wallet\models\Config $allNumberAccountInWallet */
            $allNumberAccountInWallet = $this->getFixture('configs')->getModel('group.allNumberAccountsInWallet');
            /** @var Currency $currency */
            $currency = $this->getFixture('currency')->getModel('USD');
            /** @var Wallet $wallet */
            $wallet = $this->getFixture('wallets')->getModel('config.group');
            $wallet->redefine();
            for($var = 0; $var < $allNumberAccountInWallet->value; ++$var) {
                $account = new AccountExchange;
                $account->wallet_id = $wallet->id;
                $account->code = microtime();
                $account->currency_id = $currency->id;
                $account->deleted_at = time();
                expect($account->save())->true();
            }

            $account = new AccountExchange;
            $account->wallet_id = $wallet->id;
            $account->code = microtime();
            $account->currency_id = $currency->id;
            $account->deleted_at = time();
            $result = $account->save();
            expect($result)->false();
        });
    }

    public function testCreateWithGroupLimitAllActive()
    {
        $this->specify('account should not be saved because of limit for group `All Active Account In Wallet`', function() {
            /** @var \asmbr\wallet\models\Config $allActiveAccountInWallet */
            $allActiveAccountInWallet = $this->getFixture('configs')->getModel('group.allActiveAccountInWallet');

            /** @var Currency $currency */
            $currency = $this->getFixture('currency')->getModel('USD');
            /** @var Wallet $wallet */
            $wallet = $this->getFixture('wallets')->getModel('config.group');
            $wallet->redefine();
            for($var = 0; $var < $allActiveAccountInWallet->value; ++$var) {
                $account = new AccountExchange;
                $account->wallet_id = $wallet->id;
                $account->code = microtime();
                $account->currency_id = $currency->id;
                expect($account->save())->true();
            }

            $account = new AccountExchange;
            $account->wallet_id = $wallet->id;
            $account->code = microtime();
            $account->currency_id = $currency->id;
            expect($account->save())->false();
        });
    }

    public function testCreateWithGroupLimitCurrency()
    {
        $this->specify('account should not be saved because of limit for group `All Active Account In Wallet`', function() {
            /** @var \asmbr\wallet\models\Config $numberActiveAccount */
            $numberActiveAccount = $this->getFixture('configs')->getModel('group.numberActiveAccount');

            /** @var Currency $currency */
            $currency = $this->getFixture('currency')->getModel('USD');
            /** @var Wallet $wallet */
            $wallet = $this->getFixture('wallets')->getModel('config.group');
            $wallet->redefine();
            for($var = 0; $var < $numberActiveAccount->value; ++$var) {
                $account = new AccountExchange;
                $account->wallet_id = $wallet->id;
                $account->code = microtime();
                $account->currency_id = $currency->id;
                expect($account->save())->true();
            }

            $account = new AccountExchange;
            $account->wallet_id = $wallet->id;
            $account->code = microtime();
            $account->currency_id = $currency->id;
            expect($account->save())->false();
        });
    }

    public function testCreateWithPersonalLimitAllNumber()
    {
        $this->specify('account should not be saved because of limit for personal `All Number Accounts In Wallet`', function() {
            /** @var \asmbr\wallet\models\Config $allNumberAccountInWallet */
            $allNumberAccountInWallet = $this->getFixture('configs')->getModel('wallet.allNumberAccountsInWallet');
            /** @var Currency $currency */
            $currency = $this->getFixture('currency')->getModel('USD');
            /** @var Wallet $wallet */
            $wallet = $this->getFixture('wallets')->getModel('extension');
            $wallet->redefine();
            for($var = 0; $var < $allNumberAccountInWallet->value; ++$var) {
                $account = new AccountExchange;
                $account->wallet_id = $wallet->id;
                $account->code = microtime();
                $account->currency_id = $currency->id;
                $account->deleted_at = time();
                expect($account->save())->true();
            }

            $account = new AccountExchange;
            $account->wallet_id = $wallet->id;
            $account->code = microtime();
            $account->currency_id = $currency->id;
            $account->deleted_at = time();
            expect($account->save())->false();
        });
    }

    public function testCreateWithPersonalLimitAllActive()
    {
        $this->specify('account should not be saved because of limit for personal `All Active Account In Wallet`', function() {
            /** @var \asmbr\wallet\models\Config $allActiveAccountInWallet */
            $allActiveAccountInWallet = $this->getFixture('configs')->getModel('wallet.allActiveAccountInWallet');

            /** @var Wallet $wallet */
            $wallet = $this->getFixture('wallets')->getModel('extension');
            do {
                /** @var Currency $currency */
                $currency = $this->getFixture('currency')->getModel('EUR');
                $account = new AccountExchange;
                $account->wallet_id = $wallet->id;
                $account->code = microtime();
                $account->currency_id = $currency->id;
                expect($account->save())->true();
                $wallet->refresh();
                $count = count($wallet->getActiveAccounts($currency));
            } while ($count < (int)$allActiveAccountInWallet->value);
            $account = new AccountExchange;
            $account->wallet_id = $wallet->id;
            $account->code = microtime();
            $account->currency_id = $currency->id;
            expect($account->save())->false();
        });
    }

    public function testCreateWithPersonalLimitCurrency()
    {
        $this->specify('account should not be saved because of limit for personal `Currency Number Active Account In Wallet`', function() {
            /** @var \asmbr\wallet\models\Config $numberActiveAccount */
            $numberActiveAccount = $this->getFixture('configs')->getModel('wallet.numberActiveAccount');

            /** @var Currency $currency */
            $currency = Currency::findOne(['code' => 'USD']);
            /** @var Wallet $wallet */
            $wallet = $this->getFixture('wallets')->getModel('extension');
            $wallet->redefine();
            for($var = 0; $var < $numberActiveAccount->value; ++$var) {
                $account = new AccountExchange;
                $account->wallet_id = $wallet->id;
                $account->code = microtime();
                $account->currency_id = $currency->id;
                expect($account->save())->true();
            }
            $account = new AccountExchange;
            $account->wallet_id = $wallet->id;
            $account->code = microtime();
            $account->currency_id = $currency->id;
            $result = $account->save();
            expect($result)->false();
        });
    }
}