<?php

namespace asmbr\wallet\models;

use yii;
use yii\behaviors\TimestampBehavior;
use asmbr\wallet\behaviors\Configuration;
use asmbr\wallet\behaviors\InspectBehavior;

/**
 * This is the model class for table "{{%wlt_wallet}}".
 *
 * @property integer $id
 * @property integer $wallet_group_id
 * @property integer $entity_id
 * @property integer $created_at
 * @property string $inspected
 * @property bool $dataIsValid
 *
 * @property string $acceptableCurrency
 *
 * @property AccountExchange[] $accounts
 * @property AccountExchange[] $activeAccounts
 * @property WalletGroup $group
 * @property Config $configs
 * @method void redefine() Redefine all public property in this class
 */
class Wallet extends BaseRecord
{
    public $allNumberAccountsInWallet;

    public $allActiveAccountInWallet;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_wallet}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'updatedAtAttribute' => null
            ],
            'inspect' => [
                'class' => InspectBehavior::className(),
                'validateRelations' => ['group'],
                'salt' => $this->module->modelInspectSalt
            ],
            'configuration' => [
                'class' => Configuration::className(),
                'config' => function() {
                    return [$this->group, $this];
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
            [['wallet_group_id', 'entity_id'], 'required'],
            [['wallet_group_id', 'entity_id', 'created_at'], 'integer'],
            [['inspected'], 'string', 'max' => 32],
            [['wallet_group_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['WalletGroup'], 'targetAttribute' => ['wallet_group_id' => 'id']],
        ];
    }

    /**
     * @param Currency $currency
     * @return AccountExchange[]
     */
    public function getActiveAccounts(Currency $currency = null)
    {
        $result = [];
        foreach ($this->accounts as $acc) {
            if (empty($acc->deleted_at)) {
                if ($currency !== null && $acc->currency_id == $currency->id) {
                    $result[] = $acc;
                    continue;
                }
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('wlt.models', 'ID'),
            'wallet_group_id' => Yii::t('wlt.models', 'Wallet Group ID'),
            'entity_id' => Yii::t('wlt.models', 'Entity ID'),
            'created_at' => Yii::t('wlt.models', 'Created At'),
            'inspected' => Yii::t('wlt.models', 'Inspected'),
        ];
    }

    /**
     * @param WalletGroup $group
     */
    public function setGroup(WalletGroup $group)
    {
        $this->populateRelation('group', $group);
        $this->wallet_group_id = $group->id;
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccounts()
    {
        return $this->hasMany($this->module->modelMap['AccountExchange'], ['wallet_id' => 'id']);
    }

    /**
     * @param Currency $currency
     * @return AccountExchange[]
    */
    public function getAccountsByCurrency(Currency $currency)
    {
        /** @var $class AccountExchange */
        $class = $this->module->modelMap['AccountExchange'];
        if(!$this->isNewRecord)
            return $class::findOne(['wallet_id' => $this->id, 'currency_id' => $currency->id]);
        return [];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne($this->module->modelMap['WalletGroup'], ['id' => 'wallet_group_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigs()
    {
        return $this->hasMany($this->module->modelMap['Config'], ['id' => 'wallet_group_id']);
    }
}
