<?php

namespace nadzif\behaviors;


use nadzif\behaviors\helpers\IdentityHelper;
use nadzif\behaviors\models\Trail;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * Class LogBehavior
 *
 * @package nadzif\behaviors
 *
 *
 */
class LogBehavior extends Behavior
{
    public  $_information;
    public  $_refCode = null;
    private $dataBefore;

    /**
     * @param string $action
     *
     * @return bool
     */
    public function behaviorRecord($action)
    {
        $dataBefore = Json::encode($this->getOldAttributes());
        $dataAfter  = Json::encode($this->getAttributes());

        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        $trail              = new Trail();
        $trail->userId      = IdentityHelper::getUserId();
        $trail->refCode     = $this->_refCode;
        $trail->refId       = $owner->getPrimaryKey()[0];
        $trail->className   = $owner->className();
        $trail->action      = $action;
        $trail->dataUpdated = $dataBefore == $dataAfter;
        $trail->dataBefore  = $dataBefore;
        $trail->dataAfter   = $dataAfter;
        $trail->hostName    = IdentityHelper::getHostName();
        $trail->hostInfo    = IdentityHelper::getHostInfo();
        $trail->portRequest = IdentityHelper::getPortRequest();
        $trail->url         = IdentityHelper::getUrl();
        $trail->userHost    = IdentityHelper::getUserHost();
        $trail->userIp      = IdentityHelper::getUserIP();
        $trail->clientIp    = IdentityHelper::getClientIP();
        $trail->userAgent   = IdentityHelper::getUserAgent();
        $trail->information = $this->_information;

        return $trail->save();
    }

    /**
     * @return mixed
     */
    private function getOldAttributes()
    {

        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        return $owner->getOldAttributes();
    }

    /**
     * @return mixed
     */
    private function getAttributes()
    {

        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        return $owner->getAttributes();
    }

    /**
     * @return bool
     */
    public function createRecord()
    {
        return $this->behaviorRecord(Trail::ACTION_INSERT);
    }

    /**
     * @return bool
     */
    public function updateRecord()
    {
        return $this->behaviorRecord(Trail::ACTION_UPDATE);
    }

    /**
     * @return bool
     */
    public function deleteRecord()
    {
        return $this->behaviorRecord(Trail::ACTION_DELETE);
    }


}