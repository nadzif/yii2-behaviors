<?php

use nadzif\behaviors\models\File;
use nadzif\behaviors\models\Trail;
use yii\db\Migration;

/**
 * Handles the creation of table `trail`.
 */
class m180721_000000_upload_init extends Migration
{
    public function safeUp()
    {
        switch ($this->db->driverName) {
            case 'mysql':
                $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
                break;
            default:
                $tableOptions = null;
        }

        $this->createTable(File::tableName(), [
            'id'                    => $this->bigPrimaryKey()->unsigned(),
            'type'                  => $this->string(50),
            'displayName'           => $this->string()->notNull(),
            'requireWeb'            => $this->boolean()->defaultValue(0),
            'uploadAlias'           => $this->string()->notNull(),
            'uploadPath'            => $this->string()->notNull(),
            'baseUrl'               => $this->string()->notNull(),
            'name'                  => $this->string()->notNull(),
            'size'                  => $this->double()->notNull(),
            'extension'             => $this->string(15)->notNull(),
            'mime'                  => $this->string(255)->notNull(),
            'hasThumbnail'          => $this->boolean()->defaultValue(0),
            'thumbnailName'         => $this->string(),
            'thumbnailSize'         => $this->double(),
            'thumbnailExtension'    => $this->string(15),
            'thumbnailMime'         => $this->string(255),
            'additionalInformation' => $this->text(),
            'createdAt'             => $this->dateTime(),
            'updatedAt'             => $this->dateTime(),
        ], $tableOptions);

        $this->createIndex('uniqueFile', File::tableName(), ['uploadAlias', 'uploadPath', 'name', 'extension'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable(File::tableName());
    }
}
