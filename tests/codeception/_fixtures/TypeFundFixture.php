<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/8/16
 * Time: 3:37 PM
 */

namespace asmbr\wallet\tests\codeception\_fixtures;

use yii\test\ActiveFixture;
use yii\base\InvalidConfigException;

class TypeFundFixture extends ActiveFixture
{
    public $modelClass = 'asmbr\wallet\models\TypeFund';
    public $dataFile = '@tests/codeception/_fixtures/data/type_fund.php';

    private $_models;

    public function getModel($name)
    {
        if (!isset($this->data[$name])) {
            return null;
        }
        if (array_key_exists($name, $this->_models)) {
            return $this->_models[$name];
        }

        if ($this->modelClass === null) {
            throw new InvalidConfigException('The "modelClass" property must be set.');
        }
        $row = $this->data[$name];
        /* @var $modelClass \yii\db\ActiveRecord */
        $modelClass = $this->modelClass;
        return $this->_models[$name] = $modelClass::findOne($row['id']);
    }

    public function unload()
    {
        parent::unload();
        $this->data = [];
        $this->_models = [];
    }
}