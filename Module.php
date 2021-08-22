<?php

namespace asmbr\wallet;

use yii;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    /** @var yii\db\ActiveRecord[] */
    public $modelMap = [];

    public $modelInspectSalt = 'wallet';
    
    public $dependentTransaction = false;

    public $negativeAccountExchangeBalance = false;
}