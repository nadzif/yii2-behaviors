<?php
/**
 * Created by PhpStorm.
 * User: Nadzif Glovory
 * Date: 11/28/2019
 * Time: 9:53 PM
 */

namespace nadzif\behaviors\helpers;


class FileHelper
{
    public static function convertToReadableSize($size)
    {
        $base   = log($size) / log(1024);
        $suffix = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $f_base = floor($base);
        return round(pow(1024, $base - floor($base)), 1) . $suffix[$f_base];
    }

    public static function slug($text, $length = null)
    {
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = strtolower($text);
        $text = preg_replace('~[^-\w]+~', '', $text);

        if ($length) {
            return substr($text, 0, $length);
        } else {
            return $text;
        }
    }

    public static function makeDirectory($path, $mode, $recursive, $gitignore = true)
    {
        if(!is_dir($path)){
            mkdir($path, $mode, $recursive);

            if ($gitignore && !file_exists($path  . ".gitignore")) {
                $ignoreFile = fopen($path  . ".gitignore", "w") or die();
                fwrite($ignoreFile, "*\n!.gitignore");
                fclose($ignoreFile);
            }
        }
    }
}