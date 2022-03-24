<?php declare (strict_types = 1);
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
class UserProperty
{
    public static function newProperty(array $data)
    {
        return UserProperty\UserProperty::newProperty($data);
    }

    public static function newPropertyEstate(array $data)
    {
        return UserProperty\UserProperty::newPropertyEstate($data);
    }

    public static function newPropertyBlock(array $data)
    {
        return UserProperty\UserProperty::newPropertyBlock($data);
    }

    public static function newPropertyUnit(array $data)
    {
        return UserProperty\UserProperty::newPropertyUnit($data);
    }

    public static function newPropertyOnEntity(array $data)
    {
        return UserProperty\UserProperty::newPropertyOnEntity($data);
    }

    public static function newPropertyOnEntityBlock(array $data)
    {
        return UserProperty\UserProperty::newPropertyOnEntityBlock($data);
    }

    public static function viewProperties(int $userId)
    {
        return UserProperty\UserProperty::viewProperties($userId);
    }

    public static function viewPropertiesData(int $userId)
    {
        return UserProperty\UserProperty::viewPropertiesData($userId);
    }

    public static function viewProperty(int $propertyId)
    {
        return UserProperty\UserProperty::viewProperty($propertyId);
    }

    public static function viewPropertyData(int $propertyId)
    {
        return UserProperty\UserProperty::viewPropertyData($propertyId);
    }

    public static function listAllProperties(int $userId = 1, array $data)
    {
        return UserProperty\UserProperty::listAllProperties($userId, $data);
    }

    public static function listAllPropertiesData(int $userId = 1, array $data)
    {
        return UserProperty\UserProperty::listAllPropertiesData($userId, $data);
    }

    public static function viewPropertyByName(array $data)
    {
        return UserProperty\UserProperty::viewPropertyByName($data);
    }

    public static function viewPropertyByNameData(array $data)
    {
        return UserProperty\UserProperty::viewPropertyByNameData($data);
    }
    public static function viewPropertyChildren(int $propertyId, array $floorLevel = [])
    {
        return UserProperty\UserProperty::viewPropertyChildren($propertyId, $floorLevel);
    }

    public static function viewPropertyChildrenData(int $propertyId, array $floorLevel = [])
    {
        return UserProperty\UserProperty::viewPropertyChildrenData($propertyId, $floorLevel);
    }

    public static function getPropertyChildren(int $propertyId, array $floorLevel = [])
    {
        return UserProperty\UserProperty::getPropertyChildren($propertyId, $floorLevel);
    }

    public static function viewPropertyChildrenTest(int $propertyId, array $floorLevel = [])
    {
        return UserProperty\UserProperty::viewPropertyChildrenTest($propertyId, $floorLevel);
    }

    public static function viewPropertyMetadata(int $propertyId)
    {
        return UserProperty\UserProperty::viewPropertyMetadata($propertyId);
    }

    public static function viewPropertyMetadataSet(int $propertyId)
    {
        return UserProperty\UserProperty::viewPropertyMetadataSet($propertyId);
    }

    public static function editPropertyMetadata(int $propertyId, array $data = [])
    {
        return UserProperty\UserProperty::editPropertyMetadata($propertyId, $data);
    }

    public static function getDashBoardTotal(int $userId)
    {
        return UserProperty\UserProperty::getDashBoardTotal($userId);
    }

    public static function searchEstateClient(int $userId, array $data)
    {
        return UserProperty\UserProperty::searchEstateClient($userId, $data);
    }

    public static function addAllocation(int $userId, array $data)
    {
        return UserProperty\UserProperty::addAllocation($userId, $data);
    }

    public static function viewUnitAllocationsData(int $propertyId)
    {
        return UserProperty\UserProperty::viewUnitAllocationsData($propertyId);
    }

    public static function viewDeveloperName(int $userId)
    {
        return UserProperty\UserProperty::viewDeveloperName($userId);
    }

    public static function uploadMapData(int $userId, array $data)
    {
        return UserProperty\UserProperty::uploadMapData($userId, $data);
    }

    public static function uploadEstateData(int $userId, array $data)
    {
        return UserProperty\UserProperty::uploadEstateData($userId, $data);
    }

    public static function uploadBlockData(int $userId, array $data)
    {
        return UserProperty\UserProperty::uploadBlockData($userId, $data);
    }

    public static function uploadUnitData(int $userId, array $data)
    {
        return UserProperty\UserProperty::uploadUnitData($userId, $data);
    }

    public static function viewMapDataUploadStatus(int $userId, array $data)
    {
        return UserProperty\UserProperty::viewMapDataUploadStatus($userId, $data);
    }

    public static function getEstatePropertyTotal(int $propertyId)
    {
        return UserProperty\UserProperty::getEstatePropertyTotal($propertyId);
    }

    public static function getEstatePropertyAvailable(int $propertyId)
    {
        return UserProperty\UserProperty::getEstatePropertyAvailable($propertyId);
    }

}
