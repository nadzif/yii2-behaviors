<?php
/**
 * Created by PhpStorm.
 * User: Nadzif Glovory
 * Date: 7/25/2018
 * Time: 1:13 PM
 */

namespace nadzif\behaviors\helpers;

/**
 * Class IdentityHelper
 *
 * @package nadzif\behaviors\helpers
 */
class IdentityHelper
{
    /**
     * @return int|null|string
     */
    public static function getUserId()
    {
        if (!\Yii::$app->request->isConsoleRequest && !\Yii::$app->user->isGuest) {
            return \Yii::$app->user->id;
        } else {
            return null;
        }
    }

    /**
     * @return string|bool
     */
    public static function getHostName()
    {
        return gethostname() ?: false;
    }

    /**
     * @return bool|mixed|null|string
     */
    public static function getHostInfo()
    {
        return !\Yii::$app->request->isConsoleRequest && \Yii::$app->request->hostInfo ?
            \Yii::$app->request->hostInfo : false;
    }

    /**
     * @return bool|int|mixed
     */
    public static function getPortRequest()
    {
        return !\Yii::$app->request->isConsoleRequest && \Yii::$app->request->port ?
            \Yii::$app->request->port : false;
    }

    /**
     * @return bool|mixed|string
     */
    public static function getUrl()
    {
        return !\Yii::$app->request->isConsoleRequest && \Yii::$app->request->url ?
            \Yii::$app->request->url : false;
    }

    /**
     * @return bool|mixed|null|string
     */
    public static function getUserHost()
    {
        return !\Yii::$app->request->isConsoleRequest && \Yii::$app->request->userHost ?
            \Yii::$app->request->userHost : false;
    }

    /**
     * @return bool|mixed|null|string
     */
    public static function getUserIP()
    {
        return !\Yii::$app->request->isConsoleRequest && \Yii::$app->request->userIP ?
            \Yii::$app->request->userIP : false;
    }

    /**
     * @return bool|mixed|null|string
     */
    public static function getUserAgent()
    {
        return !\Yii::$app->request->isConsoleRequest && \Yii::$app->request->userAgent ?
            \Yii::$app->request->userAgent : false;
    }

    /**
     * @return array|false|string
     */
    public static function getClientIP()
    {
        $ipAddress = '';
        if (getenv('HTTP_CLIENT_IP')) {
            $ipAddress = getenv('HTTP_CLIENT_IP');
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ipAddress = getenv('HTTP_X_FORWARDED_FOR');
            } else {
                if (getenv('HTTP_X_FORWARDED')) {
                    $ipAddress = getenv('HTTP_X_FORWARDED');
                } else {
                    if (getenv('HTTP_FORWARDED_FOR')) {
                        $ipAddress = getenv('HTTP_FORWARDED_FOR');
                    } else {
                        if (getenv('HTTP_FORWARDED')) {
                            $ipAddress = getenv('HTTP_FORWARDED');
                        } else {
                            if (getenv('REMOTE_ADDR')) {
                                $ipAddress = getenv('REMOTE_ADDR');
                            } else {
                                $ipAddress = 'UNKNOWN';
                            }
                        }
                    }
                }
            }
        }
        return $ipAddress;
    }


}