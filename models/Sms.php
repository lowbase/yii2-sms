<?php
/**
 * @package   yii2-sms
 * @author    Yuri Shekhovtsov <shekhovtsovy@yandex.ru>
 * @copyright Copyright &copy; Yuri Shekhovtsov, lowbase.ru, 2016
 * @version   1.0.0
 */

namespace lowbase\sms\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * Sms model
 *
 * @property integer $id
 * @property string $provider_sms_id
 * @property string $phone
 * @property string $text
 * @property integer $type
 * @property integer $for_user_id
 * @property integer $status
 * @property integer $created_by
 * @property string $created_at
 * @property string $must_sent_at
 * @property string $check_status_at
 * @property string $provider
 * @property string $provider_answer
 */
class Sms extends \yii\db\ActiveRecord
{
    const STATUS_UNKNOWN = -1;
    const STATUS_FAILED = 0;
    const STATUS_SENT = 1;
    const STATUS_QUEUED = 2;
    const STATUS_DELIVERED = 3;

    /**
     * @return array
     */
    public function behaviors()
    {
        return [[
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'created_at',
            'updatedAtAttribute' => 'check_status_at',
            'value' => date('Y-m-d H:i:s'),
        ], [
            'class' => BlameableBehavior::className(),
            'createdByAttribute' => 'created_by',
            'updatedByAttribute' => null,
            'value' => null,
        ]
        ];
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%sms}}';
    }

    /**
     * @param null $status
     * @return array|mixed|null
     */
    public static function getStatuses($status = null)
    {
        $statuses = [
            self::STATUS_FAILED => Yii::t('sms', 'Failed'),
            self::STATUS_SENT => Yii::t('sms', 'Sent'),
            self::STATUS_QUEUED => Yii::t('sms', 'Queued'),
            self::STATUS_DELIVERED => Yii::t('sms', 'Delivered'),
            self::STATUS_UNKNOWN => Yii::t('sms', 'Unknown'),
        ];
        if ($status) {

            return (in_array($status, array_keys($statuses))) ? $statuses[$status] : null;
        } else {

            return $statuses;
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['phone', 'text'], 'required'],
            ['phone', 'match', 'pattern' => '/^(\+7){1}\d{10}$/', 'message' => Yii::t('sms', 'Enter phone in format +79801234567.')],
            [['text', 'provider_answer'], 'string'],
            [['type', 'for_user_id', 'status', 'created_by'], 'integer'],
            [['created_at', 'must_sent_at', 'check_status_at'], 'safe'],
            [['provider_sms_id'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 20],
            [['provider'], 'string', 'max' => 100],
            ['status', 'in', 'range' => array_keys(self::getStatuses())],
            ['status', 'default', 'value' => self::STATUS_SENT],
            [['phone', 'text', 'provider_answer'], 'filter', 'filter' => 'trim'],
            [['text', 'provider_sms_id', 'type', 'for_user_id', 'created_by',
                'must_sent_at', 'check_status_at', 'provider', 'provider_answer'], 'default', 'value' => null],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('sms', 'ID'),
            'provider_sms_id' => Yii::t('sms', 'Provider Sms ID'),
            'phone' => Yii::t('sms', 'Phone'),
            'text' => Yii::t('sms', 'Text'),
            'type' => Yii::t('sms', 'Type'),
            'for_user_id' => Yii::t('sms', 'For User ID'),
            'status' => Yii::t('sms', 'Status'),
            'created_by' => Yii::t('sms', 'Created By'),
            'created_at' => Yii::t('sms', 'Created At'),
            'must_sent_at' => Yii::t('sms', 'Must Sent At'),
            'check_status_at' => Yii::t('sms', 'Check Status At'),
            'provider' => Yii::t('sms', 'Provider'),
            'provider_answer' => Yii::t('sms', 'Provider Answer'),
        ];
    }
}
