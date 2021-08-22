<?php

namespace asmbr\wallet\models;

use yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%wlt_type_transaction}}".
 *
 * @property integer $id
 * @property integer $parent_id
 * @property string $code
 * @property string $name
 * @property string $description
 * @property boolean $required_sender
 * @property boolean $enabled
 * @property boolean $can_withdraw
 * @property integer $depth
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property Transaction[] $transactions
 * @property TypeFund[] $fundTypes
 * @property TypeFund[] $fundTypesEnabled
 */
class TypeTransaction extends BaseRecord
{
    const DEFAULT_CODE = 'TT_TRANSFER';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_type_transaction}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timeStamp' => [
                'class' => TimestampBehavior::className()
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
            [['parent_id', 'required_sender', 'enabled', 'depth', 'can_withdraw'], 'integer'],
            [['code'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 256],
            [['description'], 'string', 'max' => 1024],
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
            'parent_id' => Yii::t('wlt.models', 'Parent ID'),
            'code' => Yii::t('wlt.models', 'Code'),
            'name' => Yii::t('wlt.models', 'Name'),
            'description' => Yii::t('wlt.models', 'Description'),
            'required_sender' => Yii::t('wlt.models', 'Required Sender'),
            'can_withdraw' => Yii::t('wlt.models', 'Can Withdraw'),
            'enabled' => Yii::t('wlt.models', 'Enabled'),
            'depth' => Yii::t('wlt.models', 'Depth'),
            'created_at' => Yii::t('wlt.models', 'Created At'),
            'updated_at' => Yii::t('wlt.models', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransactions()
    {
        return $this->hasMany($this->module->modelMap['Transaction'], ['type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfig()
    {
        return $this->hasMany($this->module->modelMap['ConfigGroup'], ['operation_type_id' => 'id']);
    }

    public function getFundTypesEnabled()
    {
        $result = [];
        foreach ($this->fundTypes as $type) {
            if($type->priority > 0) $result[] = $type;
        }
        return $result;
    }

    public function getFundTypes()
    {
        /** @var TypeFund $className */
        $className = $this->module->modelMap['TypeFund'];
        /** @var TypeFundQuery $query */
        $query = $className::find();
        $query->transactionType($this);
        $query->link = [];
        $query->multiple = true;
        return $query;
    }
}
