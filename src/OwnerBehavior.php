<?php

namespace nadzif\behaviors;


use nadzif\behaviors\helpers\IdentityHelper;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Class OwnerBehavior
 *
 * @package nadzif\behaviors
 */
class OwnerBehavior extends Behavior
{
    public $createdAttribute = 'createdBy';
    public $updatedAttribute = 'updatedBy';
    public $deletedAttribute = 'deletedBy';

    public $recordWhileCreate = true;
    public $recordWhileUpdate = true;
    public $recordWhileDelete = true;

    /**
     * @return array
     */
    public function events()
    {
        $behaviorEvents = [];
        if ($this->recordWhileCreate) {
            $behaviorEvents[ActiveRecord::EVENT_AFTER_INSERT] = 'createdBy';
        }

        if ($this->recordWhileCreate) {
            $behaviorEvents[ActiveRecord::EVENT_BEFORE_UPDATE] = 'updatedBy';
        }

        if ($this->recordWhileCreate) {
            $behaviorEvents[ActiveRecord::EVENT_BEFORE_DELETE] = 'deletedBy';
        }

        return $behaviorEvents;
    }

    public function createdBy()
    {
        $attribute               = $this->createdAttribute;
        $this->owner->$attribute = IdentityHelper::getUserId();
    }

    public function updatedBy()
    {
        $attribute               = $this->updatedAttribute;
        $this->owner->$attribute = IdentityHelper::getUserId();
    }

    public function deletedBy()
    {
        $attribute               = $this->deletedAttribute;
        $this->owner->$attribute = IdentityHelper::getUserId();
    }

}