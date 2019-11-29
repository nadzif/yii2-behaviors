<?php

use yii\db\Migration;

/**
 * Handles the creation of table `trail`.
 */
class m180720_000000_trail_init extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        switch (\Yii::$app->db->driverName) {
            case 'mysql':
                $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
                break;
            default:
                $tableOptions = null;
        }

        $this->createTable(\nadzif\behaviors\models\Trail::tableName(), [
            'id'          => $this->bigPrimaryKey()->unsigned(),
            'userId'      => $this->bigInteger()->unsigned()->null(),
            'refCode'     => $this->string()->null(),
            'refId'       => $this->string()->notNull(),
            'className'   => $this->string()->notNull(),
            'action'      => $this->string()->comment('insert, update, delete, restore'),
            'dataUpdated' => $this->boolean(),
            'dataBefore'  => $this->text(),
            'dataAfter'   => $this->text(),
            'hostName'    => $this->string(),
            'hostInfo'    => $this->string(),
            'portRequest' => $this->string(),
            'url'         => $this->string(),
            'userHost'    => $this->string(),
            'userIp'      => $this->string(),
            'clientIp'    => $this->string(),
            'userAgent'   => $this->string(),
            'information' => $this->text(),
            'createdAt'   => $this->dateTime(),
        ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable(\nadzif\behaviors\models\Trail::tableName());
    }
}
