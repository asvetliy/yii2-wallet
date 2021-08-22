<?php

/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/2/16
 * Time: 2:41 PM
 */

namespace asmbr\wallet\migrations;

use yii;
use asmbr\wallet\Module;

/**
 * Class Migration
 * @package asmbr\wallet\migrations
 * 
 * @property Module $module
 */
class Migration extends \yii\db\Migration
{
    protected $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
   
    public $md5_length = 32;
    public $small_str_length = 64;
    public $medium_str_length = 256;
    public $large_str_length = 1024;
    public $balance_length = 13;
    public $balance_after_point_length = 2;

    /**
     * @return null|Module
     */
    public function getModule()
    {
        return Yii::$app->getModule('wallet');
    }
}