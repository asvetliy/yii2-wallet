<?php

namespace asmbr\wallet;

use yii\base\BootstrapInterface;
use yii\i18n\PhpMessageSource;
use asmbr\math\MathHelper;

class Bootstrap implements BootstrapInterface
{
    /**
     * List using database models in module
     *
     * @var array Model's map
     */
    protected $_modelMap = [
        'User' => null,
        'AccountExchange' => 'asmbr\wallet\models\AccountExchange',
        'AccountFund' => 'asmbr\wallet\models\AccountFund',
        'Config' => 'asmbr\wallet\models\Config',
        'DependentTransaction' => 'asmbr\wallet\models\DependentTransaction',
        'Currency' => 'asmbr\wallet\models\Currency',
        'Transaction' => 'asmbr\wallet\models\Transaction',
        'Operation' => 'asmbr\wallet\models\Operation',
        'Clarity' => 'asmbr\wallet\models\Clarity',
        'TypeFund' => 'asmbr\wallet\models\TypeFund',
        'TypeFundPriority' => 'asmbr\wallet\models\TypeFundPriority',
        'TypeTransaction' => 'asmbr\wallet\models\TypeTransaction',
        'Wallet' => 'asmbr\wallet\models\Wallet',
        'WalletGroup' => 'asmbr\wallet\models\WalletGroup',
    ];

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        /* @var $module Module */
        $module = \Yii::$app->getModule('wallet');

        if ($module instanceof Module) {
            $module->modelMap = array_merge($this->_modelMap, $module->modelMap);
        }

        if (!isset($app->get('i18n')->translations['wlt*'])) {
            $app->get('i18n')->translations['wlt*'] = [
                'class'    => PhpMessageSource::className(),
                'basePath' => __DIR__ . '/messages',
                'sourceLanguage' => 'en-US'
            ];
        }

        MathHelper::$currencyClass = $module->modelMap['Currency'];
    }
}