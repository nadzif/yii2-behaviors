<?php

namespace nadzif\behaviors;

/**
 * Class TimestampBehavior
 *
 * @package nadzif\behaviors
 */
class TimestampBehavior extends \yii\behaviors\TimestampBehavior
{
    public $createdAtAttribute = 'createdAt';
    public $updatedAtAttribute = 'updatedAt';

    public function init()
    {
        $this->value = date('Y-m-d H:i:s');
        parent::init();
    }
}