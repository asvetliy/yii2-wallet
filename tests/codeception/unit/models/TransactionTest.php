<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/9/16
 * Time: 12:36 PM
 */

namespace asmbr\wallet\tests\codeception\unit\models;

use asmbr\wallet\models\AccountExchange;
use asmbr\wallet\models\Config;
use asmbr\wallet\models\Transaction;
use asmbr\wallet\models\TypeTransaction;
use asmbr\wallet\tests\codeception\_fixtures\TypeFundPriorityFixture;
use asmbr\wallet\tests\codeception\unit\TestCase;
use asmbr\wallet\tests\codeception\_fixtures\TypeTransactionFixture;
use asmbr\wallet\tests\codeception\_fixtures\AccountExchangeFixture;
use asmbr\wallet\tests\codeception\_fixtures\ConfigFixture;

class TransactionTest extends TestCase
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
    
    public function testCreate()
    {
        $this->specify('transaction should be saved', function () {
            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('W1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('W2USD'),
                'sum' => 500
            ]);
            
            expect($transaction->save())->true();
            /** @var TypeTransaction $typeTransfer */
            $typeTransfer = $this->getFixture('types')->getModel('sender_required');
            expect($transaction->type->id)->equals($typeTransfer->id);
            $this->expectTransaction($transaction);
        });

        $this->specify('transaction should be saved, when account data not valid', function () {
            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('W1USD-invalid'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('W2USD'),
                'sum' => 500
            ]);

            expect($transaction->save())->true();
            /** @var TypeTransaction $typeTransfer */
            $typeTransfer = $this->getFixture('types')->getModel('sender_required');
            expect($transaction->type->id)->equals($typeTransfer->id);
            expect($transaction->senderAccount->dataIsValid)->false();
        });

        $this->specify('transaction should be saved, when sender account is not defined', function () {
            $transaction = new Transaction([
                'recipientAccount' => $this->getFixture('accounts')->getModel('W1USD'),
                'type' => $this->getFixture('types')->getModel('sender_required'),
                'sum' => 500
            ]);

            expect((boolean) $transaction->type->required_sender)->true();
            expect($transaction->save())->false();
            expect($transaction->getErrors())->hasKey('sender_id');

            $transaction->type = $this->getFixture('types')->getModel('sender_not_required');

            expect((boolean) $transaction->type->required_sender)->false();
            expect($transaction->save())->true();
            $this->expectTransaction($transaction);
        });

        $this->specify('transaction should be saved, when sender and recipient are equals', function() {
            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('W1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('W1USD'),
                'sum' => 500
            ]);

            expect($transaction->save())->true();
            $this->expectTransaction($transaction);
        });

        $this->specify('transaction should not be saved, when currencies of accounts are different', function () {
            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('W1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('W2EUR'),
                'sum' => 500
            ]);
            
            expect($transaction->save())->false();
            expect($transaction->getErrors())->hasKey('recipient_id');
        });

        $this->specify('transaction should not be saved, when transaction type is disabled', function () {
            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('W1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('W2USD'),
                'type' => $this->getFixture('types')->getModel('disabled'),
                'sum' => 500
            ]);

            expect($transaction->save())->false();
            expect($transaction->getErrors())->hasKey('type_id');
        });

        $this->specify('transaction should not be saved, when code exist', function () {
            /** @var Transaction $existTrn */
            $existTrn = Transaction::find()->one();
            expect($existTrn)->isInstanceOf(Transaction::className());
            
            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('W1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('W2USD'),
                'code' => $existTrn->code,
                'sum' => 500
            ]);

            expect($transaction->save())->false();
            expect($transaction->getErrors())->hasKey('code');
        });
    }
    
    public function testApply()
    {
        $this->specify('transaction should be apply', function() {
            /** @var AccountExchange $sender */
            $sender = $this->getFixture('accounts')->getModel('W1USD');
            $senderBalance = $sender->balance;
            $transaction = new Transaction([
                'recipientAccount' => $sender,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => 500
            ]);
            
            $transaction->apply();
            $this->expectTransaction($transaction, true);
            expect($sender->balance - $senderBalance)->equals(500);

            /** @var AccountExchange $recipient */
            $recipient = $this->getFixture('accounts')->getModel('W2USD');
            $transaction = new Transaction([
                'senderAccount' => $sender,
                'recipientAccount' => $recipient,
                'type' => $this->getFixture('types')->getModel('sender_required'),
                'sum' => 500
            ]);
            
            expect($sender->balance)->equals(500);
            expect($recipient->balance)->equals(0);
            $transaction->apply();
            $this->expectTransaction($transaction, true);
            expect($sender->balance)->equals(0);
            expect($recipient->balance)->equals(500);

            $sender->refresh();
            $recipient->refresh();
            expect($sender->balance)->equals(0);
            expect($recipient->balance)->equals(500);
        });
        
        $this->specify('transaction should be apply, only for sender', function () {
            /** @var AccountExchange $sender */
            $sender = $this->getFixture('accounts')->getModel('W2USD');
            /** @var AccountExchange $recipient */
            $recipient = $this->getFixture('accounts')->getModel('W1USD');
            $transaction = new Transaction([
                'code' => 'custom',
                'senderAccount' => $sender,
                'recipientAccount' => $recipient,
                'sum' => 500
            ]);

            expect($transaction->save())->true();
            $this->expectTransaction($transaction);
            $transaction->apply(true, false);
            
            expect($transaction->senderOperation->isApplied)->true();
            expect($transaction->senderOperation->clarity)->notEmpty();

            expect($transaction->recipientOperation->isApplied)->false();
            expect($transaction->recipientOperation->clarity)->isEmpty();
        });

        $this->specify('transaction should be apply, only for recipient', function () {
            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('W2USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('W1USD'),
                'sum' => 500
            ]);
            
            expect($transaction->apply(false, true))->false();
            
            $transaction = Transaction::findOne(['code' => 'custom']);
            expect($transaction->apply(false, true))->true();

            $this->expectTransaction($transaction, true);
        });
        
        
    }
    
    public function expectTransaction(Transaction $transaction, $apply = false)
    {
        if($transaction->hasErrors()) return;
        
        $transaction->refresh();
        expect($transaction->dataIsValid)->true();

        if($transaction->senderOperation)
            expect($transaction->senderOperation->dataIsValid)->true();
        expect($transaction->recipientOperation->dataIsValid)->true();

        expect($transaction->type)->isInstanceOf(TypeTransaction::className());
        if($transaction->senderOperation)
            expect(count($transaction->operations))->equals(2);
        else
            expect(count($transaction->operations))->equals(1);

        if($transaction->senderOperation)
            expect($transaction->senderOperation->account_id)->equals($transaction->sender_id !== null ? $transaction->senderAccount->id : $transaction->sender_id);
        expect($transaction->recipientOperation->account_id)->equals($transaction->recipientAccount->id);

        if($transaction->senderOperation)
            expect($transaction->senderOperation->sum + $transaction->recipientOperation->sum)->equals(0);

        if(!$apply) {
            if($transaction->sender_id !== null) {
                expect($transaction->senderOperation->isApplied)->false();
                expect($transaction->senderOperation->clarity)->isEmpty();
            }
    
            expect($transaction->recipientOperation->isApplied)->false();
            expect($transaction->recipientOperation->clarity)->isEmpty();
        } else {
            if($transaction->senderOperation){
                expect($transaction->senderOperation->isApplied)->true();
                expect($transaction->senderOperation->clarity)->notEmpty();
                foreach ($transaction->senderOperation->clarity as $clarity) {
                    expect($clarity->dataIsValid)->true();
                }
            }

            expect($transaction->recipientOperation->isApplied)->true();
            expect($transaction->recipientOperation->clarity)->notEmpty();
            foreach ($transaction->recipientOperation->clarity as $clarity) {
                expect($clarity->dataIsValid)->true();
            }
        }
    }

    public function testCreateWithGroupLimitMinMaxSum()
    {
        $this->specify('transaction should be saved with group minimum and maximum limit ', function () {
            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('WL1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('WL2USD'),
                'sum' => 500
            ]);
            expect($transaction->save())->false();

            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('WL1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('WL2USD'),
                'sum' => 400
            ]);
            expect($transaction->save())->true();

            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('WL1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('WL2USD'),
                'sum' => 95
            ]);
            expect($transaction->save())->false();
        });
    }

    public function testCreateWithPersonalLimitMinMaxSum()
    {
        $this->specify('transaction should be saved with group minimum and maximum limit ', function () {
            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('WLX1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('WLX2USD'),
                'sum' => 500
            ]);
            expect($transaction->save())->false();

            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('WLX1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('WLX2USD'),
                'sum' => 400
            ]);
            expect($transaction->save())->true();

            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('WLX1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('WLX2USD'),
                'sum' => 95
            ]);
            expect($transaction->save())->false();
        });
    }

    public function testCreateWithGroupLimitCountOperationPerDay()
    {
        $this->specify('transaction should be saved with group limit count transaction per day', function () {
            /** @var Config $countTransaction */
            $countTransaction = $this->getFixture('configs')->getModel('group.countSenderTransactionPerDay');
            for($var = 0; $var < $countTransaction->value; ++$var) {
                $transaction = new Transaction([
                    'senderAccount' => $this->getFixture('accounts')->getModel('WL1USD'),
                    'recipientAccount' => $this->getFixture('accounts')->getModel('WL2USD'),
                    'sum' => 150
                ]);
                expect($transaction->save())->true();
            }

            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('WL1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('WL2USD'),
                'sum' => 150
            ]);
            expect($transaction->save())->false();
        });
    }

    public function testCreateWithPersonalLimitCountOperationPerDay()
    {
        $this->specify('transaction should be saved with group limit count transaction per day', function () {
            /** @var Config $countTransaction */
            $countTransaction = $this->getFixture('configs')->getModel('group.countSenderTransactionPerDay');
            for($var = 0; $var < $countTransaction->value; ++$var) {
                $transaction = new Transaction([
                    'senderAccount' => $this->getFixture('accounts')->getModel('WLX1USD'),
                    'recipientAccount' => $this->getFixture('accounts')->getModel('WLX2USD'),
                    'sum' => 150
                ]);
                expect($transaction->save())->true();
            }

            $transaction = new Transaction([
                'senderAccount' => $this->getFixture('accounts')->getModel('WLX1USD'),
                'recipientAccount' => $this->getFixture('accounts')->getModel('WLX2USD'),
                'sum' => 150
            ]);
            expect($transaction->save())->false();
        });
    }

    public function testLimitBalanceOnAccount()
    {
        $this->specify('recharge up to the limit', function () {
            /** @var AccountExchange $sender */
            $sender = $this->getFixture('accounts')->getModel('WL1USD');
            $sender->redefine();
            $transaction = new Transaction([
                'recipientAccount' => $sender,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => 20000
            ]);
            expect($transaction->apply())->true();

            $transaction = new Transaction([
                'recipientAccount' => $sender,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => 10000
            ]);
            expect($transaction->apply())->true();

            $transaction = new Transaction([
                'recipientAccount' => $sender,
                'type' => $this->getFixture('types')->getModel('sender_not_required'),
                'sum' => 20000
            ]);
            expect($transaction->apply())->false();
        });
    }
}