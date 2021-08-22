<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/9/16
 * Time: 4:34 PM
 */

namespace asmbr\wallet\tests\codeception\_fixtures;


use asmbr\wallet\models\Currency;
use yii\helpers\ArrayHelper;
use yii\test\Fixture;

class CurrencyFixture extends Fixture
{
    public $data = [];
    
    public function load()
    {
        $this->data = ArrayHelper::index(Currency::find()->all(), 'code');
    }
    
    public function getModel($code)
    {
        return isset($this->data[$code]) ? $this->data[$code] : null;
    }
}