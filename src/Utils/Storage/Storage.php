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
        //Save a base64 string in solution storage bucket and return an array [status: true, ref: unique_ref] to the saved object.

        return ["status"=>true, "ref":uniqid()];
    }
}