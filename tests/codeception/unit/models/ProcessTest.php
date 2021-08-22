<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/10/16
 * Time: 4:57 PM
 */

namespace asmbr\wallet\tests\codeception\unit\models;

use asmbr\wallet\models\AccountExchange;
use asmbr\wallet\models\Process;
use asmbr\wallet\models\Transaction;
use asmbr\wallet\models\TypeTransaction;
use asmbr\wallet\models\Config;
use asmbr\wallet\tests\codeception\_fixtures\AccountExchangeFixture;
use asmbr\wallet\tests\codeception\_fixtures\TypeFundPriorityFixture;
use asmbr\wallet\tests\codeception\_fixtures\TypeTransactionFixture;
use asmbr\wallet\tests\codeception\_fixtures\ConfigFixture;
use asmbr\wallet\tests\codeception\unit\TestCase;
use AspectMock\Test;

class ProcessTest extends TestCase
{
    public function fixtures()
    {
        return [
            'accounts' => [
                'class' => AccountExchangeFixture::className(),
                'dataFile' => '@tests/codeception/_fixtures/data/account_exchange.php'
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

    public function testApplying()
    {
        Test::double(Process::className(), ['getTransactions' => function() {
            return $this->_transactions;
        }]);

        $this->specify('favorable scenario', function () {

            $sum = 50;
            $tax_percent = 5;
            /** @var AccountExchange $senderAccount */
            $senderAccount = $this->getFixture('accounts')->getModel('W1USD');
            /** @var AccountExchange $recipientAccount */
            $recipientAccount = $this->getFixture('accounts')->getModel('W2USD');
            /** @var AccountExchange $taxAccount */
            $taxAccount = $this->getFixture('accounts')->getModel('W0USD');

            (new Transaction([
                'recipientAccount' => $senderAccount,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => $sum + $sum * ($tax_percent / 100)
            ]))->apply();
            expect($senderAccount->balance)->equals($sum + $sum * ($tax_percent / 100));

            $process = new Process;
            expect($process->getTransactions())->isEmpty();

            $process->pushTransactions(new Transaction([
                'senderAccount' => $senderAccount,
                'recipientAccount' => $recipientAccount,
                'sum' => $sum
            ]));
            $process->pushTransactions(new Transaction([
                'senderAccount' => $senderAccount,
                'recipientAccount' => $taxAccount,
                'sum' => $sum * ($tax_percent / 100)
            ]));
            expect($process->getTransactions())->notEmpty();
            expect(count($process->getTransactions()))->equals(2);

            expect($process->save())->true();

            $pid = null;
            $transactions = $process->getTransactions();
            foreach ($transactions as $transaction) {
                /** @var Transaction $transaction */
                if($pid === null) $pid = $transaction->pid;
                expect($transaction->pid)->notNull();
                expect($transaction->pid)->equals($pid);
            }

            expect($process->apply())->true();
            expect($senderAccount->balance)->equals(0);
            expect($recipientAccount->balance)->equals($sum);
            expect($taxAccount->balance)->equals($sum * ($tax_percent / 100));
        });
        
        $this->specify('', function () {

            $sum = 50;
            $tax_percent = 5;
            /** @var AccountExchange $senderAccount */
            $senderAccount = $this->getFixture('accounts')->getModel('W1USD');
            /** @var AccountExchange $recipientAccount */
            $recipientAccount = $this->getFixture('accounts')->getModel('W1USD-invalid');
            /** @var AccountExchange $taxAccount */
            $taxAccount = $this->getFixture('accounts')->getModel('W0USD');

            (new Transaction([
                'recipientAccount' => $senderAccount,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => $sum + $sum * ($tax_percent / 100)
            ]))->apply();
            expect($senderAccount->balance)->equals($sum + $sum * ($tax_percent / 100));

            $process = new Process;
            expect($process->getTransactions())->isEmpty();

            $process->pushTransactions(new Transaction([
                'senderAccount' => $senderAccount,
                'recipientAccount' => $recipientAccount,
                'sum' => $sum
            ]));
            $process->pushTransactions(new Transaction([
                'senderAccount' => $senderAccount,
                'recipientAccount' => $taxAccount,
                'sum' => $sum * ($tax_percent / 100)
            ]));
            expect($process->getTransactions())->notEmpty();
            expect(count($process->getTransactions()))->equals(2);

            expect($process->apply())->false();
            expect($process->apply($recipientAccount))->false();
            expect($process->apply($senderAccount))->true();

        });
    }

    public function testValidate()
    {
        $this->specify('validate limit sum transaction', function () {
            /** @var AccountExchange $sender */
            $sender = $this->getFixture('accounts')->getModel('WL1USD');

            $transaction = new Transaction([
                'recipientAccount' => $sender,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => 20000
            ]);
            expect($transaction->apply())->true();

            /** @var AccountExchange recipient */
            $recipient = $this->getFixture('accounts')->getModel('WL2USD');
            /** @var TypeTransaction $type */
            $type = $this->getFixture('types')->getModel('sender_required');

            $process = new Process;
            $process->pushTransactions(new Transaction([
                'senderAccount' => $sender,
                'recipientAccount' => $recipient,
                'type' => $type,
                'sum' => 400
            ]));
            expect($process->validate())->true();

            $process->pushTransactions(new Transaction([
                'senderAccount' => $sender,
                'recipientAccount' => $recipient,
                'type' => $this->getFixture('types')->getModel('sender_required'),
                'sum' => 95
            ]));
            expect($process->validate())->false();
            $countError = $process->getErrors();

            $process->pushTransactions(new Transaction([
                'senderAccount' => $sender,
                'recipientAccount' => $recipient,
                'type' => $this->getFixture('types')->getModel('sender_required'),
                'sum' => 500
            ]));
            expect($process->validate())->false();
            expect($countError < $process->getErrors())->true();
        });

        $this->specify('validate limit count operation per day', function () {

            /** @var TypeTransaction $type */
            $type = $this->getFixture('types')->getModel('sender_required');
            $process = new Process;

            /** @var Config $countTransaction */
            $countTransaction = $this->getFixture('configs')->getModel('group.countSenderTransactionPerDay');
            for($var = 0; $var < $countTransaction->value; ++$var) {
                $process->pushTransactions(new Transaction([
                    'senderAccount' => $this->getFixture('accounts')->getModel('WL1USD'),
                    'recipientAccount' => $this->getFixture('accounts')->getModel('WL2USD'),
                    'sum' => 150,
                    'type' => $type
                ]));
                expect($process->validate())->true();
            }
            expect($process->save())->true();

            $process = new Process;
            $process->pushTransactions(new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('WL1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('WL2USD'),
                'type' => $type,
                'sum' => 150
            ]));
            expect($process->validate())->false();
        });
    }

    public function testValidateLimitAccountBalance()
    {

        $this->specify('validate limit balance account', function () {
            $process = new Process;
            /** @var AccountExchange $sender */
            $sender = $this->getFixture('accounts')->getModel('WL1USD');
            $sender->redefine();

            $process->pushTransactions(new Transaction([
                'recipientAccount' => $sender,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => 20000
            ]));

            $process->pushTransactions(new Transaction([
                'recipientAccount' => $sender,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => 10000
            ]));
            expect($process->validate())->true();
            expect($process->apply())->true();

            $process = new Process;
            $process->pushTransactions(new Transaction([
                'recipientAccount' => $sender,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => 20000
            ]));
            expect($process->validate())->false();
        });
    }
}