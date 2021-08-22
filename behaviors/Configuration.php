<?php

namespace asmbr\wallet\behaviors;

use asmbr\wallet\models\Wallet;
use yii\base\Behavior;
use asmbr\wallet\models\Config;
use asmbr\wallet\models\WalletGroup;
use asmbr\wallet\models\Currency;
use asmbr\wallet\models\TypeTransaction;

/**
 * @property \asmbr\wallet\models\WalletGroup $group
 * @property \asmbr\wallet\models\Currency $currency
 * @property \asmbr\wallet\models\TypeTransaction $type
 * @property array $config
 * @property \asmbr\wallet\models\Config[] $stackConfiguration
 * @property \asmbr\wallet\models\BaseRecord $owner
 * @property \asmbr\wallet\models\Wallet $wallet
*/
class Configuration extends Behavior
{
    public $config;

    public $eventInit;

    protected $group;

    protected $wallet;

    protected $currency;

    protected $type;

    protected $stackConfiguration;

    public function init()
    {
        if(!($this->config instanceof \Closure))
            throw new \Exception('The argument ' . self::className() .'::config must be the Closure function');

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return $this->eventInit ? [
            $this->eventInit => 'redefine',
        ] : [];
    }

    /**
     * @return \asmbr\wallet\models\Config[]
    */
    protected function getConfig()
    {
        if($this->stackConfiguration)
            return $this->stackConfiguration;

        if($this->getBaseParams())
            return $this->stackConfiguration = Config::get($this->group, $this->currency, $this->type, $this->wallet);
        return [];
    }

    /**
     * Redefine all property in object if there is a match with the method Config::getAttributesList()
    */
    public function redefine()
    {
        $modelKey = $this->owner->getModelMapKey();

        $configs = $this->getConfig();
        foreach($configs as $conf){
            if(strpos($conf->attribute, $modelKey) !== false ){
                $arr = explode('::', $conf->attribute);
                if(isset($arr) && $this->owner->hasProperty($arr[1])){
                    $this->owner->{$arr[1]} = $conf->value;
                }
            }
        }
    }

    /**
     * @set $currency
     * @set $group
     * @set $type
     * @throws \Exception
    */
    protected function getBaseParams()
    {
        $config = call_user_func($this->config);
        if(is_array($config))
        {
            foreach($config as $item)
            {
                if($item instanceof Currency)
                    $this->currency = $item;
                if($item instanceof WalletGroup)
                    $this->group = $item;
                if($item instanceof TypeTransaction)
                    $this->type = $item;
                if($item instanceof Wallet)
                    $this->wallet = $item;
            }
        }else{
            throw new \Exception(self::className() .'::config the function should return an array of objects (WalletGroup, Currency, TypeOperation, Wallet)');
        }

        return isset($this->group);
    }
}