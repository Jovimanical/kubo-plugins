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
    public static function newProperty(array $data){
        return UserProperty\UserProperty::newProperty($data);
    }

    public static function newPropertyOnEntity(array $data){
        return UserProperty\UserProperty::newPropertyOnEntity($data);
    }

    public static function viewProperties(int $userId){
        return UserProperty\UserProperty::viewProperties($userId);
    }

    public static function viewProperty(int $propertyId){
        return UserProperty\UserProperty::viewProperty($propertyId);
    }

    public static function viewPropertyByName(array $data){
        return UserProperty\UserProperty::viewPropertyByName($data);
    }

    public static function viewPropertyChildren(int $propertyId, array $floorLevel = []){
        return UserProperty\UserProperty::viewPropertyChildren($propertyId, $floorLevel);
    }

    public static function viewPropertyMetadata(int $propertyId){
        return UserProperty\UserProperty::viewPropertyMetadata($propertyId);
    }

    public static function editPropertyMetadata(int $propertyId, array $data = []){
        return UserProperty\UserProperty::editPropertyMetadata($propertyId, $data);
    }

    public static function addNewProperty(array $data){
        $UserId = $data["UserId"];
        $PropertyTitle = $data["PropertyTitle"] ?? [];
        $LinkedEntity =  $data["LinkedEntity"];
        $DateCreated = $data["DateCreated"] ?? null;
        $PropertyId = $data["PropertyId"] ?? null;
        $PropertyFloor = $data["PropertyFloor"];

        //STEP 2: Index User Property
        $inputData = [
            "UserId"=>$user,
            "LinkedEntity"=>$entityId,
            "PropertyTitle"=>$title,
            "DateCreated"=>$DateCreated,
            "PropertyId"=>$property,
            "PropertyFloor"=>$PropertyFloor
        ];
        $result = DBQueryFactory::insert("[Properties].[UserProperty]", $inputData, false);
        
        return $result;
    }
}