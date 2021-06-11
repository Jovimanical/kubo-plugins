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
 * class KuboPlugin\Properties\UserProperty
 *
 * UserProperty Visitor
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 11/06/2021 13:48
 */
class UserProperty {
	public function newProperty(array $data){
		return UserProperty\UserProperty::newProperty($data);
	}

    public function viewProperties(int $userId){
        return UserProperty\UserProperty::viewProperties($userId);
    }

    public function viewProperty(int $propertyId){
        return UserProperty\UserProperty::viewProperty($propertyId);
    }
}