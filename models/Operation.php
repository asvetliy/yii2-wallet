<?php

namespace asmbr\wallet\models;

use asmbr\math\MathHelper;
use yii;
use yii\base\Event;
use yii\base\ModelEvent;
use yii\helpers\ArrayHelper;
use yii\behaviors\AttributeBehavior;
use asmbr\wallet\behaviors\InspectBehavior;
use yii\base\InvalidCallException;

/**
 * This is the model class for table "{{%wlt_operation}}".
 *
 * @property integer $id
 * @property integer $trn_id
 * @property integer $account_id
 * @property integer $sum
 * @property integer $balance
 * @property integer $delayed_at
 * @property integer $enrolled_at
 * @property string $inspected
 * @property boolean $dataIsValid
 * @property boolean $isApplied
 *
 * @property boolean $isSenderOperation
 * @property boolean $isRecipientOperation
 *
 * @property AccountExchange $account
 * @property AccountFund[] $accountOrderedFunds
 * @property Transaction $transaction
 * @property TypeFund[] $fundTypes
 * @property Clarity[] $clarity
 *
 * @method void updateSignatureOfData()
 */
class Operation extends BaseRecord
{
    const EVENT_DENY_APPLY = 'denyApply';
    const EVENT_BEFORE_APPLY = 'beforeApply';
    const EVENT_AFTER_APPLY = 'afterApply';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_operation}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'sum_from_storage' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    self::EVENT_AFTER_INSERT => 'sum',
                    self::EVENT_AFTER_UPDATE => 'sum',
                    self::EVENT_AFTER_FIND => 'sum',
                    self::EVENT_AFTER_REFRESH => 'sum'
                ],
                'value' => function ($event) {
                    /** @var self $model */
                    $model = $event->sender;
                    $value = $model->sum;
                    if($model->account) {
                        $value = MathHelper::div($value, $model->account->currency->storage_multiply, $model->account->currency->code);
                    }
                    $model->setOldAttribute('sum' , $value);
                    return $value;
                },
            ],
            'balance_from_storage' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    self::EVENT_AFTER_INSERT => 'balance',
                    self::EVENT_AFTER_UPDATE => 'balance',
                    self::EVENT_AFTER_FIND => 'balance',
                    self::EVENT_AFTER_REFRESH => 'balance'
                ],
                'value' => function ($event) {
                    /** @var self $model */
                    $model = $event->sender;
                    $value = $model->balance;
                    if($model->account) {
                        $value = MathHelper::div($value, $model->account->currency->storage_multiply, $model->account->currency->code);
                    }
                    $model->setOldAttribute('balance' , $value);
                    return $value;
                },
            ],
            'inspect' => [
                'class' => InspectBehavior::className(),
                'salt' => $this->module->modelInspectSalt
            ],
            'sum_to_storage' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => 'sum',
                    self::EVENT_BEFORE_UPDATE => 'sum',
                ],
                'value' => function ($event) {
                    /** @var self $model */
                    $model = $event->sender;
                    return MathHelper::mul($model->sum, $model->account->currency->storage_multiply, $model->account->currency->code, false);
                },
            ],
            'balance_to_storage' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => 'balance',
                    self::EVENT_BEFORE_UPDATE => 'balance',
                ],
                'value' => function ($event) {
                    /** @var self $model */
                    $model = $event->sender;
                    return MathHelper::mul($model->balance, $model->account->currency->storage_multiply, $model->account->currency->code, false);
                },
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['trn_id', 'sum'], 'required'],
            ['sum', 'compare', 'compareValue' => 0, 'operator' => '!=='],
            ['enrolled_at', 'compare', 'compareValue' => null, 'operator' => '==='],
            ['account_id', 'required', 'when' => function($model) { /** @var self $model */ return $model->isRecipientOperation || ($model->isSenderOperation && $model->transaction->type->required_sender); }],
            [['trn_id', 'account_id', 'delayed_at', 'enrolled_at'], 'integer'],
            [['sum', 'balance'], 'double'],
            [['inspected'], 'string', 'max' => 32],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('wlt.models', 'ID'),
            'trn_id' => Yii::t('wlt.models', 'Trn ID'),
            'account_id' => Yii::t('wlt.models', 'Account ID'),
            'sum' => Yii::t('wlt.models', 'Sum'),
            'balance' => Yii::t('wlt.models', 'Balance'),
            'delayed_at' => Yii::t('wlt.models', 'Delayed At'),
            'enrolled_at' => Yii::t('wlt.models', 'Enrolled At'),
            'inspected' => Yii::t('wlt.models', 'Inspected'),
        ];
    }

    public function apply()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $this->applyInternal();
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->denyApply();
            $this->addError('inspected', $e->getMessage());
            Yii::error($e->getMessage());
            return false;
        }
    }

    protected function applyInternal()
    {
        if(!$this->beforeApply())
            throw new InvalidCallException();

        if($this->enrolled_at !== null)
            throw new InvalidCallException('The operation has already been applied.');

        if((int) $this->delayed_at > time())
            throw new InvalidCallException('The operation cannot be used because of the delay.');

        if($this->account) {
            $this->account->refresh();
            if(!$this->account->validate())
                throw new InvalidCallException('Account is not valid');
        }

        $fundTypesEnabled = $this->transaction->type->fundTypesEnabled;
        if(!count($fundTypesEnabled))
            throw new InvalidCallException('In this type transaction does not enabled any type of funds.');

        /** @var $clarityClass Clarity */
        $clarityClass = $this->module->modelMap['Clarity'];
        /** @var Clarity[] $clarity*/
        $clarity = [];

        $sum = 0;
        if($this->transaction->senderOperation) {
            if($this->transaction->recipientOperation->id === $this->id && $this->transaction->senderOperation !== null && $this->transaction->senderOperation->enrolled_at === null)
                throw new InvalidCallException('Deposit operation cannot be performed before withdrawal operation.');

            if($this->account_id !== null && array_sum(ArrayHelper::getColumn($this->getAccountOrderedFunds(), 'balance')) + $this->sum < 0)
                throw new InvalidCallException('It is impossible to apply the operation. Insufficient funds.');

            if($this->isSenderOperation) {
                $sum = $this->sum;
                foreach ($this->getAccountOrderedFunds() as $fund) {
                    if(empty($sum)) break;
                    if($fund->balance + $sum >= 0) {
                        $tmpSum = $sum;
                    } else {
                        $tmpSum = -$fund->balance;
                    }
                    $sum -= $tmpSum;
                    if(!empty($tmpSum))
                        $clarity[] = new $clarityClass(['operation' => $this, 'fund_type_id' => $fund->type->id, 'sum' => $tmpSum]);
                }
            } elseif($this->isRecipientOperation) {
                foreach ($this->transaction->senderOperation->clarity as $clr) {
                    $clarity[] = new $clarityClass(['operation' => $this, 'fund_type_id' => $clr->fundType->id, 'sum' => -$clr->sum]);
                }
            }
        } else {
            if($this->sum > 0){
                if($this->fundTypes) {
                    $fundType = $this->fundTypes[0];
                } else {
                    $fundTypesEnabled = $this->transaction->type->fundTypesEnabled;
                    $fundType = $fundTypesEnabled[0];
                }
                $clarity[] = new $clarityClass(['operation' => $this, 'fund_type_id' => $fundType->id, 'sum' => $this->sum]);
            }elseif($this->sum < 0) {
                $sum = $this->sum;
                foreach ($this->getAccountOrderedFunds() as $fund) {
                    if(empty($sum)) break;
                    if($fund->balance + $sum >= 0 || $fund->type->negative_balance) {
                        $tmpSum = $sum;
                    } else {
                        $tmpSum = -$fund->balance;
                    }

                    $sum -= $tmpSum;
                    if($tmpSum) {
                        $clarity[] = new $clarityClass(['operation' => $this, 'fund_type_id' => $fund->type->id, 'sum' => $tmpSum]);
                    }
                }
            }
        }

        if(empty($clarity) || (float) $sum != 0) {
            throw new InvalidCallException('The clarity were not generated.');
        }

        foreach ($clarity as $clr) {
            $clr->on($clr::EVENT_AFTER_VALIDATE, function($event) use($fundTypesEnabled) {
                /** @var Clarity $model */
                $model = $event->sender;
                if($this->account_id !== null && !in_array($model->fundType->id, ArrayHelper::getColumn($fundTypesEnabled, 'id')))
                    $model->addError('fundType', "Сan not make a deal with the fund type '{$model->fundType->name}'.");
            });
            if(!$clr->save())
                throw new InvalidCallException('Clarity does not save. Errors : '.json_encode($clr->getErrors()));
        }

        $this->populateRelation('clarity', $clarity);

        $this->balance = MathHelper::array_add(ArrayHelper::getColumn($this->getAccountOrderedFunds(), 'balance'), $this->account->currency->code);
        $this->enrolled_at = time();
        $this->updateSignatureOfData();
        /** @var AttributeBehavior $balanceToStorageBehavior */
        $balanceToStorageBehavior = $this->getBehavior('balance_to_storage');
        $balanceToStorageBehavior->evaluateAttributes(new Event(['name' => self::EVENT_BEFORE_UPDATE, 'sender' => $this]));
        $this->updateAttributes([
            'enrolled_at' => $this->enrolled_at,
            'balance' => $this->balance
        ]);

        $this->afterApply();

    }

    public function denyApply()
    {
        $this->trigger(self::EVENT_DENY_APPLY, new Event);
    }

    public function beforeApply()
    {
        $event = new ModelEvent;
        $this->trigger(self::EVENT_BEFORE_APPLY, $event);

        return $event->isValid;
    }

    public function afterApply()
    {
        $this->trigger(self::EVENT_AFTER_APPLY, new Event);
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->getIsNewRecord()) {
            return $this->insert($runValidation, $attributeNames);
        } else
            throw new InvalidCallException('The transaction can not be upgraded.');
    }

    public function getIsApplied()
    {
        return (boolean) $this->enrolled_at;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if($this->isSenderOperation && $this->account_id === null && $this->enrolled_at === null) {
            $this->apply();
        }
    }

    public function getIsSenderOperation()
    {
        return $this->transaction->senderOperation ? $this->transaction->senderOperation->equals($this) : false;
    }

    public function getIsRecipientOperation()
    {
        return $this->transaction->recipientOperation->equals($this);
    }

    /**
     * @param $value
     * @throws \TypeError
     */
    public function setAccount($value)
    {
        if(!$value instanceof AccountExchange)
            throw new \TypeError('Argument 1 passed to ' . self::className() . '::setAccount() must be an instance of ' . AccountExchange::className() . '.');
        if(!$this->getIsNewRecord())
            throw new InvalidCallException("Assignment transaction is not allowed.");
        $this->populateRelation('account', $value);
        $this->account_id = $value ? $value->id : null;
    }    
    
    public function setTransaction(Transaction $value)
    {
        if(!$this->getIsNewRecord())
            throw new InvalidCallException("Assignment transaction is not allowed.");
        $this->populateRelation('transaction', $value);
        $this->trn_id = $value->id;
    }

    /**
     * @param $value
     * @throws \TypeError
     */
    public function setFundTypes($value)
    {
        if($value !== null) {
            if(!is_array($value)) $value = [$value];
            foreach ($value as $val) {
                if(!$val instanceof TypeFund)
                    throw new \TypeError('Argument 1 passed to ' . self::className() . '::setFundTypes() must be an instance of ' . TypeFund::className() . '.');
            }
        }
        $this->populateRelation('fundTypes', $value);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransaction()
    {
        return $this->hasOne($this->module->modelMap['Transaction'], ['id' => 'trn_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne($this->module->modelMap['AccountExchange'], ['id' => 'account_id']);
    }

    /**
     * @return AccountFund[]
     */
    public function getAccountOrderedFunds()
    {
        $fundClass = $this->module->modelMap['AccountFund'];
        $accountFunds = ArrayHelper::index($this->account->funds, ['type', 'id']);
        $orderedAccountFunds = [];
        foreach($this->transaction->type->fundTypesEnabled as $fundType) {
            if(isset($accountFunds[$fundType->id])) {
                $orderedAccountFunds[] = $accountFunds[$fundType->id];
            } else {
                $orderedAccountFunds[] = new $fundClass(['account_id' => $this->account->id, 'type_fund_id' => $fundType->id, 'balance' => 0]);
            }
        }
        return $orderedAccountFunds;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClarity()
    {
        return $this->hasMany($this->module->modelMap['Clarity'], ['opr_id' => 'id']);
    }

    /**
     * @return TypeFund|null
     */
    public function getFundTypes()
    {
        if(empty($this->fundTypes))
            return null;
        return $this->fundTypes;
    }
}
