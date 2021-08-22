<?php

namespace asmbr\wallet\models;

use yii\behaviors\TimestampBehavior;
use asmbr\math\MathHelper;
use yii;

/**
 * This is the model class for table "wlt_dependent_transaction".
 *
 * @property integer $id
 * @property integer $wallet_group_id
 * @property integer $sender_id
 * @property integer $currency_id
 * @property integer $recipient_id
 * @property integer $owner_type_trn_id
 * @property integer $type_trn_id
 * @property integer $opr_type
 * @property double $percent
 * @property string $static
 * @property string $trn_desc
 * @property string $desc
 * @property integer $enabled
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property WalletGroup|null $walletGroup
 * @property Currency|null $currency
 * @property TypeTransaction|null $ownerTypeTransaction
 * @property TypeTransaction|null $typeTransaction
 * @property AccountExchange|null $senderAccount
 * @property AccountExchange|null $recipientAccount
 *
 */
class DependentTransaction extends BaseRecord
{
    const ACCOUNT_SENDER = -1;
    const ACCOUNT_RECIPIENT = -2;

    const OPERATION_TYPE_INCOMING = 1;
    const OPERATION_TYPE_OUTBOUND = 2;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wlt_dependent_transaction';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['wallet_group_id', 'currency_id', 'owner_type_trn_id', 'sender_id', 'recipient_id', 'type_trn_id', 'opr_type'], 'required'],
            [['wallet_group_id', 'currency_id', 'owner_type_trn_id', 'sender_id', 'recipient_id', 'type_trn_id', 'enabled', 'created_at', 'updated_at'], 'integer'],
            ['type_trn_id', 'compare', 'compareAttribute' => 'owner_type_trn_id', 'operator' => '!='],
            ['static', 'required', 'enableClientValidation' => false, 'when' => function($model) {return !$model->percent;}],
            ['percent', 'required', 'enableClientValidation' => false, 'when' => function($model) {return !$model->static;}],
            [['static', 'percent'], 'default', 'value' => 0],
            [['percent', 'static'], 'number'],
            [['trn_desc', 'desc'], 'string', 'max' => 1024],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('wlt.models', 'ID'),
            'wallet_group_id' => Yii::t('wlt.models', 'Wallet Group ID'),
            'currency_id' => Yii::t('wlt.models', 'Currency ID'),
            'owner_type_trn_id' => Yii::t('wlt.models', 'Owner Transaction Type ID'),
            'sender_id' => Yii::t('wlt.models', 'Sender ID'),
            'recipient_id' => Yii::t('wlt.models', 'Recipient ID'),
            'type_trn_id' => Yii::t('wlt.models', 'Transaction Type ID'),
            'percent' => Yii::t('wlt.models', 'Percent'),
            'static' => Yii::t('wlt.models', 'Static'),
            'trn_desc' => Yii::t('wlt.models', 'Transaction Description'),
            'desc' => Yii::t('wlt.models', 'Description'),
            'enabled' => Yii::t('wlt.models', 'Enabled'),
            'created_at' => Yii::t('wlt.models', 'Created At'),
            'updated_at' => Yii::t('wlt.models', 'Updated At'),
        ];
    }

    public function createTransaction(Transaction $transaction)
    {
        switch ($this->sender_id) {
            case self::ACCOUNT_RECIPIENT:
                $sender = $transaction->recipientAccount;
                break;
            case self::ACCOUNT_SENDER:
                $sender = $transaction->senderAccount;
                break;
            default:
                $sender = AccountExchange::findOne($this->sender_id);
        }
        switch ($this->recipient_id) {
            case self::ACCOUNT_RECIPIENT:
                $recipient = $transaction->recipientAccount;
                break;
            case self::ACCOUNT_SENDER:
                $recipient = $transaction->senderAccount;
                break;
            default:
                $recipient = AccountExchange::findOne($this->recipient_id);
        }
        $currencyCode = $recipient->currency->code;
        return $transaction = new Transaction([
            'user_id' => $transaction->user_id,
            'type' => $this->typeTransaction,
            'senderAccount' => $sender,
            'recipientAccount' => $recipient,
            'sum' => MathHelper::add(MathHelper::div(MathHelper::mul($transaction->sum, $this->percent, $currencyCode), 100, $currencyCode), $this->static, $currencyCode)
        ]);
    }

    /**
     * @return yii\db\ActiveQuery
     */
    public function getWalletGroup()
    {
        return $this->hasOne($this->module->modelMap['WalletGroup'], ['id' => 'wallet_group_id']);
    }

    /**
     * @return yii\db\ActiveQuery
     */
    public function getCurrency()
    {
        return $this->hasOne($this->module->modelMap['Currency'], ['id' => 'currency_id']);
    }

    /**
     * @return yii\db\ActiveQuery
     */
    public function getOwnerTypeTransaction()
    {
        return $this->hasOne($this->module->modelMap['TypeTransaction'], ['id' => 'owner_type_trn_id']);
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeTransaction()
    {
        return $this->hasOne($this->module->modelMap['TypeTransaction'], ['id' => 'type_trn_id']);
    }

    /**
     * @return yii\db\ActiveQuery
     */
    public function getSenderAccount()
    {
        return $this->hasOne($this->module->modelMap['AccountExchange'], ['id' => 'sender_id']);
    }

    /**
     * @return yii\db\ActiveQuery
     */
    public function getRecipientAccount()
    {
        return $this->hasOne($this->module->modelMap['AccountExchange'], ['id' => 'recipient_id']);
    }
}
