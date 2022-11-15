<?php
/**
 * Created by PhpStorm.
 * User: Nadzif Glovory
 * Date: 11/26/2019
 * Time: 3:34 PM
 */

namespace nadzif\behaviors\models;


use nadzif\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\imagine\Image;

/**
 * Class File
 *
 * @package nadzif\behaviors\models
 * @property integer $id
 * @property string  $type
 * @property string  $displayName
 * @property string  $requireWeb
 * @property string  $uploadAlias
 * @property string  $uploadPath
 * @property string  $baseUrl
 * @property string  $name
 * @property double  $size
 * @property string  $extension
 * @property string  $mime
 * @property boolean $hasThumbnail
 * @property string  $thumbnailName
 * @property double  $thumbnailSize
 * @property string  $thumbnailExtension
 * @property string  $thumbnailMime
 * @property string  $additionalInformation
 * @property string  $createdAt
 * @property string  $updatedAt
 *
 * @property string  $directoryPath
 * @property string  $fileName
 * @property string  $thumbnailFileName
 * @property string  $thumbnailLocation
 * @property string  $location
 * @property string  $thumbnailUrl
 * @property string  $url
 * @property string  $readableSize
 * @property string  $base64
 */
class File extends ActiveRecord
{
    const TYPE_IMAGE    = 'image';
    const TYPE_DOCUMENT = 'document';
    const TYPE_VIDEO    = 'video';
    const TYPE_AUDIO    = 'audio';
    const TYPE_OTHER    = 'other';

    // list of all (basic, advanced) application
    const ALIAS_APP     = '@app';
    const ALIAS_VENDOR  = '@vendor';
    const ALIAS_RUNTIME = '@runtime';
    const ALIAS_WEB     = '@web';
    const ALIAS_WEBROOT = '@webroot';
    const ALIAS_TESTS   = '@tests';

    // list of advanced application
    const ALIAS_API      = '@api';
    const ALIAS_COMMON   = '@common';
    const ALIAS_BACKEND  = '@backend';
    const ALIAS_FRONTEND = '@frontend';
    const ALIAS_CONSOLE  = '@console';

    public $allowedExtensions = [
        self::TYPE_IMAGE    => ['jpg', 'jpeg', 'png'],
        self::TYPE_DOCUMENT => ['txt', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'],
        self::TYPE_VIDEO    => ['mp4', 'wmv', 'mpg', 'mpeg'],
        self::TYPE_AUDIO    => ['mp3', 'wav'],
        self::TYPE_OTHER    => [],
    ];

    public $deleteRelatedFiles        = true;
    public $defaultThumbnailExtension = 'jpg';
    public $thumbnailPrefix           = '_thumb';
    public $thumbnailOptions          = [
        'width'         => 320,
        'height'        => 240,
        'quality'       => 100,
        'pageIndex'     => 0,// for document thumbnail captured page (start from 0)
        'videoDuration' => false,// for video thumbnail captured frame (in seconds); false for capture middle content
    ];

    public static function tableName()
    {
        return '{{%file}}';
    }

    public function behaviors()
    {
        return [
            'timestampBehavior' => ['class' => TimestampBehavior::class],
            'setType'           => [
                'class'      => AttributeBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'type',
                ],
                'value'      => function ($event) {
                    $fileType = null;
                    foreach ($this->allowedExtensions as $type => $extensions) {
                        if (ArrayHelper::isIn($this->extension, $extensions)) {
                            $fileType = $type;
                            break;
                        }
                    }

                    return $fileType;
                }
            ]
        ];
    }

    function getReadableSize($precision = 2)
    {
        $size = $this->size;

        $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $step  = 1024;
        $i     = 0;
        while (($size / $step) > 0.9) {
            $size = $size / $step;
            $i++;
        }
        return round($size, $precision) . $units[$i];
    }

    public function getFileName()
    {
        return $this->name . '.' . $this->extension;
    }

    public function getThumbnailFileName()
    {
        return $this->hasThumbnail ? $this->thumbnailName . '.' . $this->thumbnailExtension : false;
    }

    public function getThumbnailUrl()
    {
        $thumbUrl = $this->baseUrl;
        if (strlen($this->uploadPath)) {
            $thumbUrl .= '/' . $this->uploadPath;
        }

        return $thumbUrl . $this->thumbnailFileName;
    }

    public function getUrl()
    {
        $url = $this->baseUrl;
        if (strlen($this->uploadPath)) {
            $url .= '/' . $this->uploadPath;
        }
        return $url . $this->fileName;
    }

    public function delete()
    {
        if ($this->deleteRelatedFiles) {
            $this->removeThumbnail();
            $this->removeFile();
        }

        return parent::delete();
    }

    protected function removeThumbnail()
    {
        if ($this->hasThumbnail && file_exists($this->thumbnailLocation)) {
            unlink($this->thumbnailLocation);

            $this->hasThumbnail       = false;
            $this->thumbnailName      = null;
            $this->thumbnailExtension = null;
            $this->thumbnailMime      = null;
            $this->thumbnailSize      = null;

            $this->save();
            $this->refresh();
        }

        return true;
    }

    protected function removeFile()
    {
        if (file_exists($this->location)) {
            unlink($this->location);
        }

        return true;
    }

    public function createThumbnail()
    {
        $thumbnailExtension = $this->defaultThumbnailExtension;
        $thumbnailFilename  = $this->name . $this->thumbnailPrefix;

        $fileLocation      = $this->location;
        $thumbnailLocation = $this->directoryPath . $thumbnailFilename . '.' . $thumbnailExtension;

        $thumbnailExist = $this->hasThumbnail;

        if ($this->type == self::TYPE_IMAGE) {
            $success = $this->createThumbnailImage($fileLocation, $thumbnailLocation, $this->thumbnailOptions);
        } elseif ($this->type == self::TYPE_DOCUMENT) {
            $success = $this->createThumbnailDocument($fileLocation, $thumbnailLocation, $this->thumbnailOptions);
        } elseif ($this->type == self::TYPE_AUDIO) {
            $success = false; // TODO search method for make audio thumnail
        } elseif ($this->type == self::TYPE_VIDEO) {
            $success = $this->createThumbnailVideo($fileLocation, $thumbnailLocation, $this->thumbnailOptions);
        } else {
            $success = false; // TODO search for other unsupported file thumbnail
        }

        if ($success) {
            if ($thumbnailExist) {
                $this->removeThumbnail();
            }
            $this->hasThumbnail       = true;
            $this->thumbnailName      = $thumbnailFilename;
            $this->thumbnailExtension = $thumbnailExtension;
            $this->thumbnailSize      = filesize($thumbnailLocation);
            $this->thumbnailMime      = mime_content_type($thumbnailLocation);
            $this->save();
            $this->refresh();
        }
    }

    private function createThumbnailImage($source, $destination, $options)
    {
        Image::thumbnail(
            $source,
            ArrayHelper::getValue($options, 'width', 100),
            ArrayHelper::getValue($options, 'height', 100))
            ->save($destination, ['quality' => ArrayHelper::getValue($options, 'quality', 50)]);

        return file_exists($destination);
    }

    private function createThumbnailDocument($source, $destination, $options)
    {
        try {
            $imagick = new \Imagick($source . '[' . ArrayHelper::getValue($options, 'pageIndex', 0) . ']');
            $imagick->setImageFormat($this->defaultThumbnailExtension);
            $imagick->setImageColorspace(255);
            $imagick->thumbnailImage(
                ArrayHelper::getValue($options, 'width', 100),
                ArrayHelper::getValue($options, 'height', 100)
            );

            return $imagick->writeImage($destination) && $imagick->clear() && $imagick->destroy();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function createThumbnailVideo($source, $destination, $options)
    {
        try {
            $ffmpeg = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'ffmpeg.exe' : 'ffmpeg';

            $capturedInterval = ArrayHelper::getValue($options, 'videoDuration', false);

            if ($capturedInterval === false) {
                $time = exec($ffmpeg . " -i $source 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");

                $duration          = explode(":", $time);
                $durationInSeconds = $duration[0] * 3600 + $duration[1] * 60 + round($duration[2]);
                $minutes           = ($durationInSeconds / 2) / 60;
                $realMinutes       = floor($minutes);
                $realSeconds       = round(($minutes - $realMinutes) * 60);
            } else {
                $realSeconds = $capturedInterval;
            }

            $thumbnailSize = ArrayHelper::getValue($options, 'width', 100) . 'x'
                . ArrayHelper::getValue($options, 'height', 100);
            $cmd           = $ffmpeg . " -i \"" . $source . "\" -deinterlace -an -ss " . $realSeconds
                . " -f mjpeg -t 1 -r 1 -y -s " . $thumbnailSize . " \"" . $destination . "\" 2>&1";

            exec($cmd);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getDirectoryPath()
    {
        $directory = \Yii::getAlias($this->uploadAlias) . DIRECTORY_SEPARATOR;
        $directory .= $this->requireWeb ? 'web' . DIRECTORY_SEPARATOR : "";
        $directory .= $this->uploadPath;
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $directory);
    }

    protected function getThumbnailLocation()
    {
        return $this->hasThumbnail ? $this->directoryPath . $this->thumbnailFileName : false;
    }

    protected function getLocation()
    {
        return $this->directoryPath . $this->fileName;
    }

    public function getBase64()
    {
        try {
            $path = $this->location;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        } catch (\Exception $e) {
            return null;
        }
    }
}