<?php

namespace asmbr\wallet\models;

use asmbr\wallet\Module;
use yii\db\ActiveRecord;
use yii\db\Schema;
use yii\helpers\ArrayHelper;
use yii\base\InvalidParamException;

/**
 * Base class for all model`s in module
 *
 * @property $module \asmbr\wallet\Module
*/
class BaseRecord extends ActiveRecord
{
    /** @var Module */
    protected $module;

    public function __construct($config = [])
    {
        $this->module = \Yii::$app->getModule('wallet');
        parent::__construct($config);
    }

    /**
     * @inheritdoc
    */
    public static function getTableSchema()
    {
        $scheme = parent::getTableSchema();
        foreach ($scheme->columns as $name => $column) {
            if($column->type == Schema::TYPE_DECIMAL)
                $column->phpType = 'double';
        }
        return $scheme;
    }

    public function readOnlyValidator($attribute)
    {
        if($this->getOldAttribute($attribute) !== $this->{$attribute})
            $this->addError($attribute, \Yii::t('wlt.models', "Attribute «{attr}» can't be updated.", ['attr' => $this->getAttributeLabel($attribute)]));
    }

    /**
     * @return string
    */
    public function getModelMapKey()
    {
        foreach($this->module->modelMap as $key => $namespace)
        {
            if($namespace !== null && $this instanceof $namespace)
                return $key;
        }
        throw new InvalidParamException('Can`t find module with key '.$this->className());
    }

    /**
     * @param array $models ActiveRecord
     * @return int
    */
    public static function batchInsert(array $models)
    {
        /** @var $models ActiveRecord[] */
        if(!empty($models))
        {
            $rows = ArrayHelper::getColumn($models, 'attributes');
            return \Yii::$app->db->createCommand()->batchInsert($models[0]::tableName(), $models[0]->attributes(), $rows)->execute();
        }
        return 0;
    }
}