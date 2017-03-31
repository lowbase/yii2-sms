<?php
/**
 * @package   yii2-sms
 * @author    Yuri Shekhovtsov <shekhovtsovy@yandex.ru>
 * @copyright Copyright &copy; Yuri Shekhovtsov, lowbase.ru, 2016
 * @version   1.0.0
 */

use yii\db\Migration;

/**
 * Sms migration
 *
 * Class m161004_204816_sms_init
 */
class m161004_204816_sms_init extends Migration
{
    const SMS_DEFAULT_STATUS = 1;

    /**
     * Run migration by single transaction
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // Create sms table
        $this->createTable('{{%sms}}', [
            'id' => $this->primaryKey(),
            'provider_sms_id' => $this->string(),
            'phone' => $this->string(20)->notNull(),
            'text' => $this->text()->notNull(),
            'type' => $this->smallInteger(3),
            'for_user_id' => $this->integer(),
            'status' => $this->smallInteger(1)->notNull()->defaultValue(self::SMS_DEFAULT_STATUS),
            'created_by' => $this->integer(),
            'created_at' => $this->dateTime()->notNull(),
            'must_sent_at' => $this->dateTime(),
            'check_status_at' => $this->dateTime(),
            'provider' => $this->string(100),
            'provider_answer' => $this->text()
        ], $tableOptions);

        // Create indexes
        $this->createIndex('lb_sms-status-idx', '{{%sms}}', 'status');
        $this->createIndex('lb_provider_sms_id-idx', '{{%sms}}', 'provider_sms_id');
        $this->createIndex('lb_sms-for_user_id-idx', '{{%sms}}', 'for_user_id');
        $this->createIndex('lb_sms-phone-idx', '{{%sms}}', 'phone');
    }

    /**
     * Migration down by single transaction
     */
    public function safeDown()
    {
        // Drop sms table
        $this->dropTable('{{%sms}}');
    }
}
