<?php
/**
 * Created by PhpStorm.
 * User: brom
 * Date: 6/8/16
 * Time: 2:14 PM
 */

use asmbr\wallet\models\TypeTransaction;

$time = time();

$fixtures = [
    'sender_required' => [
        'id' => 100,
        'parent_id' => null,
        'code' => TypeTransaction::DEFAULT_CODE,
        'name' => 'Transfer',
        'description' => '',
        'required_sender' => 1,
        'enabled' => 1,
        'depth' => 0,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'sender_not_required' => [
        'id' => 101,
        'parent_id' => null,
        'code' => 'TT_UPCASH',
        'name' => 'Up Cash',
        'description' => '',
        'required_sender' => 0,
        'enabled' => 1,
        'depth' => 0,
        'created_at' => $time,
        'updated_at' => $time,
    ],
    'disabled' => [
        'id' => 102,
        'parent_id' => null,
        'code' => 'TT_DISABLED',
        'name' => 'Disabled',
        'description' => '',
        'required_sender' => 1,
        'enabled' => 0,
        'depth' => 0,
        'created_at' => $time,
        'updated_at' => $time,
    ],
];

return $fixtures;