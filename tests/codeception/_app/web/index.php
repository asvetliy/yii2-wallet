<?php

require(__DIR__ . '/../../_init.php');
require(VENDOR_DIR . '/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/web.php');

(new yii\web\Application($config))->run();