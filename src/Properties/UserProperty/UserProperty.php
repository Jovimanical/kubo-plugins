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

namespace KuboPlugin\Properties\UserProperty;

/**
 * class KuboPlugin\Properties\UserProperty
 *
 * UserProperty Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 11/06/2021 13:50
 */
class UserProperty {
	public static function newProperty(array $data){
        $user = $data["user"];
        $metadata = $data["property_metadata"] ?? [];
        $title =  $data["property_title"];
        $geometry = $data["property_geometry"] ?? null;
        $parent = $data["property_parent"] ?? null;
        $type = $data["property_type"];

        //STEP 1: Index Spatial Entity
        $entity = [
            "entityName"=>$title,
            "entityType"=>$type,
            "entityParentId"=>$parent,
            "entityGeometry"=>$geometry
        ];

        $indexEntityResult = \KuboPlugin\SpatialEntity\Entity\Entity::newEntity($entity);
        $entityId = $indexEntityResult["lastInsertId"];

        //STEP 2: Index User Property
        $inputData = [
            "UserId"=>$user,
            "LinkedEntity"=>$entityId,
            "PropertyTitle"=>QB::wrapString($title, "'")
        ];
        $result = DBQueryFactory::insert("[Properties].[UserProperty]", $inputData, false);

        $propertyId = $result["lastInsertId"];

        //STEP 3: Index Metadata
        $values = [];
        foreach ($metadata as $key => $value) {
            $values[]  .= "($propertyId, '$key', '$value')";
        }

        $query = "INSERT INTO Properties.UserPropertyMetadata (PropertyId, FieldName, FieldValue) VALUES ". implode(",", $values);

        $result = DBConnectionFactory::getConnection()->query($query);
        
        return $result;
	}

    public static function viewProperties(int $userId){
        return UserProperty\UserProperty::getObject($objectId);
    }
}