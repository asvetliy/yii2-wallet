<?php

namespace asmbr\wallet\models;

use asmbr\math\MathHelper;
use yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use asmbr\wallet\behaviors\DependentTransactionBehavior;
use asmbr\wallet\behaviors\InspectBehavior;
use asmbr\wallet\behaviors\Configuration;
use yii\base\InvalidCallException;
use yii\base\Exception;

/**
 * This is the model class for table "{{%wlt_transaction}}".
 *
 * @property integer $id
 * @property integer $pid
 * @property string $code
 * @property integer $user_id
 * @property integer $sender_id
 * @property integer $recipient_id
 * @property float $sum
 * @property integer $type_id
 * @property string $description
 * @property integer $created_at
 * @property string $inspected
 * @property bool $dataIsValid
 *
 * @property $user
 * @property AccountExchange $senderAccount
 * @property AccountExchange $recipientAccount
 * @property Operation $senderOperation
 * @property Operation $recipientOperation
 * @property Operation[] $operations
 * @property TypeTransaction $type
 * @property Currency $currency
 * @property integer $enrollDate
 *
 * @method redefine()
 */
class Transaction extends BaseRecord
{
    const EVENT_PREPARE_PROCESS = 'prepareProcess';

    public $minimumBalanceTransaction;

    public $maximumBalanceTransaction;

    public $countSenderTransactionPerDay;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_transaction}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = [
            'timeStamp' => [
                'class' => TimestampBehavior::className(),
                'updatedAtAttribute' => null
            ],
            'inspect' => [
                'class' => InspectBehavior::className(),
                //'validateRelations' => ['senderAccount', 'recipientAccount'],
                'salt' => $this->module->modelInspectSalt
            ],
            'configuration' => [
                'class' => Configuration::className(),
                'config' => function() {
                    if($this->senderAccount && $this->type){
                        return [
                            $this->senderAccount->wallet,
                            $this->senderAccount->wallet->group,
                            $this->senderAccount->currency,
                            $this->type];
                    }
                    return [];
                },
            ]
        ];
        
        if($this->module->dependentTransaction)
            $behaviors['dependencies'] = [
                'class' => DependentTransactionBehavior::className()
            ];
        
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['user_id', 'default', 'value' => function() {
                if (Yii::$app instanceof yii\console\Application) {
                    return 0;
                } else {
                    return (int) Yii::$app->user->id;
                }
            }],
            ['type_id', 'default', 'value' => function() {
                /** @var TypeTransaction $class */
                $class = $this->module->modelMap['TypeTransaction'];
                $this->type = $class::findOne(['code' => $class::DEFAULT_CODE]);
                return $this->type->id;
            }],
            ['code', 'default', 'value' => $this->generateCode()],
            [['code', 'user_id', 'sum', 'recipient_id', 'type_id'], 'required'],
            ['sum', 'double'],
            ['sum', 'validatorLimitSum'],
            ['sum', 'validatorCountTransactionPerDay'],
            ['sum', 'validatorLimitBalanceAccount'],
            ['sum', 'compare', 'operator' => '>', 'compareValue' => 0, 'when' => function($model) {/** @var self $model */ return !$model->type->can_withdraw;}],
            [['sender_id'], 'required', 'enableClientValidation' => false, 'when' => function($model) {/** @var self $model */ return $model->type->required_sender;}],
            [['user_id', 'sender_id', 'recipient_id', 'type_id', 'created_at'], 'integer'],
            [['code'], 'string', 'max' => 64],
            [['code'], 'unique'],
            [['description'], 'string', 'max' => 256],
            [['inspected'], 'string', 'max' => 32],
            [['sender_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['AccountExchange'], 'targetAttribute' => ['sender_id' => 'id']],
            [['recipient_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['AccountExchange'], 'targetAttribute' => ['recipient_id' => 'id']],
            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['TypeTransaction'], 'targetAttribute' => ['type_id' => 'id']],
            [['type_id'], function($attribute) {
                if(!$this->type->enabled) 
                    $this->addError($attribute, Yii::t('wlt.models', "Transaction type '{name}' is disabled.", ['name' => $this->type->name]));
            }],
            [['recipient_id'], function($attribute) {
                if($this->senderAccount && $this->senderAccount->currency_id !== $this->recipientAccount->currency_id)
                    $this->addError($attribute, Yii::t('wlt.models', 'Currency sender and recipient must match.'));
            }]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('wlt.models', 'ID'),
            'code' => Yii::t('wlt.models', 'Code'),
            'user_id' => Yii::t('wlt.models', 'User ID'),
            'sender_id' => Yii::t('wlt.models', 'Sender ID'),
            'recipient_id' => Yii::t('wlt.models', 'Recipient ID'),
            'type_id' => Yii::t('wlt.models', 'Type ID'),
            'description' => Yii::t('wlt.models', 'Description'),
            'created_at' => Yii::t('wlt.models', 'Created At'),
            'inspected' => Yii::t('wlt.models', 'Inspected'),
        ];
    }

    public function validatorLimitSum($attribute)
    {
        $this->redefine();
        if($this->minimumBalanceTransaction !== null)
            if($this->sum < $this->minimumBalanceTransaction)
                $this->addError($attribute, Yii::t('wlt.models', 'Minimum limit transaction {sum}', ['sum' => $this->minimumBalanceTransaction]));

        if($this->maximumBalanceTransaction !== null)
            if($this->sum > $this->maximumBalanceTransaction)
                $this->addError($attribute, Yii::t('wlt.models', 'Maximum limit transaction {sum}', ['sum' => $this->maximumBalanceTransaction]));
    }

    public function validatorCountTransactionPerDay($attribute)
    {
        $this->redefine();
        if($this->countSenderTransactionPerDay !== null){
            $count = self::getCountSenderOperationPerDay($this->senderAccount);
            if(!empty($count) && $this->countSenderTransactionPerDay <= $count)
                $this->addError($attribute, Yii::t('wlt.model', 'Limit transactions on this day is over, maximum {number}', ['number' => $this->countSenderTransactionPerDay]));
        }
    }

    public function validatorLimitBalanceAccount($attribute)
    {
        $this->redefine();
        if($this->senderAccount)
        {
            $balance = $this->senderAccount->balance - $this->sum;
            if($this->senderAccount->minimumBalanceAccount && $balance < $this->senderAccount->minimumBalanceAccount)
                $this->addError($attribute, Yii::t('wlt.model', 'Limit sender account balance is over, minimum {sum}', ['sum' => $this->senderAccount->minimumBalanceAccount]));
        }

        $balance = $this->recipientAccount->balance + $this->sum;
        if($this->recipientAccount->maximumBalanceAccount && $balance > $this->recipientAccount->maximumBalanceAccount)
            $this->addError($attribute, Yii::t('wlt.model', 'Limit recipient account is over, maximum {sum}', ['sum' => $this->recipientAccount->maximumBalanceAccount]));
    }

    public function __construct(array $config = [])
    {
        if(isset($config['sum'])) {
            $config['sum'] = ArrayHelper::remove($config, 'sum');
            if(isset($config['delay'])) {
                $config['delay'] = ArrayHelper::remove($config, 'delay');
            }
        }

        parent::__construct($config);
    }

    protected function generateCode()
    {
        return (microtime(true)*10000).rand(1000, 9999);
    }

    public function prepareProcess()
    {
        $process = new Process();
        $process->pushTransactions($this->prepareProcessArray());
        return $process;
    }
    
    public function prepareProcessArray()
    {
        $transactions = [$this];

        $this->validate(['type']);

        $event = new TransactionEvent();
        $this->trigger(self::EVENT_PREPARE_PROCESS, $event);
        $transactions = array_merge($transactions, $event->result);
        
        return $transactions;
    }

    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_INSERT
        ];
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->getIsNewRecord()) {
            return $this->insert($runValidation, $attributeNames);
        } else
            throw new InvalidCallException('The transaction can not be upgraded.');
    }

    public function afterSave($insert, $changedAttributes)
    {
        if($this->senderOperation) {
            $this->senderOperation->transaction = $this;
            if(!$this->senderOperation->save())
                throw new Exception('Sender Operation failed validation.');
        }
                
        $this->recipientOperation->transaction = $this;
        if(!$this->recipientOperation->save())
            throw new Exception('Recipient Operation failed validation.');

        if(!$this->pid)
            $this->updateAttributes(['pid' => $this->id]);
        
        parent::afterSave($insert, $changedAttributes);
    }

    public function apply($applySenderOperation = true, $applyRecipientOperation = true)
    {
        $valid = true;
        if($this->isNewRecord) $valid = $this->save();
        if($valid) {
            if($this->senderOperation && $applySenderOperation && $this->senderOperation->enrolled_at === null) $valid = $valid && $this->senderOperation->apply();
            if($applyRecipientOperation && $this->recipientOperation->enrolled_at === null) $valid = $valid && $this->recipientOperation->apply();
        }
        return $valid;
    }

    //region SETTERS

    /**
     * @param null|AccountExchange $value
     * @throws \TypeError
     */
    public function setSenderAccount($value)
    {
        if(!$value instanceof AccountExchange)
            throw new \TypeError('Argument 1 passed to ' . self::className() . '::setSenderAccount() must be an instance of ' . AccountExchange::className() . '.');
        $this->populateRelation('senderAccount', $value);
        $this->sender_id = $value ? $value->id : null;
    }

    /**
     * @param null|AccountExchange $value
     * @throws \TypeError
     */
    public function setRecipientAccount($value)
    {
        if(!$value instanceof AccountExchange)
            throw new \TypeError('Argument 1 passed to ' . self::className() . '::setRecipientAccount() must be an instance of ' . AccountExchange::className() . '.');
        $this->populateRelation('recipientAccount', $value);
        $this->recipient_id = $value ? $value->id : null;
    }

    public function setType(TypeTransaction $value)
    {
        $this->populateRelation('type', $value);
        $this->type_id = $value->id;
    }

    /**
     * @param float|array $value
    */
    public function setSum($value)
    {
        if($this->recipientAccount) {
            if (!is_array($value)) {
                $value = [
                    MathHelper::format($value, $this->recipientAccount->currency->code),
                    null
                ];
            }
            list($sum, $fundTypes) = $value;
            /** @var Operation $class */
            $class = $this->module->modelMap['Operation'];
            if($this->senderAccount) {
                $operations = [
                    new $class(['account' => $this->senderAccount, 'sum' => -$sum]),
                    new $class(['account' => $this->recipientAccount, 'sum' => $sum])
                ];
            }else{
                $operations = [
                    new $class(['account' => $this->recipientAccount, 'sum' => $sum, 'fundTypes' => $fundTypes])
                ];
            }

            $this->populateRelation('operations', $operations);
        }
    }

    /**
     * @param int $value
     */
    public function setDelay($value)
    {
        if($this->recipientOperation) {
            $this->recipientOperation->delayed_at = time() + (int) $value;
        } else
            throw new InvalidCallException('Need specify sum.');
    }

    //endregion

    //region GETTERS & RELATIONS

    /**
     * @return float
     */
    public function getSum()
    {
        if ($this->recipientOperation) {
            return MathHelper::format($this->recipientOperation->sum, $this->recipientOperation->account->currency->code);
        }
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne($this->module->modelMap['User'], ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSenderAccount()
    {
        return $this->hasOne($this->module->modelMap['AccountExchange'], ['id' => 'sender_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSenderOperation()
    {
        if($this->operations) {
            if(count($this->operations) > 1) {
                if($this->operations[0]->account_id == $this->sender_id)
                    return $this->operations[0];
            }
        }
        return null;

    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRecipientAccount()
    {
        return $this->hasOne($this->module->modelMap['AccountExchange'], ['id' => 'recipient_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRecipientOperation()
    {
        if($this->operations){
            if(count($this->operations) > 1){
                if($this->operations[1]->account_id == $this->recipient_id)
                    return $this->operations[1];
            }else{
                if($this->operations[0]->account_id == $this->recipient_id)
                    return $this->operations[0];
            }
        }
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperations()
    {
        $operationClass = $this->module->modelMap['Operation'];
        return $this->hasMany($operationClass, ['trn_id' => 'id'])->orderBy([$operationClass::tableName().'.id' => SORT_ASC])->inverseOf('transaction');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne($this->module->modelMap['TypeTransaction'], ['id' => 'type_id']);
    }

    /**
     * @param AccountExchange $account
     * @return int
     */
    public static function getCountSenderOperationPerDay(AccountExchange $account)
    {
        if($account->isNewRecord)
            return null;

        $time = time();
        return $account->getSenderOperations()->joinWith(['transaction'])
            ->andFilterWhere(['>=', 'created_at', $time - ($time%(86400))])
            ->count();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProcess()
    {
        return $this->hasMany($this->module->modelMap['Transaction'], ['pid' => 'pid']);
    }

    /**
     * @return integer
    */
    public function getEnrollDate()
    {
        return $this->recipientOperation ? $this->recipientOperation->enrolled_at : null;
    }
    
    //endregion
}
