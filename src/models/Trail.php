<?php
/**
 * Created by PhpStorm.
 * User: Nadzif Glovory
 * Date: 7/25/2018
 * Time: 1:25 PM
 */

namespace nadzif\behaviors\models;


use nadzif\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * Class Trail
 *
 * @package nadzif\behaviors\models
 *
 * @property integer $id
 * @property string  $userId
 * @property string  $refCode
 * @property string  $refId
 * @property string  $className
 * @property string  $action
 * @property boolean $dataUpdated
 * @property string  $dataBefore
 * @property string  $dataAfter
 * @property string  $hostName
 * @property string  $hostInfo
 * @property string  $portRequest
 * @property string  $url
 * @property string  $userHost
 * @property string  $userIp
 * @property string  $clientIp
 * @property string  $userAgent
 * @property string  $information
 * @property string  $createdAt
 *
 */
class Trail extends ActiveRecord
{

    const ACTION_INSERT  = 'insert';
    const ACTION_UPDATE  = 'update';
    const ACTION_DELETE  = 'delete';
    const ACTION_RESTORE = 'restore';

    public static function tableName()
    {
        return '{{%trail}}';
    }

    public static function rollBackByTime($from, $to = false)
    {
        /** @todo create function for rollback by time */
    }

    public function behaviors()
    {
        return [
            'timestampBehavior' => [
                'class'              => TimestampBehavior::className(),
                'updatedAtAttribute' => false
            ],
        ];
    }

    /**
     * @return array
     */
    public function getDataBefore()
    {
        return $this->dataBefore ? Json::decode($this->dataBefore) : [];
    }

    /**
     * @return array
     */
    public function getDataAfter()
    {
        return $this->dataAfter ? Json::decode($this->dataAfter) : [];
    }

    /**
     * @return bool|ActiveRecord
     */
    public function rollBack()
    {
        if ($this->getOldAttributes()) {
            $modelClass = $this->className;
            /** @var ActiveRecord $model */
            if ($this->action == self::ACTION_UPDATE) {
                $model = $modelClass::findOne($this->refId);
            } else {
                $model = new $modelClass;
            }

            $model->behaviors          = [];
            $model->attributes         = $this->getOldAttributes();

            $model->save(false);

            return $model;
        } else {
            return false;
        }

    }

}