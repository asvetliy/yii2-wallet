<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/21/16
 * Time: 11:31 AM
 */

namespace asmbr\wallet\tests\codeception\unit\models;

use asmbr\wallet\models\AccountExchange;
use yii\helpers\ArrayHelper;
use asmbr\wallet\models\Transaction;
use asmbr\wallet\models\TypeFund;
use asmbr\wallet\tests\codeception\_fixtures\AccountExchangeFixture;
use asmbr\wallet\tests\codeception\_fixtures\TypeFundFixture;
use asmbr\wallet\tests\codeception\_fixtures\TypeFundPriorityFixture;
use asmbr\wallet\tests\codeception\_fixtures\TypeTransactionFixture;
use asmbr\wallet\tests\codeception\unit\TestCase;

class FundTest extends TestCase
{
    public function fixtures()
    {
        return [
            'accounts' => [
                'class' => AccountExchangeFixture::className(),
                'dataFile' => '@tests/codeception/_fixtures/data/account_exchange.php'
            ],
            'transactions' => [
                'class' => TypeTransactionFixture::className(),
                'dataFile' => '@tests/codeception/_fixtures/data/type_transaction.php'
            ],
            'types' => [
                'class' => TypeFundFixture::className(),
                'dataFile' => '@tests/codeception/_fixtures/data/type_fund.php'
            ],
            'fundsPriority' => [
                'class' => TypeFundPriorityFixture::className(),
                'dataFile' => '@tests/codeception/_fixtures/data/type_fund_priority.php'
            ],
        ];
    }

    public function testFund()
    {
        $this->specify('upcash default', function() {

            $sum = 500;
            $transaction = new Transaction([
                'type' => $this->getFixture('transactions')->getModel('sender_not_required'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('W1USD'),
                'sum' => $sum
            ]);

            expect($transaction->apply())->true();
            if($transaction->senderOperation) {
               expect(count($transaction->senderOperation->clarity))->equals(1);
               expect($transaction->senderOperation->clarity[0]->fundType->code)->equals(TypeFund::DEFAULT_CODE);
               expect($transaction->senderOperation->clarity[0]->sum)->equals(-$sum);
            }
            expect($transaction->senderAccount)->null();
            expect($transaction->recipientAccount->balance)->equals($sum);

            $this->expectTransactionClarity($transaction);
        });

        $this->specify('upcash with specify fund type', function() {

            $sum = [500, $this->getFixture('types')->getModel('promo')];
            $transaction = new Transaction([
                'type' => $this->getFixture('transactions')->getModel('sender_not_required'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('W1USD'),
                'sum' => $sum
            ]);

            expect($transaction->apply())->true();
            if($transaction->senderOperation) {
                expect(count($transaction->senderOperation->clarity))->equals(1);
                expect($transaction->senderOperation->clarity[0]->fundType->code)->equals('FT_PROMO');
                expect($transaction->senderOperation->clarity[0]->sum)->equals(-$sum[0]);
                expect($transaction->senderOperation->account)->null();
            }
            expect($transaction->recipientAccount->balance)->equals(1000);

            $this->expectTransactionClarity($transaction);
        });

        $this->specify('upcash with specify fund type (disable)', function() {
            $sum = [500, $this->getFixture('types')->getModel('disabled')];
            $transaction = new Transaction([
                'type' => $this->getFixture('transactions')->getModel('sender_not_required'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('W1USD'),
                'sum' => $sum
            ]);

            expect($transaction->apply())->false();
            expect($transaction->isNewRecord)->false();
            if($transaction->senderOperation) {
                expect($transaction->senderOperation->enrolled_at)->notNull();
                expect(count($transaction->senderOperation->clarity))->equals(1);
                expect($transaction->senderOperation->clarity[0]->fundType->code)->equals('FT_DISABLED');
            }
            expect($transaction->recipientOperation->enrolled_at)->null();
            expect($transaction->recipientOperation->apply())->false();
        });

        $this->specify('transfer 1', function () {

            $sum = 300;
            /** @var AccountExchange $senderAccount */
            $senderAccount = $this->getFixture('accounts')->getModel('W1USD');
            /** @var AccountExchange $recipientAccount */
            $recipientAccount = $this->getFixture('accounts')->getModel('W2USD');

            expect(count($senderAccount->funds))->equals(2);
            expect($senderAccount->funds[0]->type->code)->equals('FT_BASIC');
            expect($senderAccount->funds[0]->balance)->equals(500);
            expect($senderAccount->funds[1]->type->code)->equals('FT_PROMO');
            expect($senderAccount->funds[1]->balance)->equals(500);
            expect($senderAccount->funds[0]->type->priority)->lessThan($senderAccount->funds[1]->type->priority);
            
            $transaction = new Transaction([
                'type' => $this->getFixture('transactions')->getModel('sender_required'),
                'senderAccount' => $senderAccount,
                'recipientAccount' => $recipientAccount,
                'sum' => $sum
            ]);

            expect($transaction->apply())->true();
            expect(count($transaction->senderOperation->clarity))->equals(1);
            expect($transaction->senderOperation->clarity[0]->fundType->code)->equals('FT_BASIC');
            expect($senderAccount->funds[0]->type->code)->equals('FT_BASIC');
            expect($senderAccount->funds[0]->balance)->equals(200);
            expect($senderAccount->funds[1]->type->code)->equals('FT_PROMO');
            expect($senderAccount->funds[1]->balance)->equals(500);

            $this->expectTransactionClarity($transaction);            
        });
        
        $this->specify('transfer 2', function() {
            
            $sum = 500;
            /** @var AccountExchange $senderAccount */
            $senderAccount = $this->getFixture('accounts')->getModel('W1USD');
            /** @var AccountExchange $recipientAccount */
            $recipientAccount = $this->getFixture('accounts')->getModel('W2USD');
            
            $transaction = new Transaction([
                'type' => $this->getFixture('transactions')->getModel('sender_required'),
                'senderAccount' => $senderAccount,
                'recipientAccount' => $recipientAccount,
                'sum' => $sum
            ]);

            expect($transaction->apply())->true();
            expect(count($transaction->senderOperation->clarity))->equals(2);
            expect($transaction->senderOperation->clarity[0]->fundType->code)->equals('FT_BASIC');
            expect($transaction->senderOperation->clarity[0]->sum)->equals(-200);
            expect($transaction->senderOperation->clarity[1]->fundType->code)->equals('FT_PROMO');
            expect($transaction->senderOperation->clarity[1]->sum)->equals(-300);

            $this->expectTransactionClarity($transaction);

            expect($senderAccount->funds[0]->type->code)->equals('FT_BASIC');
            expect($senderAccount->funds[0]->balance)->equals(0);
            expect($senderAccount->funds[1]->type->code)->equals('FT_PROMO');
            expect($senderAccount->funds[1]->balance)->equals(200);
        });

        $this->specify('transfer 3', function() {

            $sum = 200;
            /** @var AccountExchange $senderAccount */
            $senderAccount = $this->getFixture('accounts')->getModel('W1USD');
            /** @var AccountExchange $recipientAccount */
            $recipientAccount = $this->getFixture('accounts')->getModel('W2USD');
            
            $transaction = new Transaction([
                'type' => $this->getFixture('transactions')->getModel('sender_required'),
                'senderAccount' => $senderAccount,
                'recipientAccount' => $recipientAccount,
                'sum' => $sum
            ]);

            expect($transaction->apply())->true();
            expect(count($transaction->senderOperation->clarity))->equals(1);
            expect($transaction->senderOperation->clarity[0]->fundType->code)->equals('FT_PROMO');
            expect($transaction->senderOperation->clarity[0]->sum)->equals(-200);

            $this->expectTransactionClarity($transaction);

            expect($senderAccount->funds[0]->type->code)->equals('FT_BASIC');
            expect($senderAccount->funds[0]->balance)->equals(0);
            expect($senderAccount->funds[1]->type->code)->equals('FT_PROMO');
            expect($senderAccount->funds[1]->balance)->equals(0);

            expect($senderAccount->balance)->equals(0);
            expect($recipientAccount->balance)->equals(1000);

            expect($recipientAccount->funds[0]->type->code)->equals('FT_BASIC');
            expect($recipientAccount->funds[0]->balance)->equals(500);
            expect($recipientAccount->funds[1]->type->code)->equals('FT_PROMO');
            expect($recipientAccount->funds[1]->balance)->equals(500);
        });
    }

    public function expectTransactionClarity(Transaction $transaction)
    {
        if($transaction->senderOperation){
            expect(count($transaction->senderOperation->clarity))->equals(count($transaction->recipientOperation->clarity));
            $senderSum = array_sum(ArrayHelper::getColumn($transaction->senderOperation->clarity, 'sum'));
            $recipientSum = array_sum(ArrayHelper::getColumn($transaction->recipientOperation->clarity, 'sum'));
            expect($senderSum + $recipientSum)->equals(0);
            foreach ($transaction->senderOperation->clarity as $i => $senderClarity) {
                $recipientClarity = $transaction->recipientOperation->clarity[$i];
                expect($senderClarity->fundType->id)->equals($recipientClarity->fundType->id);
                expect($senderClarity->sum + $recipientClarity->sum)->equals(0);
            }
        }
    }
}