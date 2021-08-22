<?php

namespace asmbr\wallet\models;

use asmbr\math\MathHelper;
use yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\AttributeBehavior;
use asmbr\wallet\behaviors\InspectBehavior;
use yii\base\InvalidCallException;

/**
 * This is the model class for table "{{%wlt_clarity}}".
 *
 * @property integer $id
 * @property integer $opr_id
 * @property integer $fund_type_id
 * @property float $sum
 * @property string $inspected
 * @property boolean $dataIsValid
 *
 * @property Operation $operation
 * @property AccountFund $fund
 * @property TypeFund $fundType
 */
class Clarity extends BaseRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_clarity}}';
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
                    $currency = null;
                    if($model->operation->account) {
                        $currency = $model->operation->account->currency;
                    } else {
                        foreach ($model->operation->transaction->operations as $operation) {
                            if($operation->account) {
                                $currency = $operation->account->currency;
                                break;
                            }
                        }
                    }
                    if($currency === null)
                        throw new yii\db\Exception('Can not get currency');
                    $value = MathHelper::div($model->sum, $currency->storage_multiply, $currency->code);
                    $model->setOldAttribute('sum', $value);
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
                    return MathHelper::mul($model->sum, $model->operation->account->currency->storage_multiply, $model->operation->account->currency->code, false);
                },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['opr_id', 'fund_type_id', 'sum'], 'required'],
            [['opr_id', 'fund_type_id'], 'integer'],
            [['sum'], 'number'],
            [['inspected'], 'string', 'max' => 32],
            [['fund_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['TypeFund'], 'targetAttribute' => ['fund_type_id' => TypeFund::tableName().'.id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('wlt.models', 'ID'),
            'opr_id' => Yii::t('wlt.models', 'Opr ID'),
            'fund_type_id' => Yii::t('wlt.models', 'Fund Type ID'),
            'sum' => Yii::t('wlt.models', 'Sum'),
            'inspected' => Yii::t('wlt.models', 'Inspected'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->getIsNewRecord()) {
            return $this->insert($runValidation, $attributeNames);
        } else
            throw new InvalidCallException('The clarity can not be upgraded.');
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if(!parent::beforeSave($insert)) return false;
        if($this->operation->account && $this->fund === null) {
            $classAccountFund = $this->module->modelMap['AccountFund'];
            $fund = new $classAccountFund([
                'account_id' => $this->operation->account_id,
                'type_fund_id' => $this->fund_type_id,
                'balance' => 0
            ]);
            if(!$fund->save()) return false;
            $funds = array_merge($this->operation->account->funds, [$fund]);
            $funds = ArrayHelper::index($funds, 'type_fund_id');
            ksort($funds);
            $this->operation->account->populateRelation('funds', array_values($funds));
        }
        return true;
    }

    /**
     * @inheritdoc
    */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if($this->operation->account) {
            $this->fund->balance = MathHelper::add($this->fund->balance, $this->sum, $this->operation->account->currency->code);
            if(!$this->fund->save()) {
                throw new InvalidCallException('The fund was not updated.');
            }
        }
    }

    /**
     * @return TypeFund
    */
    public function getFund()
    {
        if(($account = $this->operation->account) !== null) {
            $funds = ArrayHelper::index($account->funds, ['type', 'id']);
            return empty($funds[$this->fund_type_id]) ? null : $funds[$this->fund_type_id];
        }
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne($this->module->modelMap['Operation'], ['id' => 'opr_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
    */
    public function getFundType()
    {
        return $this->hasOne($this->module->modelMap['TypeFund'], ['id' => 'fund_type_id']);
    }
    
    /**
     * @param Operation $value
     * @setOperation $value
    */
    public function setOperation(Operation $value)
    {
        $this->populateRelation('operation', $value);
        $this->opr_id = $value->id;
    }
}
