<?php

namespace asmbr\wallet\models;

use asmbr\math\MathHelper;
use yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;
use asmbr\wallet\behaviors\InspectBehavior;
use asmbr\wallet\behaviors\Configuration;

/**
 * This is the model class for table "{{%wlt_account_exchange}}".
 *
 * @property integer $id
 * @property integer $wallet_id
 * @property integer $currency_id
 * @property string $code
 * @property integer $created_at
 * @property integer $deleted_at
 * @property string $inspected
 * @property bool $dataIsValid;
 * @property float $balance
 * @property Currency $currency
 * @property Wallet $wallet
 * @property AccountFund[] $funds
 * @property AccountFund[] $fundsDisabled
 * @property Operation[] $recipientOperations
 * @property Operation[] $senderOperations
 * @property Transaction[] $transactions
 * @method void redefine() Redefine all public property in this class
 */
class AccountExchange extends BaseRecord
{
    public $numberActiveAccount;

    public $minimumBalanceAccount;
    
    public $maximumBalanceAccount;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_account_exchange}}';
    }

    /**
     * @inheritdoc
    */
    public function behaviors()
    {
        return [
            'timeStamp' => [
                'class' => TimestampBehavior::className(),
                'updatedAtAttribute' => null
            ],
            'inspect' => [
                'class' => InspectBehavior::className(),
                'salt' => $this->module->modelInspectSalt
            ],
            'configuration' => [
                'class' => Configuration::className(),
                'config' => function() {
                    return [$this->wallet->group, $this->currency, $this->wallet];
                },
                'eventInit' => self::EVENT_BEFORE_VALIDATE
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code'], 'default', 'value' => function() { return $this->generateCode(); }],
            [['wallet_id', 'currency_id', 'code'], 'required'],
            [['wallet_id', 'currency_id', 'created_at'], 'integer'],
            [['currency_id'], 'validatorLimitCreation', 'when' => function() { return $this->isNewRecord; }],
            [['code'], 'string', 'max' => 64],
            [['inspected'], 'string', 'max' => 32],
            [['code'], 'unique'],
            [['currency_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['Currency'], 'targetAttribute' => ['currency_id' => 'id']],
            [['wallet_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['Wallet'], 'targetAttribute' => ['wallet_id' => 'id']],
            [['wallet_id', 'currency_id'], 'readOnlyValidator', 'when' => function() { return !$this->isNewRecord; }],
            [['deleted_at'], function($attribute){
                if(!empty($this->getOldAttribute('deleted_at')))
                    $this->addError($attribute, Yii::t('wlt.models', 'Account deleted'));
            }]
        ];
    }

    protected function generateCode()
    {
        if($this->currency_id)
            return $this->currency->code . time() . $this->wallet_id;
        else
            return (string) time() . $this->wallet_id;
    }

    public function validatorLimitCreation()
    {
        if ($this->wallet) {
            $this->wallet->redefine();

            if ($this->wallet->allNumberAccountsInWallet !== null) {
                $count = count($this->wallet->accounts);
                if ($count >= $this->wallet->allNumberAccountsInWallet)
                    $this->addError('currency_id', Yii::t('wlt.model', 'Can`t create account, limit accounts is exceeded, your limit is {count}', [
                        'count' => $this->wallet->allNumberAccountsInWallet
                    ]));
            }

            if ($this->wallet->allActiveAccountInWallet !== null) {
                $count = count($this->wallet->activeAccounts);
                if ($count >= $this->wallet->allActiveAccountInWallet)
                    $this->addError('currency_id', Yii::t('wlt.model', 'Can`t create account, limit active accounts is exceeded, your limit is {count}', [
                        'count' => $this->wallet->allActiveAccountInWallet
                    ]));
            }


            if ($this->numberActiveAccount !== null) {
                $count = count($this->wallet->getActiveAccounts($this->currency));
                if ($count >= $this->numberActiveAccount)
                    $this->addError('currency_id', Yii::t('wlt.model', 'Can`t create account.  Limited active accounts in {currency} currency', [
                        'currency' => $this->currency->code
                    ]));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('wlt.models', 'ID'),
            'wallet_id' => Yii::t('wlt.models', 'Wallet ID'),
            'currency_id' => Yii::t('wlt.models', 'Currency ID'),
            'code' => Yii::t('wlt.models', 'Code'),
            'created_at' => Yii::t('wlt.models', 'Created At'),
            'inspected' => Yii::t('wlt.models', 'Inspected'),
        ];
    }

    public function getBalance()
    {
        return MathHelper::array_add(ArrayHelper::getColumn($this->funds, 'balance'), $this->currency->code);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurrency()
    {
        return $this->hasOne($this->module->modelMap['Currency'], ['id' => 'currency_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWallet()
    {
        return $this->hasOne($this->module->modelMap['Wallet'], ['id' => 'wallet_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFunds()
    {
        return $this->hasMany($this->module->modelMap['AccountFund'], ['account_id' => 'id'])->with('type');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRecipientOperations()
    {
        return $this->hasMany($this->module->modelMap['Operation'], ['account_id' => 'id'])->andFilterWhere(['>', 'sum', 0]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSenderOperations()
    {
        return $this->hasMany($this->module->modelMap['Operation'], ['account_id' => 'id'])->andFilterWhere(['<', 'sum', 0]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransactions()
    {
        /** @var Transaction $class */
        $class = $this->module->modelMap['Transaction'];
        $query = $class::find()->where(['or', ['sender_id' => $this->id], ['recipient_id' => $this->id]]);
        $query->multiple = true;
        return $query;
    }

    /**
     * @inheritdoc
    */
    public function delete()
    {
        $this->deleted_at = time();
        return $this->save();
    }
}
