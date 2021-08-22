<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 5/31/16
 * Time: 5:33 PM
 */

namespace asmbr\wallet\behaviors;

use yii;
use yii\base\Behavior;
use asmbr\wallet\models\Transaction;
use asmbr\wallet\models\TransactionEvent;

class DependentTransactionBehavior extends Behavior
{
    /** @var Transaction */
    public $owner;

    public function events()
    {
        return [
            Transaction::EVENT_PREPARE_PROCESS => 'prepareProcessArray'
        ];
    }

    public function prepareProcessArray(TransactionEvent $event)
    {
        /** @var \asmbr\wallet\Module $module */
        $module = Yii::$app->getModule('wallet');
        /** @var \asmbr\wallet\models\DependentTransaction $classDependentTransaction */
        $classDependentTransaction = $module->modelMap['DependentTransaction'];
        $query = $classDependentTransaction::find();
        if($this->owner->senderAccount) {
            $query->andWhere([
                'wallet_group_id' => $this->owner->senderAccount->wallet->wallet_group_id,
                'currency_id' => $this->owner->senderAccount->currency_id,
                'owner_type_trn_id' => $this->owner->type->id,
                'opr_type' => $classDependentTransaction::OPERATION_TYPE_OUTBOUND,
                'enabled' => true
            ]);
        }
        if($this->owner->recipientAccount) {
            $query->orWhere([
                'wallet_group_id' => $this->owner->recipientAccount->wallet->wallet_group_id,
                'currency_id' => $this->owner->recipientAccount->currency_id,
                'owner_type_trn_id' => $this->owner->type->id,
                'opr_type' => $classDependentTransaction::OPERATION_TYPE_INCOMING,
                'enabled' => true
            ]);
        }

        /** @var \asmbr\wallet\models\DependentTransaction[] $dependencies */
        $dependencies = $query->all();
        foreach ($dependencies as $dependent) {
            if(($transaction = $dependent->createTransaction($this->owner)) instanceof Transaction) {
                $event->result = array_merge($event->result, $transaction->prepareProcessArray());
            }
        }
    }
}