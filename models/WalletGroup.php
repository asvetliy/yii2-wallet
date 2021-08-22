<?php

namespace asmbr\wallet\models;

use yii;
use yii\behaviors\TimestampBehavior;
use asmbr\wallet\behaviors\InspectBehavior;

/**
 * This is the model class for table "{{%wlt_wallet_group}}".
 *
 * @property integer $id
 * @property string $code
 * @property string $name
 * @property string $description
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $inspected
 * @property bool $dataIsValid
 *
 * @property Wallet[] $wallets
 */
class WalletGroup extends BaseRecord
{
    const DEFAULT_CODE = 'WGRP_DEFAULT';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_wallet_group}}';
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
            'created_at' => Yii::t('wlt.models', 'Created At'),
            'updated_at' => Yii::t('wlt.models', 'Updated At'),
            'inspected' => Yii::t('wlt.models', 'Inspected'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWallets()
    {
        return $this->hasMany($this->module->modelMap['Wallet'], ['wallet_group_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigGroups()
    {
        return $this->hasMany($this->module->modelMap['ConfigGroup'], ['id' => 'wallet_group_id']);
    }
}
