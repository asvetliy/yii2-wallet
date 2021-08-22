<?php

use asmbr\wallet\migrations\Migration;
use asmbr\wallet\models\Currency;

/**
 * Migrate for standard ISO 4217 currencies
 */
class m160519_135214_currency_init extends Migration
{
    public $currency = [
        ['code' => 'USD', 'name' => 'U.S. Dollar', 'enabled' => 1],
        ['code' => 'EUR', 'name' => 'Euro'],
        ['code' => 'RUB', 'name' => 'Russian Ruble'],
        ['code' => 'GBP', 'name' => 'British Pound'],
        ['code' => 'HKD', 'name' => 'Hong Kong Dollar'],
        ['code' => 'SGD', 'name' => 'Singapore Dollar'],
        ['code' => 'JPY', 'name' => 'Japanese Yen'],
        ['code' => 'CAD', 'name' => 'Canadian Dollar'],
        ['code' => 'AUD', 'name' => 'Australian Dollar'],
        ['code' => 'CHF', 'name' => 'Swiss Franc'],
        ['code' => 'DKK', 'name' => 'Danish Krone'],
        ['code' => 'SEK', 'name' => 'Swedish Krona'],
        ['code' => 'NOK', 'name' => 'Norwegian Krone'],
        ['code' => 'ILS', 'name' => 'Israeli Sheke'],
        ['code' => 'MYR', 'name' => 'Malaysian Ringgit'],
        ['code' => 'NZD', 'name' => 'New Zealand Dollar'],
        ['code' => 'TRY', 'name' => 'New Turkish Lira'],
        ['code' => 'AED', 'name' => 'Utd. Arab Emir. Dirham'],
        ['code' => 'MAD', 'name' => 'Moroccan Dirham'],
        ['code' => 'QAR', 'name' => 'Qatari Rial'],
        ['code' => 'SAR', 'name' => 'Saudi Riyal'],
        ['code' => 'TWD', 'name' => 'Taiwan Dollar'],
        ['code' => 'THB', 'name' => 'Thailand Baht'],
        ['code' => 'CZK', 'name' => 'Czech Koruna'],
        ['code' => 'HUF', 'name' => 'Hungarian Forint'],
        ['code' => 'BGN', 'name' => 'Bulgarian Leva'],
        ['code' => 'PLN', 'name' => 'Polish Zloty'],
        ['code' => 'ISK', 'name' => 'Iceland Krona'],
        ['code' => 'INR', 'name' => 'Indian Rupee'],
        ['code' => 'KRW', 'name' => 'South‐Korean Won'],
        ['code' => 'ZAR', 'name' => 'South‐African Rand'],
        ['code' => 'RON', 'name' => 'Romanian Leu New'],
        ['code' => 'HRK', 'name' => 'Croatian Kuna'],
        ['code' => 'JOD', 'name' => 'Jordanian Dinar'],
        ['code' => 'OMR', 'name' => 'Omani Rial'],
        ['code' => 'RSD', 'name' => 'Serbian Dinar'],
        ['code' => 'TND', 'name' => 'Tunisian Dinar'],
        ['code' => 'BHD', 'name' => 'Bahraini Dinar'],
        ['code' => 'KWD', 'name' => 'Kuwaiti Dinar']
    ];

    public function up()
    {
        foreach ($this->currency as $c) {
            $columns = array_merge([
                'enabled' => 0,
                'created_at' => time(),
                'updated_at' => time()
            ], $c);
            $columns['inspected'] = md5($this->db->lastInsertID . implode('', $columns) . $this->module->modelInspectSalt);
            $this->insert(Currency::tableName(), $columns);
        }
    }

    public function down()
    {
        return true;
    }
}
