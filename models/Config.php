<?php

namespace asmbr\wallet\models;

use yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\Inflector;

/**
 * This is the model class for table "{{%wlt_config}}".
 *
 * @property integer $id
 * @property integer $wallet_group_id
 * @property integer $wallet_id
 * @property integer $currency_id
 * @property integer $type_transaction_id
 * @property string $attribute
 * @property string $value
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property WalletGroup $walletGroup
 * @property Wallet $wallet
 * @property Currency $currency
 * @property TypeTransaction $typeTransaction
 */
class Config extends BaseRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_config}}';
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
     * Create customize query for db
     *
     * @param WalletGroup $group
     * @param Currency $currency
     * @param TypeTransaction $type
     * @param Wallet $wallet
     * @return Config[]
     */
    public static function get(WalletGroup $group, Currency $currency = null, TypeTransaction $type = null, Wallet $wallet = null)
    {
        $query = self::find();

        if ($wallet !== null) {
            $query->andWhere(['or', [self::tableName() . '.wallet_group_id' => $group->id], [self::tableName() . '.wallet_group_id' => null]]);
            $query->andWhere(['or', [self::tableName() . '.wallet_id' => $wallet->id], [self::tableName() . '.wallet_id' => null]]);
        } else {
            $query->andWhere(['wallet_id' => null]);
            $query->andWhere(['wallet_group_id' => $group->id]);
        }

        if ($currency !== null)
            $query->andWhere(['or', [self::tableName() . '.currency_id' => $currency->id], [self::tableName() . '.currency_id' => null]]);
        else
            $query->andWhere(['currency_id' => null]);

        if ($type !== null)
            $query->andWhere(['or', ['type_transaction_id' => $type->id], ['type_transaction_id' => null]]);
        else
            $query->andWhere(['type_transaction_id' => null]);

        $query->orderBy([
            self::tableName() . '.wallet_id' => SORT_ASC,
            self::tableName() . '.currency_id' => SORT_ASC,
            self::tableName() . '.type_transaction_id' => SORT_ASC
        ]);

        /** @var $results Config[] */
        $results = $query->all();

        /**
         * Add populate relation in received objects
         */
        foreach ($results as $index => $r) {
            $results[$index]->populateRelation('walletGroup', $group);
            if ($currency !== null)
                $results[$index]->populateRelation('currency', $currency);
            if ($type !== null)
                $results[$index]->populateRelation('typeOperation', $type);
            if ($wallet !== null)
                $results[$index]->populateRelation('wallet', $wallet);
        }

        return $results;
    }

    /**
     * Array params for all available attributes for set in modules
     *
     * @return array
     */
    public function getAttributesList()
    {
        $result = [];
        foreach ($this->getReflectedClass() as $key => $attributes) {
            /** @var $class_name yii\db\ActiveRecord */
            $reflect = new \ReflectionClass($attributes['class']);
            $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

            $getAttr = true;
            foreach ($attributes['dependence'] as $element => $isset) {
                $attr = $this->getAttribute($element);
                if (!empty($attr) != $isset) {
                    $getAttr = false;
                    break;
                }
            }
            if ($getAttr)
                foreach ($properties as $prop) {
                    $result[$key . '::' . $prop->name] = $key . ': ' . Inflector::camel2words($prop->name);
                }
        }
        return $result;
    }

    /**
     * @return array
     */
    protected function getReflectedClass()
    {
        $exAttr = $this->wallet_id ? 'wallet_id' : 'wallet_group_id';
        return [
            'Wallet' => [
                'class' => $this->module->modelMap['Wallet'],
                'dependence' => [$exAttr => true, 'currency_id' => false, 'type_transaction_id' => false],
            ],
            'AccountExchange' => [
                'class' => $this->module->modelMap['AccountExchange'],
                'dependence' => [$exAttr => true, 'currency_id' => true, 'type_transaction_id' => false]
            ],
            'Transaction' => [
                'class' => $this->module->modelMap['Transaction'],
                'dependence' => [$exAttr => true, 'currency_id' => true, 'type_transaction_id' => true]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['attribute', 'value'], 'required'],
            [['wallet_group_id', 'currency_id', 'type_transaction_id', 'created_at', 'updated_at', 'wallet_id'], 'integer'],
            [['attribute', 'value'], 'string', 'max' => 64],
            ['attribute', 'in', 'range' => function () {
                return array_keys(self::getAttributesList());
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
            'wallet_id' => Yii::t('wlt.models', 'Wallet Exception'),
            'config_group_id' => Yii::t('wlt.models', 'Config Group ID'),
            'attribute' => Yii::t('wlt.models', 'Attribute'),
            'value' => Yii::t('wlt.models', 'Value'),
            'created_at' => Yii::t('wlt.models', 'Created At'),
            'updated_at' => Yii::t('wlt.models', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWalletGroup()
    {
        return $this->hasOne($this->module->modelMap['WalletGroup'], ['id' => 'wallet_group_id']);
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
    public function getTypeTransaction()
    {
        return $this->hasOne($this->module->modelMap['TypeTransaction'], ['id' => 'type_transaction_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWallet()
    {
        return $this->hasOne($this->module->modelMap['Wallet'], ['id' => 'wallet_id']);
    }
}
