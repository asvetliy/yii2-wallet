<?php

/**
 * Created by PhpStorm.
 * User: brom
 * Date: 5/20/16
 * Time: 12:14 PM
 */

namespace asmbr\wallet\behaviors;

use yii;
use yii\base\Event;
use yii\base\Model;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\base\InvalidValueException;

class InspectBehavior extends Behavior
{
    const EVENT_NOT_VALID = 'eventInspectHashIsNotValid';

    /** @var ActiveRecord */
    public $owner;

    /** @var string */
    public $attribute = 'inspected';

    /** @var array */
    public $validateRelations = [];

    public $validateScenarios = [Model::SCENARIO_DEFAULT];

    /** @var array|\Closure */
    public $value;

    /** @var string */
    public $salt = '';

    /** @var bool */
    protected $dataIsValid = true;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_FIND => 'inspect',
            BaseActiveRecord::EVENT_AFTER_REFRESH => 'inspect',
            BaseActiveRecord::EVENT_AFTER_INSERT => 'updateSignatureOfData',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            BaseActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate'
        ];
    }

    public function inspect()
    {
        if($this->getValue() !== $this->owner->{$this->attribute}) {
            $this->owner->trigger(static::EVENT_NOT_VALID, new Event);
            $this->dataIsValid = false;
        } else {
            $this->dataIsValid = true;
        }
    }
    
    public function getDataIsValid()
    {
        $this->inspect();
        return $this->dataIsValid;
    }

    public function updateSignatureOfData()
    {
        $this->owner->updateAttributes([$this->attribute => $this->getValue()]);
    }

    public function beforeUpdate($event)
    {
        if($this->dataIsValid) {
            $this->owner->{$this->attribute} = $this->getValue();
        } else {
            $event->sender->addError($this->attribute, 'Hash is not valid.');
            $event->isValid = false;
        }
    }

    protected function getValue()
    {
        if($this->value === null)
            $this->value = array_diff($this->owner->attributes(), [$this->attribute]);
        if(is_array($this->value)) {
            $str = '';
            foreach ($this->value as $attribute) {
                $str .= $this->owner->{$attribute};
            }
            return md5($str . $this->salt);
        } elseif($this->value instanceof \Closure) {
            return call_user_func($this->value);
        } else {
            throw new InvalidValueException('');
        }
    }

    public function afterValidate()
    {
        if($this->owner->hasErrors() || !in_array($this->owner->scenario, $this->validateScenarios)) return;
        if(!$this->owner->isNewRecord && !$this->dataIsValid)
            $this->owner->addError($this->attribute, 'Hash is not valid.');
        foreach ($this->validateRelations as $name) {
            $this->owner->getRelation($name, true);
            /** @var ActiveRecord $relation */
            if(($relation = $this->owner->$name) !== null) {
                foreach ($relation->behaviors as $behavior) {
                    if($behavior instanceof self) {
                        if(!$relation->dataIsValid)
                            $this->owner->addError($name, 'Hash is not valid.');
                        break;
                    }
                }
            }
        }
    }


}