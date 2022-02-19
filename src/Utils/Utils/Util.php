<?php declare (strict_types = 1);
/**
 * Controller Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\Utils\Utils;

/**
 * class KuboPlugin\Utils\Utils
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
        return strtolower(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', $sc, $string));
    }

    public static function isJSON(String $stringData)
    {
        $string = str_replace('&#39;', '"', $stringData);
        $string = str_replace('&#34;', '"', $stringData);
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }

    public static function clientRequest(String $url, String $method = 'GET', array $data = [], String $header = "")
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        if($header != ""){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
        }

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        }

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;

    }
}
