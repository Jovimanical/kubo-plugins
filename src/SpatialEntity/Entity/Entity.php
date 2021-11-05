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

namespace KuboPlugin\SpatialEntity\Entity;

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;

/**
 * class KuboPlugin\SpatialEntity\Entity\Entity
 *
 * Geospatial Objects Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 11/06/2021 09:33
 */
class Entity {
    private static function serializeObject($object){
        return serialize($object);
    }

    private static function unserializeObject($str){
        $data = html_entity_decode(unserialize($str));
        $reps = ["\n"=>"",'\\'=>"", "&#39;"=>"\""];
        foreach($reps as $dirt=>$val){
            $data = str_replace($dirt, $val, $data);
        }

        return $data;
    }

    public static function newEntity(array $data){
        $name = $data["entityName"];
        $type = $data["entityType"];
        $parentId = $data["entityParentId"] ?? null;
        $geometry = $data["entityGeometry"] ?? null;
        $description = $data["description"] ?? "";

        $inputData = [
            "EntityName"=>QB::wrapString($name, "'"),
            "EntityType"=>QB::wrapString($type, "'"),
            "EntityDescription"=>QB::wrapString($description, "'")
        ];

        if (!is_null($parentId)){
            $inputData["EntityParent"] = $parentId;
        }

        if (!is_null($geometry)){
            $inputData["EntityGeometry"] = QB::wrapString(self::serializeObject($geometry), "'");
        }

        $result = DBQueryFactory::insert("[SpatialEntities].[Entities]", $inputData, false);

        if (!$result['lastInsertId']){
            //throw an exception, insert was unsuccessful
        }

        return $result;
    }

    public static function viewEntitiesByType(array $data){
        $type = $data["entityType"];
        $query = "SELECT EntityId, EntityName, EntityType, EntityGeometry, EntityDescription, DateCreated, LastModified FROM [SpatialEntities].[Entities] WHERE EntityType = $type";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        foreach($result as $key=>$entity){
            $result[$key]["EntityGeometry"] = self::unserializeObject($entity["EntityGeometry"]);
        }

        return $result;
    }

    public static function viewEntityChildren(array $data){
        $entity = $data["entityId"];
        $query = "SELECT EntityId, EntityName, EntityType, EntityGeometry, EntityDescription, DateCreated, LastModified FROM [SpatialEntities].[Entities] WHERE EntityParent = $entity";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $children = [];
        foreach($result as $key=>$entity){
            $entity["EntityGeometry"] = self::unserializeObject($entity["EntityGeometry"]);
            $children[$entity["EntityId"]] = $entity;
        }

        var_dump("second break ".$results);

        return $children;
    }

    public static function viewEntityParent(array $data){
        $entity = $data["entityId"];
        $query = "SELECT EntityParent FROM [SpatialEntities].[Entities] WHERE EntityId = $entity";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public static function viewEntityTypes(){
        $query = "SELECT * FROM [SpatialEntities].[EntityTypes];";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public static function viewEntity(array $data){
        $entity = $data["entityId"];
        $query = "SELECT * FROM SpatialEntities.Entities WHERE EntityId = $entity";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        foreach($result as $key=>$entity){
            $result[$key]["EntityGeometry"] = self::unserializeObject($entity["EntityGeometry"]);
        }

        return $result;
    }
}