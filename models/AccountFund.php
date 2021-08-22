<?php

namespace asmbr\wallet\models;

use asmbr\math\MathHelper;
use yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use asmbr\wallet\behaviors\InspectBehavior;

/**
 * This is the model class for table "{{%wlt_account_fund}}".
 *
 * @property integer $id
 * @property integer $account_id
 * @property integer $type_fund_id
 * @property float $balance
 * @property integer $updated_at
 * @property integer $created_at
 * @property string $inspected
 *
 * @property AccountExchange $account
 * @property TypeFund $type
 */
class AccountFund extends BaseRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_account_fund}}';
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
                    $value = MathHelper::div($model->balance, $model->account->currency->storage_multiply, $model->account->currency->code);
                    $model->setOldAttribute('balance', $value);
                    return $value;
                },
            ],
            'inspect' => [
                'class' => InspectBehavior::className(),
                'salt' => $this->module->modelInspectSalt
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
            [['account_id', 'type_fund_id'], 'required'],
            [['account_id', 'type_fund_id', 'updated_at', 'created_at'], 'integer'],
            [['balance'], 'double'],
            [['balance'], 'number', 'min' => 0, 'when' => function($model) {/** @var self $model */ return !$model->type->negative_balance; }],
            [['inspected'], 'string', 'max' => 32],
            [['account_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['AccountExchange'], 'targetAttribute' => ['account_id' => 'id']],
            [['type_fund_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['TypeFund'], 'targetAttribute' => ['type_fund_id' => TypeFund::tableName().'.id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('wlt.models', 'ID'),
            'account_id' => Yii::t('wlt.models', 'Account ID'),
            'type_fund_id' => Yii::t('wlt.models', 'Type Fund ID'),
            'balance' => Yii::t('wlt.models', 'Balance'),
            'updated_at' => Yii::t('wlt.models', 'Updated At'),
            'created_at' => Yii::t('wlt.models', 'Created At'),
            'inspected' => Yii::t('wlt.models', 'Inspected'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne($this->module->modelMap['AccountExchange'], ['id' => 'account_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne($this->module->modelMap['TypeFund'], ['id' => 'type_fund_id']);
    }
}
