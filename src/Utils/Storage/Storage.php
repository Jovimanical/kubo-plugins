<?php declare (strict_types = 1);
/**
 * Controller Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 *
 */

namespace KuboPlugin\Utils\Storage;

use EmmetBlue\Core\Constant;

/**
 * class KuboPlugin\Utils\Storage
 *
 * Storage Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 28/11/2021 11:57
 */
class Storage
{
    protected static function saveToFileServerPath($fileName, $content){
        $fileServerPath = Constant::getGlobals()["file-server-path"];
       // if(!imagecreatefrompng($fileServerPath.DIRECTORY_SEPARATOR.$fileName)){
       //     return false;
       // }

        return file_put_contents($fileServerPath.DIRECTORY_SEPARATOR.$fileName, $content);
    }

    public static function storeBase64(array $data)
    {
        //Save a base64 string in solution storage bucket and return an array [status: true, ref: unique_ref] to the saved object.
        $string = $data["object"] ?? "";

        if (empty($string)) {
            return [
                "status" => false,
                "message" => "Object is required",
            ];
        }

        $ref = md5(uniqid());

        $result = self::saveToFileServerPath($ref, $string);

        if (!$result) {
            return [
                "status" => false,
                "message" => "Failed to save image",
            ];
        }

        return ["status" => true, "ref" => $ref];
    }

    public static function readBase64(array $data){
        $fileName = $data["file"] ?? "";
        $contents = file_get_contents(Constant::getGlobals()["file-server-path"].DIRECTORY_SEPARATOR.$fileName);

        return ["contents"=>$contents];
    }
}