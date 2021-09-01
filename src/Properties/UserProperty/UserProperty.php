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

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;

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

        $result = DBConnectionFactory::getConnection()->exec($query);
        
        return $result;
    }

    public static function viewProperties(int $userId){
        $query = "SELECT a.* FROM Properties.UserProperty a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.UserId = $userId AND b.EntityParent IS NULL";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $key=>$property){
            $result[$key]["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId"=>$property["LinkedEntity"]]);
            $result[$key]["Metadata"] = self::viewPropertyMetadata((int)$property["PropertyId"]);
        }

        return $result;
    }

    public static function viewProperty(int $propertyId){
        $query = "SELECT * FROM Properties.UserProperty WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $result = $result[0] ?? [];
        if (count($result) > 0){
            $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId"=>$result["LinkedEntity"]]);
            $result["Metadata"] = self::viewPropertyMetadata((int)$result["PropertyId"]);
        }

        return $result;
    }

    public static function viewPropertyByName(array $data){
        $name = $data["name"] ?? 0;
        $query = "SELECT * FROM Properties.UserProperty WHERE PropertyTitle = '$name'";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $result = $result[0] ?? [];
        if (count($result) > 0){
            $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId"=>$result["LinkedEntity"]]);
            $result["Metadata"] = self::viewPropertyMetadata((int)$result["PropertyId"]);
        }

        return $result;
    }

    public static function viewPropertyChildren(int $propertyId){
        $query = "SELECT a.*, b.EntityParent FROM Properties.UserProperty a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE b.EntityParent = (SELECT LinkedEntity FROM Properties.UserProperty WHERE PropertyId = $propertyId)";
        $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if (isset($results[0])){
            $propertyId = $results[0]["EntityParent"];
        }
        
        $propertyChildren = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntityChildren(["entityId"=>$propertyId]);

        $childrenMetadata = self::viewPropertyChildrenMetadata((int)$propertyId);

        foreach ($results as $key=>$result){
            $results[$key]["Entity"] = $propertyChildren[$result["LinkedEntity"]] ?? [];
            $results[$key]["Metadata"] = $childrenMetadata[$result["PropertyId"]] ?? [];
        }

        return $results;
    }

    public static function viewPropertyMetadata(int $propertyId){
        $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $propertyParentQuery = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata
                                WHERE PropertyId = (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity = (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN 
                                SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE PropertyId=$propertyId))";
        $propertyParentResult = DBConnectionFactory::getConnection()->query($propertyParentQuery)->fetchAll(\PDO::FETCH_ASSOC);

        $metadata = [];
        foreach ($result as $key=>$value){
            $metadata[$value["FieldName"]] = ["FieldValue"=>$value["FieldValue"], "MetadataId"=>$value["MetadataId"]];
        }

        foreach ($propertyParentResult as $key => $value){
            if (!isset($metadata[$value["FieldName"]])){
                $metadata[$value["FieldName"]] = ["FieldValue"=>$value["FieldValue"], "MetadataId"=>$value["MetadataId"]];
            }
        }

        return $metadata;
    }

    public static function viewPropertyChildrenMetadata(int $parentId){
        $query = "SELECT a.* FROM Properties.UserPropertyMetadata a 
                    INNER JOIN Properties.UserProperty b ON a.PropertyId = b.PropertyId
                    INNER JOIN SpatialEntities.Entities c ON b.LinkedEntity = c.EntityId
                    WHERE c.EntityParent = $parentId";

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $propertyParentQuery = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $parentId";
        $propertyParentResult = DBConnectionFactory::getConnection()->query($propertyParentQuery)->fetchAll(\PDO::FETCH_ASSOC);

        $metadata = [];

        foreach ($result as $key=>$value){
            if (!isset($metadata[$value["PropertyId"]])){
                $propertyId = $value["PropertyId"];
                $metadata[$value["PropertyId"]] = [];
            }

            $metadata[$value["PropertyId"]][$value["FieldName"]] = ["FieldValue"=>$value["FieldValue"], "MetadataId"=>$value["MetadataId"]];

            foreach ($propertyParentResult as $key => $value){
                if (!isset($metadata[$propertyId][$value["FieldName"]])){
                    $metadata[$propertyId][$value["FieldName"]] = ["FieldValue"=>$value["FieldValue"], "MetadataId"=>$value["MetadataId"]];
                }
            }
        }


        return $metadata;
    }

    public static function editPropertyMetadata(int $propertyId, array $metadata = []){
        $queries = [];
        foreach($metadata as $key=>$value){
            if (is_array($value)){
                $value = json_encode($value);
            }
            
            $queries[] = "BEGIN TRANSACTION;".
                        "UPDATE Properties.UserPropertyMetadata SET FieldValue='$value' WHERE FieldName='$key' AND PropertyId=$propertyId; ".
                        "IF @@ROWCOUNT = 0 BEGIN INSERT INTO Properties.UserPropertyMetadata (PropertyId, FieldName, FieldValue) VALUES ($propertyId, '$key', '$value') END;".
                        "COMMIT TRANSACTION;";


        }

        $query = implode(";", $queries);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;
    }
}