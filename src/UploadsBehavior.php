<?php
/**
 * Created by PhpStorm.
 * User: Nadzif Glovory
 * Date: 1/31/2020
 * Time: 4:00 AM
 */

namespace nadzif\behaviors;

use nadzif\behaviors\helpers\FileHelper;
use nadzif\behaviors\models\File;
use yii\base\Behavior;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

class UploadsBehavior extends Behavior
{
    public $modelClass;
    public $targetAttribute = 'fileId';

    /** @var ActiveRecord */
    public $ownerModel;
    public $ownerKey = 'id';
    public $relationAttribute;

    // FORM ATTRIBUTE
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

    public $eventName = 'afterSubmit';

    public $thumbnailPrefix; //optional
    public $thumbnailExtension; //optional
    public $thumbnailOptions; //optional

    /**
     * @return array
     */
    public function events()
    {
        return [
            $this->eventName => $this->isBase64 ? 'uploadBase64' : 'upload'
        ];
    }

    /**
     *
     */
    public function upload()
    {
        $this->validateEvent();

        /** @var Model $formModel */
        $formModel     = $this->owner;
        $fileInstances = UploadedFile::getInstances($formModel, $this->fileAttribute);

        if ($fileInstances) {
            $aliasDirectory = \Yii::getAlias($this->uploadAlias);
            $dirPath        = $aliasDirectory . DIRECTORY_SEPARATOR;
            $uploadPath     = str_replace(['/', '\\'], '/', $this->uploadPath . '/');

            foreach ($fileInstances as $fileInstance) {
                /** @var File $fileModel */
                $fileModel = new $this->fileClass;

                if ($this->thumbnailPrefix) {
                    $fileModel->thumbnailPrefix = $this->thumbnailPrefix;
                }

                if ($this->thumbnailOptions) {
                    $fileModel->thumbnailOptions = $this->thumbnailOptions;
                }

                if ($this->thumbnailExtension) {
                    $fileModel->defaultThumbnailExtension = $this->thumbnailExtension;
                }

                if ($this->allowedExtensions) {
                    $fileModel->allowedExtensions = $this->allowedExtensions;
                }

                if (!ArrayHelper::isIn($this->uploadAlias, [File::ALIAS_WEBROOT, File::ALIAS_WEB])) {
                    $dirPath               .= 'web' . DIRECTORY_SEPARATOR;
                    $fileModel->requireWeb = true;
                } else {
                    $fileModel->requireWeb = false;
                }

                if ($uploadPath) {
                    $dirPath .= $uploadPath;
                }

                FileHelper::makeDirectory($dirPath, $this->directoryMode, true);

                if ($fileInstance->size > $this->maxSize) {
                    $formModel->addError($this->fileAttribute,
                        \Yii::t('app', 'File size is too big. Max size: {size}', [
                            'size' => FileHelper::convertToReadableSize($this->maxSize)
                        ]));
                }

                $fileModel->uploadAlias = $this->uploadAlias;
                $fileModel->uploadPath  = $uploadPath;
                $fileModel->baseUrl     = $this->baseUrl ?: \Yii::$app->urlManager->baseUrl;
                $fileModel->name        = FileHelper::slug($fileInstance->baseName) . '_' . dechex(time());
                $fileModel->size        = $fileInstance->size;
                $fileModel->extension   = $fileInstance->extension;
                $fileModel->mime        = $fileInstance->type;

                $extensionAllowed = false;
                foreach ($fileModel->allowedExtensions as $type => $extensions) {
                    if (ArrayHelper::isIn($fileModel->extension, $extensions)) {
                        $extensionAllowed = true;
                        $fileModel->type  = $type;
                        break;
                    }
                }

                if (!$extensionAllowed) {
                    $formModel->addError($this->fileAttribute, \Yii::t('app', 'Extension not supported.'));
                }

                $nameAttribute        = $this->nameAttribute ?: false;
                $informationAttribute = $this->informationAttribute ?: false;

                if ($nameAttribute) {
                    $fileModel->displayName = $formModel->$nameAttribute;
                } else {
                    $fileModel->displayName = $fileInstance->name;
                }

                if ($informationAttribute) {
                    $fileModel->additionalInformation = $formModel->$informationAttribute;
                }

                $fullPath = $dirPath . $fileModel->name . '.' . $fileModel->extension;
                $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);

                if (!$formModel->hasErrors()) {
                    if ($fileModel->validate() && $fileInstance->saveAs($fullPath) && $fileModel->save()) {
                        if ($this->createThumbnail) {
                            $fileModel->createThumbnail($this->thumbnailExtension);
                        }
                        $targetAttribute   = $this->targetAttribute;
                        $relationAttribute = $this->relationAttribute;
                        $ownerModel        = $this->ownerModel;
                        $ownerKey          = $this->ownerKey;

                        /** @var ActiveRecord $mode */
                        $childModel                     = new $this->modelClass;
                        $childModel->$targetAttribute   = $fileModel->id;
                        $childModel->$relationAttribute = $ownerModel->$ownerKey;
                        $childModel->save();

                    };
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