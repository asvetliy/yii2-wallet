<?php

namespace asmbr\wallet\models;

use yii;
use yii\behaviors\TimestampBehavior;
use asmbr\wallet\behaviors\InspectBehavior;
use asmbr\math\CurrencyScaleInterface;

/**
 * This is the model class for table "{{%wlt_currency}}".
 *
 * @property integer $id
 * @property string $code
 * @property string $name
 * @property integer $enabled
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $inspected
 * @property integer $storage_factor
 * @property integer $storage_multiply
 * @property boolean $isCrypto
 * @property $symbol
 *
 * @property AccountExchange[] $accounts
 */
class Currency extends BaseRecord implements CurrencyScaleInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_currency}}';
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
            [['enabled', 'created_at', 'updated_at', 'storage_factor'], 'integer'],
            [['code'], 'string', 'max' => 10],
            [['name'], 'string', 'max' => 256],
            [['inspected'], 'string', 'max' => 32],
            [['code'], 'unique'],
            ['isCrypto', 'boolean']
        ];
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->populateRelation('storage_multiply', pow(10, $this->storage_factor));
    }

    public function getSymbol()
    {
        $currency = $this->code;
        $locale = Yii::$app->formatter->locale;
        $fmt = new \NumberFormatter( $locale."@currency=$currency", \NumberFormatter::CURRENCY );
        return $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
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
            'enabled' => Yii::t('wlt.models', 'Enabled'),
            'created_at' => Yii::t('wlt.models', 'Created At'),
            'updated_at' => Yii::t('wlt.models', 'Updated At'),
            'inspected' => Yii::t('wlt.models', 'Inspected'),
            'storage_factor' => Yii::t('wlt.models', 'Factor'),
            'isCrypto' => Yii::t('wlt.models', 'is Crypto?'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccounts()
    {
        return $this->hasMany($this->module->modelMap['AccountExchange'], ['currency_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigGroup()
    {
        return $this->hasMany($this->module->modelMap['ConfigGroup'], ['currency_id' => 'id']);
    }

    /**
     * @inheritdoc
     */
    public function getScale($code)
    {
        $model = self::findOne(['code' => $code]);
        if ($model) {
            return $model->storage_factor;
        }
        return false;
    }
}
