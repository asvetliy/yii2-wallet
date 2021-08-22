<?php
// This is global bootstrap for autoloading
use AspectMock\Kernel;

require __DIR__.'/_init.php';

// TODO: remove this shitty hack
// without following line test on travis fails
require_once VENDOR_DIR.'/yiisoft/yii2/base/ErrorException.php';

/** @var Kernel $kernel */
$kernel = Kernel::getInstance();
$kernel->init([
    'debug'        => true,
    'includePaths' => [__DIR__.'/../../models']
]);
$kernel->loadFile(VENDOR_DIR.'/yiisoft/yii2/Yii.php');

$_SERVER['SERVER_NAME']     = 'localhost';

Yii::setAlias('@tests', dirname(__DIR__));
Yii::setAlias('@asmbr/wallet', realpath(__DIR__.'..'));