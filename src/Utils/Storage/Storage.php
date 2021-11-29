<?php declare (strict_types=1);
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

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;

/**
 * class KuboPlugin\Utils\Storage
 *
 * Storage Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 28/11/2021 11:57
 */
class Storage {
    public static function storeBase64(array $data){
        try {

        
        //Save a base64 string in solution storage bucket and return an array [status: true, ref: unique_ref] to the saved object.
        $object = $data["object"] ?? "";

        if(empty($object)){
            return [
                "status" => false,
                "message" => "Object is required"
            ];
        }

        $fileName = time()."_".uniqid().".png";
        $filePath = "/var/www/html/kubo-core/";

        $ref = self::base64ToImg($object,$filePath,$fileName);

        if(!$ref){
            return [
                "status" => false,
                "message" => "Failed to save image"
            ];
        }

       // var_dump($ref);

        return ["status"=>true, "ref"=>$ref];

        } catch(\Exception $e){
          return ["status"=>false, "message"=>$e->getMessage()];
        }

    }
    
    }

    public static function base64ToImg($base64String, $filePath, $outputFile) {
        // check for dir
        $filer = $filePath."uploads/".$outputFile;

        $dirname = dirname($filer);
        if (!is_dir($dirname))
        {
            mkdir($dirname, 0755, true);
        }
        // open file for writing
        $imgStringFile = fopen( $filePath."uploads/".$outputFile, 'w' );

        

        // split the string on commas
        $dataImg = explode( ',', $base64String );

        $writer = fwrite( $imgStringFile, base64_decode( $dataImg[1] ) );


        // clean up the file resource
        fclose($imgStringFile);

        if(!$writer){
            return false;
        }

        return "uploads/".$outputFile;
    }
}