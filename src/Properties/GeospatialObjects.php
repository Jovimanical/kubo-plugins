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

namespace KuboPlugin\Properties;

/**
 * class KuboPlugin\Properties\GeospatialObjects
 *
 * GeospatialObjects Visitor
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 11/06/2021 09:11
 */
class GeospatialObjects {
	public function newObject(array $data){
		return GeospatialObjects\GeospatialObjects::newObject($data);
	}

    public function getObject(int $objectId){
        return GeospatialObjects\GeospatialObjects::getObject($objectId);
    }
}