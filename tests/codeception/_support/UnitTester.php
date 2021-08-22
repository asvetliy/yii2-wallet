<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class UnitTester extends \Codeception\Actor
{
    use _generated\UnitTesterActions;

    public function migrateUp()
    {
        exec('php ' . __DIR__ . '/../_app/yii migrate --interactive=0');
    }

    public function migrateDown()
    {
        exec('php ' . __DIR__ . '/../_app/yii migrate/down 999 --interactive=0');
    }

    public function echo($message)
    {
        fwrite(STDERR, PHP_EOL . $message . PHP_EOL);
    }
    
   /**
    * Define custom actions here
    */
}
