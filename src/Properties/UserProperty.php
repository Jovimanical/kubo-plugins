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

    public static function newPropertyEstate(array $data)
    {
        return UserProperty\UserProperty::newPropertyEstate($data);
    }

    public static function newPropertyEstateLong(array $data)
    {
        return UserProperty\UserProperty::newPropertyEstateLong($data);
    }

    public static function newPropertyBlock(array $data)
    {
        return UserProperty\UserProperty::newPropertyBlock($data);
    }

    public static function newPropertyBlockerLong(array $data)
    {
        return UserProperty\UserProperty::newPropertyBlockerLong($data);
    }

    public static function newPropertyBlockerExtraLong(array $data)
    {
        return UserProperty\UserProperty::newPropertyBlockerExtraLong($data);
    }

    public static function newPropertyUnit(array $data)
    {
        return UserProperty\UserProperty::newPropertyUnit($data);
    }

    public static function newPropertyUniterLong(array $data)
    {
        return UserProperty\UserProperty::newPropertyUniterLong($data);
    }

    public static function newPropertyUniterExtraLong(array $data)
    {
        return UserProperty\UserProperty::newPropertyUniterExtraLong($data);
    }

    public static function addBlockGeojson(array $data)
    {
        return UserProperty\UserProperty::addBlockGeojson($data);
    }

    public static function addBlockChildrenGeojsons(array $data)
    {
        return UserProperty\UserProperty::addBlockChildrenGeojsons($data);
    }

    public static function addUnitGeojson(array $data)
    {
        return UserProperty\UserProperty::addUnitGeojson($data);
    }

    public static function editEstateGeojson(array $data)
    {
        return UserProperty\UserProperty::editEstateGeojson($data);
    }

    public static function editBlockGeojson(array $data)
    {
        return UserProperty\UserProperty::editBlockGeojson($data);
    }

    public static function editUnitGeojson(array $data)
    {
        return UserProperty\UserProperty::editUnitGeojson($data);
    }


    public static function newPropertyOnEntity(array $data)
    {
        return UserProperty\UserProperty::newPropertyOnEntity($data);
    }

    public static function viewProperties(int $userId)
    {
        return UserProperty\UserProperty::viewProperties($userId);
    }

    public static function viewProperty(int $propertyId, array $data)
    {
        return UserProperty\UserProperty::viewProperty($propertyId, $data);
    }

    public static function listAllProperties(int $userId = 1, array $data)
    {
        return UserProperty\UserProperty::listAllProperties($userId, $data);
    }

    public static function viewPropertyByName(array $data)
    {
        return UserProperty\UserProperty::viewPropertyByName($data);
    }

    public static function viewPropertyChildren(int $propertyId, array $floorLevel = [])
    {
        return UserProperty\UserProperty::viewPropertyChildren($propertyId, $floorLevel);
    }

    public static function getPropertyChildren(int $propertyId, array $floorLevel = [])
    {
        return UserProperty\UserProperty::getPropertyChildren($propertyId, $floorLevel);
    }

    public static function viewPropertyMetadata(int $propertyId, array $data)
    {
        return UserProperty\UserProperty::viewPropertyMetadata($propertyId, $data);
    }

    public static function viewPropertyMetadataTester(int $propertyId, array $data)
    {
        return UserProperty\UserProperty::viewPropertyMetadataTester($propertyId, $data);
    }

    public static function editPropertyMetadataEstateTester(int $propertyId, array $data = [])
    {
        return UserProperty\UserProperty::editPropertyMetadataEstateTester($propertyId, $data);
    }

    public static function editPropertyMetadataEstate(int $propertyId, array $data = [])
    {
        return UserProperty\UserProperty::editPropertyMetadataEstate($propertyId, $data);
    }

    public static function editPropertyMetadataBlock(int $propertyId, array $data = [])
    {
        return UserProperty\UserProperty::editPropertyMetadataBlock($propertyId, $data);
    }

    public static function editPropertyMetadataUnit(int $propertyId, array $data = [])
    {
        return UserProperty\UserProperty::editPropertyMetadataUnit($propertyId, $data);
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

    public static function deleteUploadData(int $userId, array $data)
    {
        return UserProperty\UserProperty::deleteUploadData($userId, $data);
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
