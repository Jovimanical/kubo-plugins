<?php declare (strict_types = 1);
/**
 * Visitor Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\Utils;

/**
 * class KuboPlugin\Utils
 *
 * Util Controller
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 09/12/2021 11:44
 */
class Util
{
    public static function camelToSnakeCase(String $string, String $sc = "_")
    {
        return Utils\Util::camelToSnakeCase($string, $sc = "_");
    }

    public static function isJSON(String $stringData)
    {
        return Utils\Util::isJSON($stringData);
    }

    public static function clientRequest(String $url, String $method = 'GET', array $data = [], String $header = "")
    {
        return Utils\Util::clientRequest($url, $method, $data, $header);
    }

    public static function recurseRmdir($dir)
    {
        return Utils\Util::recurseRmdir($dir);
    }

    public static function sendNota($token, $apikey, $title, $message, $channelId = 100, $data = [] )
    {
        return Utils\Util::sendNota($token,$apikey,$title,$message,$channelId,$data);
    }

    
    public static function checkAuthorization()
    {
        return Utils\Util::checkAuthorization();
    }

    public static function serializeObject($object)
    {
        return Utils\Util::serializeObject($object);
    }

    public static function unserializeObject($str)
    {
        return Utils\Util::unserializeObject($str);
    }
}
