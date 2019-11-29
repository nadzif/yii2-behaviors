<?php
/**
 * Created by PhpStorm.
 * User: Nadzif Glovory
 * Date: 11/26/2019
 * Time: 3:36 PM
 */

namespace nadzif\behaviors;


use nadzif\behaviors\helpers\FileHelper;
use nadzif\behaviors\models\File;
use yii\base\Model;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * Class UploadBehavior
 *
 * @package nadzif\behaviors
 */
class UploadBehavior extends AttributeBehavior
{
    /** @var ActiveRecord */
    public $targetModel;
    public $fileAttribute;
    public $nameAttribute; //optional
    public $informationAttribute; //optional

    public $fileClass = File::class;

    public $isBase64        = false;
    public $requireLogin    = true;
    public $maxSize         = 88388608;
    public $uploadAlias     = File::ALIAS_WEB;
    public $uploadPath      = 'uploads';
    public $directoryMode   = 0755;
    public $createThumbnail = true;
    public $baseUrl;

    public $allowedExtensions;

    public $thumbnailPrefix; //optional
    public $thumbnailExtension; //optional
    public $thumbnailOptions; //optional

    /**
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => $this->isBase64 ? 'uploadBase64' : 'upload'
        ];
    }

    /**
     *
     */
    public function upload()
    {
        $this->validateEvent();

        /** @var Model $formModel */
        $formModel    = $this->owner;
        $fileInstance = UploadedFile::getInstance($formModel, $this->fileAttribute);

        $aliasDirectory = \Yii::getAlias($this->uploadAlias);
        $dirPath        = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $aliasDirectory) . DIRECTORY_SEPARATOR;
        $uploadPath     = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->uploadPath) . DIRECTORY_SEPARATOR;

        /** @var File $model */
        $model = new $this->fileClass;

        if ($this->thumbnailPrefix) {
            $model->thumbnailPrefix = $this->thumbnailPrefix;
        }

        if ($this->thumbnailOptions) {
            $model->thumbnailOptions = $this->thumbnailOptions;
        }

        if ($this->thumbnailExtension) {
            $model->defaultThumbnailExtension = $this->thumbnailExtension;
        }

        if ($this->allowedExtensions) {
            $model->allowedExtensions = $this->allowedExtensions;
        }

        if (!ArrayHelper::isIn($this->uploadAlias, [File::ALIAS_WEBROOT, File::ALIAS_WEB])) {
            $dirPath           .= 'web' . DIRECTORY_SEPARATOR;
            $model->requireWeb = true;
        } else {
            $model->requireWeb = false;
        }

        is_dir($dirPath) ?: mkdir($dirPath, $this->directoryMode, true); // MAKE DIRECTORY IF NOT EXIST

        if ($fileInstance->size > $this->maxSize) {
            $formModel->addError($this->fileAttribute, \Yii::t('app', 'File size is too big. Max size: {size}', [
                'size' => FileHelper::convertToReadableSize($this->maxSize)
            ]));
        }

        $extensionAllowed = false;
        foreach ($model->allowedExtensions as $type => $extensions) {
            if (ArrayHelper::isIn($model->extension, $extensions)) {
                $extensionAllowed = true;
                break;
            }
        }

        if (!$extensionAllowed) {
            $formModel->addError($this->fileAttribute, \Yii::t('app', 'Extension not supported.'));
        }

        $model->uploadAlias = $this->uploadAlias;
        $model->uploadPath  = $uploadPath;
        $model->baseUrl     = $this->baseUrl ?: \Yii::$app->urlManager->baseUrl;
        $model->name        = FileHelper::slug($fileInstance->baseName) . '_' . dechex(time());
        $model->size        = $fileInstance->size;
        $model->extension   = $fileInstance->extension;
        $model->type        = $fileInstance->type;

        $nameAttribute        = $this->nameAttribute ?: false;
        $informationAttribute = $this->informationAttribute ?: false;

        if ($nameAttribute) {
            $model->displayName = $formModel->$nameAttribute;
        } else {
            $model->displayName = $fileInstance->name;
        }

        if ($informationAttribute) {
            $model->additionalInformation = $formModel->$informationAttribute;
        }

        $fullPath = $dirPath . $model->name . '.' . $model->extension;
        $fullPath = str_replace('\\', DIRECTORY_SEPARATOR, $fullPath);

        if (!$formModel->hasErrors()) {
            if ($model->validate() && $fileInstance->saveAs($fullPath) && $model->save()) {
                if ($this->createThumbnail) {
                    $model->createThumbnail($this->thumbnailExtension);
                }
            }
        }
    }

    private function validateEvent()
    {
        /** @var Model $formModel */
        $formModel = $this->owner;

        if ($this->requireLogin && \Yii::$app->user->isGuest) {
            $formModel->addError($this->fileAttribute, \Yii::t('app', 'Upload file require login'));
        }

    }

}