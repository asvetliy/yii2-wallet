<?php

namespace asmbr\wallet\tests\codeception\unit;

use yii;
use yii\codeception\TestCase as BaseTestCase;
use yii\test\ActiveFixture;
use Codeception\Specify;
use asmbr\wallet\Module;

/**
 * Class TestCase
 * @package asmbr\wallet\tests\codeception\unit
 *
 * @method getName($withDataSet = true)
 * @method getTestResultObject()
 * @method toString()
 * @method ActiveFixture getFixture(string $name)
 */
class TestCase extends BaseTestCase
{
    use Specify;

    /** @var \UnitTester */
    protected $tester;

    /** @var Module */
    protected $module;

    public function setUp()
    {
        parent::setUp();
        $this->module = Yii::$app->getModule('wallet');
        $this->specifyConfig()->deepClone(false);
    }

    protected function _before()
    {
        parent::_before();
        $this->tester->migrateUp();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->tester->migrateDown();
    }
}