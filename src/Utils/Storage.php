<?php declare (strict_types=1);
/**
 * Visitor Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 *
 */

namespace KuboPlugin\Utils;

/**
 * class KuboPlugin\Utils\Storage
 *
 * Storage Visitor
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 28/11/2021 11:55
 */
class Storage {
    public static function storeBase64(array $data){
        return Storage\Storage::storeBase64($data);
    }
    public static function readBase64(array $data){
        return Storage\Storage::readBase64($data);
    }
}