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

use EmmetBlue\Core\Constant;

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
}