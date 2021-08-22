<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 5/31/16
 * Time: 10:35 AM
 */

namespace asmbr\wallet\models;

use yii\base\Event;

class TransactionEvent extends Event
{
    /** @var Transaction[] */
    public $result = [];
}