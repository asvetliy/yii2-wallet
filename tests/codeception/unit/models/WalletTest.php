<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/7/16
 * Time: 12:18 PM
 */

namespace asmbr\wallet\tests\codeception\unit\models;

use asmbr\wallet\models\AccountExchange;
use asmbr\wallet\models\Currency;
use asmbr\wallet\models\WalletGroup;
use yii;
use asmbr\wallet\models\Wallet;
use asmbr\wallet\tests\codeception\_fixtures\WalletGroupFixture;
use asmbr\wallet\tests\codeception\unit\TestCase;

class WalletTest extends TestCase
{
    public function fixtures()
    {
        return [
            'walletGroup' => [
                'class' => WalletGroupFixture::className(),
                'dataFile' => '@tests/codeception/_fixtures/data/wallet_group.php'
            ]
        ];
    }

    public function testWallet()
    {
        $this->specify('wallet should be saved', function () {
            $wallet = new Wallet();
            $wallet->group = $this->getFixture('walletGroup')->getModel('default');
            $wallet->entity_id = 1;
            expect_that($wallet->save());
            expect_that($wallet->dataIsValid);
            expect_not(count($wallet->accounts));
        });

        $this->specify('wallet should change group', function () {
            /** @var Wallet $wallet */
            $wallet = Wallet::find()->one();

            $wallet->group = $this->getFixture('walletGroup')->getModel('valid');
            expect_that($wallet->save());
            expect($wallet->group->id)->equals($this->getFixture('walletGroup')->getModel('valid')->id);
            expect($wallet->wallet_group_id)->equals($this->getFixture('walletGroup')->getModel('valid')->id);
            expect_that($wallet->dataIsValid);

            $wallet->group = $this->getFixture('walletGroup')->getModel('invalid');
            expect_not($wallet->group->dataIsValid);
            expect_not($wallet->save());
        });

        $this->specify('wallet should not be changed from the outside', function() {
            /** @var Wallet $wallet */
            $wallet = Wallet::find()->one();
            expect_that($wallet->dataIsValid);
            /** @var WalletGroup $changeGroup */
            $changeGroup = $this->getFixture('walletGroup')->getModel('default');
            Yii::$app->db->createCommand()->update(Wallet::tableName(), ['wallet_group_id' => $changeGroup->id], ['id' => $wallet->id])->execute();
            $wallet->refresh();
            expect_not($wallet->dataIsValid);
        });

        $this->specify('wallet should be contained account exchanges', function() {
            /** @var Wallet $wallet */
            $wallet = Wallet::find()->one();
            /** @var Currency $currency */
            $currency = Currency::find()->one();
            expect(count($wallet->accounts))->equals(0);
            $accountExchange = new AccountExchange(['wallet_id' => $wallet->id, 'currency_id' => $currency->id]);
            $accountExchange->save();
            $wallet->refresh();
            expect(count($wallet->accounts))->equals(1);
            expect_that($wallet->accounts[0]->id === $accountExchange->id);
        });
    }
}