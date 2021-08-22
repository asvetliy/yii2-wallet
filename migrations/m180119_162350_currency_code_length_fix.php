<?php

use yii\db\Migration;
use asmbr\wallet\models\Currency;

/**
 * Class m180119_162350_currency_code_length
 */
class m180119_162350_currency_code_length_fix extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn(Currency::tableName(), 'code', $this->string(10)->unique()->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->alterColumn(Currency::tableName(), 'code', $this->string(3)->unique()->notNull());

        return true;
    }
}
