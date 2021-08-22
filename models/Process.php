<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 5/26/16
 * Time: 2:13 PM
 */

namespace asmbr\wallet\models;

use yii;
use yii\helpers\ArrayHelper;
use yii\base\Component;
use yii\base\InvalidValueException;

/**
 * Class Process
 * @package asmbr\wallet\models
 */
class Process extends Component
{

    /** @var array Transaction[] */
    private $_transactions = [];
    private $_errors;

    /**
     * @param Transaction|Transaction[] $transactions
     */
    public function pushTransactions($transactions)
    {
        if($transactions instanceof Transaction)
            $transactions = [$transactions];

        foreach ($transactions as $transaction) {
            if($transaction instanceof Transaction && $transaction->isNewRecord) {
                $this->_transactions[] = $transaction;
            } else
                throw new InvalidValueException();
        }
    }

    /**
     * @param null|AccountExchange|AccountExchange[] $validation
     * @return false|int
     * @throws yii\db\Exception
     */
    public function save($validation = null)
    {
        if($this->validate($validation)) {
            $dbTrn = Yii::$app->db->beginTransaction();

            $pid = null;
            foreach ($this->_transactions as $transaction) {
                /** @var Transaction $transaction */
                if($pid !== null) $transaction->pid = $pid;
                if($transaction->isNewRecord && !$transaction->save()) {
                    $dbTrn->rollBack();
                    return false;
                }
                if($pid === null) $pid = $transaction->pid;
            }

            $dbTrn->commit();
            return $pid;
        }
        return false;
    }

    /**
     * @param null|AccountExchange|AccountExchange[] $validation
     * @param null|AccountExchange|AccountExchange[] $apply
     * @return bool
     * @throws yii\db\Exception
     */
    public function apply($validation = null, $apply = null)
    {
        if($pid = $this->save($validation)) {
            if($apply instanceof AccountExchange) $apply = [$apply];
            if(is_array($apply)) $apply = ArrayHelper::getColumn($apply, 'id');
            $valid = true;
            foreach ($this->_transactions as $transaction) {
                /** @var Transaction $transaction */
                if($apply === null) {
                    $valid = $valid && $transaction->apply();
                } elseif(is_array($apply)) {
                    $senderOperation = in_array($transaction->senderAccount->id, $apply) ? true : false;
                    $recipientOperation = in_array($transaction->recipientAccount->id, $apply) ? true : false;
                    $valid = $valid && $transaction->apply($senderOperation, $recipientOperation);
                } else
                    throw new InvalidValueException();
            }
            return $valid ? $pid : false;
        }
        return false;
    }

    //region VALIDATION

    /**
     * @param null|AccountExchange|AccountExchange[] $accounts
     * @param bool $clearErrors
     * @return bool
     */
    public function validate($accounts = null, $clearErrors = true)
    {
        if ($clearErrors) {
            $this->clearErrors();
        }

        $existsAccounts = [];
        foreach ($this->_transactions as $transaction) {
            /** @var Transaction $transaction */
            if($transaction->senderAccount !== null)
                $existsAccounts[$transaction->senderAccount->id] = $transaction->senderAccount;
            if($transaction->recipientAccount !== null)
                $existsAccounts[$transaction->recipientAccount->id] = $transaction->recipientAccount;
        }

        if($accounts === null) {
            $accounts = array_values($existsAccounts);
        } elseif($accounts instanceof AccountExchange && in_array($accounts->id, array_keys($existsAccounts))) {
            $accounts = [$accounts];
        } elseif(!is_array($accounts))
            throw new InvalidValueException('Specified accounts not involved in the process or is not correct.');

        foreach ($accounts as $account) {
            if(!$account instanceof AccountExchange)
                throw new InvalidValueException('Specified accounts not involved in the process or is not correct.');

            $this->validateAccountData($account);
            $this->validateSumProcess($account, $this->_transactions);
            $this->validateLimitSumTransaction($account, $this->_transactions);
            $this->validateLimitCountOperationPerDay($this->_transactions);
            $this->validateLimitBalanceAccount($this->_transactions);
        }

        return !$this->hasErrors();
    }

    /**
     * @param AccountExchange $account
     */
    protected function validateAccountData($account)
    {
        if(!$account->dataIsValid)
            $this->addError($account, Yii::t('wlt.model', 'Account Exchange is not valid.'));
    }

    /**
     * @param AccountExchange $account
     * @param Transaction[] $transactions
     */
    protected function validateSumProcess($account, $transactions)
    {
        $sum = 0;
        foreach ($transactions as $transaction) {
            if ($transaction->senderAccount && $transaction->senderAccount->id == $account->id)
                $sum += $transaction->senderOperation->sum;
            if ($transaction->recipientAccount && $transaction->recipientAccount->id == $account->id)
                $sum += $transaction->recipientOperation->sum;
        }

        /** @var \asmbr\wallet\Module $module */
        $module = Yii::$app->getModule('wallet');
        if (!$module->negativeAccountExchangeBalance && $account->balance + $sum < 0)
            $this->addError($account, Yii::t('wlt.model', 'Insufficient funds.'));
    }

    /**
     * @param AccountExchange $account
     * @param Transaction[] $transactions
     */
    protected function validateLimitSumTransaction($account, $transactions)
    {
        foreach ($transactions as $transaction) {
            $transaction->redefine();
            if (isset($transaction->minimumBalanceTransaction))
                if ($transaction->sum < $transaction->minimumBalanceTransaction)
                    $this->addError($account, Yii::t('wlt.model', 'The transaction is less than the specified limit for the count'));

            if (isset($transaction->maximumBalanceTransaction))
                if ($transaction->sum > $transaction->maximumBalanceTransaction)
                    $this->addError($account, Yii::t('wlt.model', 'The transaction is greater than the specified limit for the count'));
        }
    }

    /**
     * @param Transaction[] $transactions
     */
    protected function validateLimitCountOperationPerDay($transactions)
    {
        foreach ($transactions as $transaction) {
            $transaction->redefine();
            if (isset($transaction->countSenderTransactionPerDay)) {
                $count = (int)$transaction::getCountSenderOperationPerDay($transaction->senderAccount);
                if ((int)$transaction->countSenderTransactionPerDay <= $count){
                    $this->addError($transaction->senderAccount, Yii::t('wlt.model', 'The transaction is less than the specified limit for the count'));
                }
            }
        }
    }

    /**
     * @param Transaction[] $transactions
     */
    protected function validateLimitBalanceAccount($transactions)
    {
        foreach ($transactions as $transaction){
            $transaction->redefine();
            if($transaction->senderAccount) {
                $balance = $transaction->senderAccount->balance - $transaction->sum;
                if($transaction->senderAccount->minimumBalanceAccount && $balance < $transaction->senderAccount->minimumBalanceAccount)
                    $this->addError($transaction->senderAccount, Yii::t('wlt.model', 'Limit sender account balance is over, minimum {sum}', ['sum' => $transaction->senderAccount->minimumBalanceAccount]));
            }

            $balance = $transaction->recipientAccount->balance + $transaction->sum;
            if($transaction->recipientAccount->maximumBalanceAccount && $balance > $transaction->recipientAccount->maximumBalanceAccount)
                $this->addError($transaction->recipientAccount, Yii::t('wlt.model', 'Limit recipient account is over, maximum {sum}', ['sum' => $transaction->recipientAccount->maximumBalanceAccount]));
        }
    }

    //endregion

    //region WORK WITH ERRORS

    /**
     * @param AccountExchange $account
     * @param string $message
     */
    public function addError(AccountExchange $account, $message = '')
    {
        $this->_errors[$account->id][] = $message;
    }

    /**
     * @param AccountExchange|null $account
     * @return bool
     */
    public function hasErrors(AccountExchange $account = null)
    {
        return $account === null ? !empty($this->_errors) : isset($this->_errors[$account->id]);
    }

    /**
     * @param AccountExchange|null $account
     * @return mixed
     */
    public function getErrors(AccountExchange $account = null)
    {
        return $account === null ? $this->_errors : $this->_errors[$account->id];
    }

    /**
     * @param AccountExchange|null $account
     */
    public function clearErrors(AccountExchange $account = null)
    {
        if ($account === null) {
            $this->_errors = [];
        } else {
            unset($this->_errors[$account->id]);
        }
    }

    //endregion

    /**
     * @return array
    */
    public function dump() {
        $result = [];
        foreach ($this->_transactions as $transaction) {
            /** @var Transaction $transaction */
            $result[] = [
                'type' => $transaction->type->name,
                'description' => $transaction->description,
                'sender' => $transaction->senderAccount ? $transaction->senderAccount->code : null,
                'recipient' => $transaction->recipientAccount ? $transaction->recipientAccount->code : null,
                'sum' => $transaction->sum
            ];
        }
        return $result;
    }
    
}


