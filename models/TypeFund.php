<?php

namespace asmbr\wallet\models;

use yii;
use yii\db\ActiveQuery;
use yii\behaviors\TimestampBehavior;
use asmbr\wallet\behaviors\InspectBehavior;

/**
 * This is the model class for table "{{%type_fund}}".
 *
 * @property integer $id
 * @property string $code
 * @property string $name
 * @property string $description
 * @property boolean $negative_balance
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $inspected
 *
 * @property AccountFund[] $accountFunds
 * @property TypeFundPriority $priorityObject
 * @property integer $priority
 */
class TypeFund extends BaseRecord
{
    const DEFAULT_CODE = 'FT_BASIC';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_type_fund}}';
    }

    public static function find()
    {
        return new TypeFundQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timeStamp' => [
                'class' => TimestampBehavior::className()
            ],
            'inspect' => [
                'class' => InspectBehavior::className(),
                'salt' => $this->module->modelInspectSalt
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['code'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 256],
            [['description'], 'string', 'max' => 1024],
            [['negative_balance'], 'boolean'],
            [['inspected'], 'string', 'max' => 32],
            [['code'], 'unique'],
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
            'name' => Yii::t('wlt.models', 'Name'),
            'description' => Yii::t('wlt.models', 'Description'),
            'negative_balance' => Yii::t('wlt.models', 'Balance can be negative'),
            'created_at' => Yii::t('wlt.models', 'Created At'),
            'updated_at' => Yii::t('wlt.models', 'Updated At'),
            'inspected' => Yii::t('wlt.models', 'Inspected'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccountFunds()
    {
        return $this->hasMany($this->module->modelMap['AccountFund'], ['type_fund_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPriorityObject()
    {
        return $this->hasOne($this->module->modelMap['TypeFundPriority'], ['fund_type_id' => 'id']);
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priorityObject ? (int) $this->priorityObject->value : 0;
    }
}

class TypeFundQuery extends ActiveQuery
{
    /** @var TypeTransaction */
    public $trnType = null;

    public $returnDefault = true;

    protected $countFunds = null;

    public function init()
    {
        parent::init();
        $this->joinWith(['priorityObject' => function($joinQuery) {
            /** @var ActiveQuery $joinQuery */
            $trnTypeCondition = null;
            if($this->trnType !== null) {
                if($this->countFunds === null && $this->returnDefault) {
                    $this->countFunds = TypeFundPriority::find()->andWhere(['trn_type_id' => $this->trnType->id])->count();
                }
                if($this->countFunds || !$this->returnDefault) {
                    $trnTypeCondition = $this->trnType->id;
                }
            }
            $joinQuery->andOnCondition(['trn_type_id' => $trnTypeCondition]);
        }]);
        $this->select([TypeFund::tableName() . '.*', 'COALESCE(' . TypeFundPriority::tableName() . '.value, 0) as priority']);
        $this->orderBy('`priority` = 0, priority');
    }

    public function transactionType(TypeTransaction $type = null, $returnDefault = true)
    {
        $this->trnType = $type;
        $this->returnDefault = $returnDefault;
        return $this;
    }
}
