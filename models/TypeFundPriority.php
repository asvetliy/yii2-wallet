<?php

namespace asmbr\wallet\models;

use Yii;

/**
 * This is the model class for table "wlt_type_fund_priority".
 *
 * @property integer $id
 * @property integer $trn_type_id
 * @property integer $fund_type_id
 * @property integer $value
 *
 * @property TypeTransaction $typeTransaction
 * @property TypeFund $typeFund
 * @property int $int
 */
class TypeFundPriority extends BaseRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wlt_type_fund_priority}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['trn_type_id', 'fund_type_id', 'value'], 'integer'],
            [['fund_type_id', 'value'], 'required'],
            [['trn_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['TypeTransaction'], 'targetAttribute' => ['trn_type_id' => 'id']],
            [['fund_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['TypeFund'], 'targetAttribute' => ['fund_type_id' => TypeFund::tableName() . '.id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('wlt.models', 'ID'),
            'trn_type_id' => Yii::t('wlt.models', 'Trn Type ID'),
            'fund_type_id' => Yii::t('wlt.models', 'Fund Type ID'),
            'value' => Yii::t('wlt.models', 'Value'),
        ];
    }

    public function enable()
    {
        if(!$this->value) {
            $value = TypeFundPriority::find()->andWhere(['trn_type_id' => $this->trn_type_id])->max('value');
            $this->value = $value + 1;
            $this->save();
        }
    }

    public function disable()
    {
        if($this->value !== 0) {
            /** @var TypeFundPriority[] $priorities */
            $priorities = TypeFundPriority::find()->andWhere(['trn_type_id' => $this->trn_type_id])->andWhere(['>', 'value', $this->value])->all();
            $this->value = 0;
            $this->save();
            foreach ($priorities as $priority) {
                $priority->value = $priority->value - 1;
                $priority->save();
            }
        }
    }

    public function up()
    {
        if($this->value > 1) {
            $this->value -= 1;
            /** @var TypeFundPriority $priority */
            if(($priority = TypeFundPriority::find()->andWhere(['trn_type_id' => $this->trn_type_id, 'value' => $this->value])->one()) !== null) {
                $priority->value += 1;
                $priority->save();
            }
            $this->save();
        }
    }

    public function down()
    {
        $value = TypeFundPriority::find()->andWhere(['trn_type_id' => $this->trn_type_id])->max('value');
        if($this->value < $value && $this->value > 0) {
            $this->value += 1;
            /** @var TypeFundPriority $priority */
            if(($priority = TypeFundPriority::find()->andWhere(['trn_type_id' => $this->trn_type_id, 'value' => $this->value])->one()) !== null) {
                $priority->value -= 1;
                $priority->save();
            }
            $this->save();
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeTransaction()
    {
        return $this->hasOne($this->module->modelMap['TypeTransaction'], ['id' => 'trn_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeFund()
    {
        return $this->hasOne($this->module->modelMap['TypeFund'], ['id' => 'fund_type_id']);
    }
}
