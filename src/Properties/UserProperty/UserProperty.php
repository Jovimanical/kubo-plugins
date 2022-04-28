<?php declare (strict_types = 1);
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

use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;
use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;

/**
 * class KuboPlugin\Properties\UserProperty
 *
 * UserProperty Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 11/06/2021 13:50
 */
class UserProperty
{

    // Redesigned newProperty 1
    public static function newPropertyEstate(array $data)
    {
        // collecting parameters
        $user = $data["user"];
        $metadata = $data["property_metadata"] ?? [];
        $title = $data["property_title"];
        $geometry = $data["property_geometry"] ?? null;
        $parent = $data["property_parent"] ?? null;
        $type = $data["property_type"];
        $propertyUUID = str_replace(".", "z", uniqid(uniqid(), true));

        if (self::isJSON($metadata)) { // checking for json data and converting to array
            if (is_string($metadata)) {
                $metadata = str_replace('&#39;', '"', $metadata);
                $metadata = str_replace('&#34;', '"', $metadata);
                $metadata = html_entity_decode($metadata);
                $metadata = json_decode($metadata, true);
            }

        }

        // return $metadata;

        //STEP 1: Index Spatial Entity
        $entity = [
            "entityName" => $title,
            "entityType" => $type,
            "entityParentId" => $parent,
            "entityGeometry" => $geometry,
        ];

        // Getting entity data
        $indexEntityResult = \KuboPlugin\SpatialEntity\Entity\Entity::newEntity($entity);
        $entityId = $indexEntityResult["lastInsertId"];

        //STEP 2: Index User Property
        $inputData = [
            "UserId" => $user,
            "LinkedEntity" => $entityId,
            "PropertyTitle" => QB::wrapString($title, "'"),
            "PropertyUUID" => QB::wrapString($propertyUUID, "'"),
        ];
        $result = DBQueryFactory::insert("[Properties].[UserProperty]", $inputData, false);

        $propertyId = $result["lastInsertId"];

        $metadata["property_floor_count"] = 1;

        //STEP 3: Index Metadata
        $values = [];
        foreach ($metadata as $key => $value) {
            if (is_array($values)) {
                $value = json_encode($value);
            }
            $values[] = "($propertyId, '$key', '$value')";
        }

        $query = "INSERT INTO Properties.UserPropertyMetadata (PropertyId, FieldName, FieldValue) VALUES " . implode(",", $values);

        $result = DBConnectionFactory::getConnection()->exec($query);

        $resultData['EstateId'] = $propertyId;
        $resultData['EntityId'] = $entityId;
        $resultData['result'] = $result;

        return $resultData;
    }

    // Redesigned newProperty 1
    public static function newPropertyEstateTest(array $data)
    {
        // collecting parameters
        $user = $data["user"];
        $metadata = $data["property_metadata"] ?? [];
        $title = $data["property_title"];
        $geometry = $data["property_geometry"] ?? null;
        $parent = $data["property_parent"] ?? null;
        $type = $data["property_type"];
        $propertyUUID = str_replace(".", "z", uniqid(uniqid(), true));

        if (self::isJSON($metadata)) { // checking for json data and converting to array
            if (is_string($metadata)) {
                $metadata = str_replace('&#39;', '"', $metadata);
                $metadata = str_replace('&#34;', '"', $metadata);
                $metadata = html_entity_decode($metadata);
                $metadata = json_decode($metadata, true);
            }

        }

        //STEP 2: Index User Property
        $inputData = [
            "UserId" => $user,
            "LinkedEntity" => (int) time(),
            "PropertyTitle" => QB::wrapString($title, "'"),
            "PropertyUUID" => QB::wrapString($propertyUUID, "'"),
            "PropertyFloorCount" => 1,
            "EntityGeometry" => QB::wrapString($geometry, "'"),
        ];
        $result = DBQueryFactory::insert("[Properties].[UserProperty]", $inputData, false);

        $propertyId = $result["lastInsertId"];

        $metadata["property_floor_count"] = 1;

        //STEP 3: Index Metadata
        $values = [];
        foreach ($metadata as $key => $value) {
            if (is_array($values)) {
                $value = json_encode($value);
            }
            $values[] = "($propertyId, '$key', '$value')";
        }

        $query = "INSERT INTO Properties.UserPropertyMetadata (PropertyId, FieldName, FieldValue) VALUES " . implode(",", $values);

        $result = DBConnectionFactory::getConnection()->exec($query);

        $resultData['EstateId'] = $propertyId;
        $resultData['result'] = $result;

        return $resultData;
    }

    // Redesigned newProperty 2
    public static function newPropertyBlock(array $data)
    {
        // collecting parameters
        $user = $data["user"];
        $metadata = $data["property_metadata"] ?? [];
        $title = $data["property_title"];
        $estateId = $data["property_estate_id"];
        $geometry = $data["property_geometry"] ?? null;
        $parent = $data["property_parent"] ?? null;
        $type = $data["property_type"];
        $propertyUUID = str_replace(".", "z", uniqid(uniqid(), true));

        if (self::isJSON($metadata)) { // checking for json data and converting to array
            if (is_string($metadata)) {
                $metadata = str_replace('&#39;', '"', $metadata);
                $metadata = str_replace('&#34;', '"', $metadata);
                $metadata = html_entity_decode($metadata);
                $metadata = json_decode($metadata, true);
            }

        }

        // return $metadata;

        //STEP 1: Index Spatial Entity
        $entity = [
            "entityName" => $title,
            "entityType" => $type,
            "entityParentId" => $parent,
            "entityGeometry" => $geometry,
            "entityEstate" => $estateId,
        ];

        // Getting entity data
        $indexEntityResult = \KuboPlugin\SpatialEntity\Entity\Entity::newEntity($entity);
        $entityId = $indexEntityResult["lastInsertId"];

        //STEP 2: Index User Property
        $inputData = [
            "UserId" => $user,
            "LinkedEntity" => $entityId,
            "PropertyTitle" => QB::wrapString($title, "'"),
            "PropertyUUID" => QB::wrapString($propertyUUID, "'"),
            "PropertyEstate" => $estateId,
        ];
        $result = DBQueryFactory::insert("[Properties].[UserPropertyBlocks]", $inputData, false);

        $propertyId = $result["lastInsertId"];

        $updateQuery = "UPDATE SpatialEntities.Entities SET EntityBlock = $propertyId WHERE EntityId = $entityId";
        $resultUpdate = DBConnectionFactory::getConnection()->exec($updateQuery);

        $metadata["property_floor_count"] = 1;

        //STEP 3: Index Metadata
        $values = [];
        foreach ($metadata as $key => $value) {
            if (is_array($values)) {
                $value = json_encode($value);
            }
            $values[] = "($propertyId, $estateId, '$key', '$value')";
        }

        $query = "INSERT INTO Properties.UserPropertyMetadataBlocks (PropertyId, PropertyEstate, FieldName, FieldValue) VALUES " . implode(",", $values);

        $result = DBConnectionFactory::getConnection()->exec($query);

        $resultData['EstateId'] = $estateId;
        $resultData['BlockId'] = $propertyId;
        $resultData['EntityId'] = $entityId;
        $resultData['result'] = $result;

        return $resultData;
    }

    // Redesigned newProperty 2
    public static function newPropertyBlockTest(array $data)
    {
        // collecting parameters
        $user = $data["user"];
        $metadata = $data["property_metadata"] ?? [];
        $title = $data["property_title"];
        $estateId = $data["property_estate_id"];
        $geometry = $data["property_geometry"] ?? null;
        $parent = $data["property_parent"] ?? null;
        $type = $data["property_type"];
        $propertyUUID = str_replace(".", "z", uniqid(uniqid(), true));

        if (self::isJSON($metadata)) { // checking for json data and converting to array
            if (is_string($metadata)) {
                $metadata = str_replace('&#39;', '"', $metadata);
                $metadata = str_replace('&#34;', '"', $metadata);
                $metadata = html_entity_decode($metadata);
                $metadata = json_decode($metadata, true);
            }

        }

        $geometry =  htmlentities(\KuboPlugin\Utils\Util::serializeObject($geometry), ENT_QUOTES);

        $linkedTimer = (int)time();
        $query = "INSERT INTO Properties.UserPropertyBlocks (UserId, PropertyTitle, PropertyUUID, LinkedEntity, PropertyEstate, EntityGeometry , PropertyFloorCount, PropertyType) VALUES ($user,'$title','$propertyUUID',$linkedTimer,$estateId,'$geometry',1,'$type')";


        return $query;

    }

    // Redesigned newProperty 3
    public static function newPropertyUnit(array $data)
    {
        try {
            // collecting parameters
            $user = $data["user"];
            $metadata = $data["property_metadata"] ?? [];
            $title = $data["property_title"];
            $estateId = $data["property_estate_id"];
            $blockId = $data["property_block_id"];
            $blockChainAddress = ""; // $data["block_chain_address"];
            $geometry = $data["property_geometry"] ?? null;
            $parent = $data["property_parent"] ?? null;
            $type = $data["property_type"];
            $propertyUUID = str_replace(".", "z", uniqid(uniqid(), true));

            if (self::isJSON($metadata)) { // checking for json data and converting to array
                if (is_string($metadata)) {
                    $metadata = str_replace('&#39;', '"', $metadata);
                    $metadata = str_replace('&#34;', '"', $metadata);
                    $metadata = html_entity_decode($metadata);
                    $metadata = json_decode($metadata, true);
                }

            }

            // return $metadata;

            //STEP 1: Index Spatial Entity
            $entity = [
                "entityName" => $title,
                "entityType" => $type,
                "entityParentId" => $parent,
                "entityGeometry" => $geometry,
                "entityEstate" => $estateId,
                "entityBlock" => $blockId,
            ];

            // Getting entity data
            $indexEntityResult = \KuboPlugin\SpatialEntity\Entity\Entity::newEntity($entity);
            $entityId = $indexEntityResult["lastInsertId"];

            //STEP 2: Index User Property
            $inputData = [
                "UserId" => $user,
                "LinkedEntity" => $entityId,
                "PropertyTitle" => QB::wrapString($title, "'"),
                "PropertyEstate" => $estateId,
                "PropertyBlock" => $blockId,
                "BlockChainAddress" => QB::wrapString($blockChainAddress, "'"),
                "PropertyUUID" => QB::wrapString($propertyUUID, "'"),
            ];

            $result = DBQueryFactory::insert("[Properties].[UserPropertyUnits]", $inputData, false);

            $propertyId = $result["lastInsertId"];

            //STEP 3: Index Metadata
            $values = [];
            foreach ($metadata as $key => $value) {
                if (is_array($values)) {
                    $value = json_encode($value);
                }
                $values[] = "($propertyId, $estateId, $blockId, '$key', '$value')";
            }

            $query = "INSERT INTO Properties.UserPropertyMetadataUnits (PropertyId, PropertyEstate, PropertyBlock, FieldName, FieldValue) VALUES " . implode(",", $values);

            $result = DBConnectionFactory::getConnection()->exec($query);

            $resultData['EstateId'] = $estateId;
            $resultData['BlockId'] = $blockId;
            $resultData['UnitId'] = $propertyId;
            $resultData['EntityId'] = $entityId;
            $resultData['result'] = $result;

            return $resultData;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    // Redesigned newProperty 3
    public static function newPropertyUnitTest(array $data)
    {
        try {
            // collecting parameters
            $user = $data["user"];
            $metadata = $data["property_metadata"] ?? [];
            $title = $data["property_title"];
            $estateId = $data["property_estate_id"];
            $blockId = $data["property_block_id"];
            $blockChainAddress = ""; // $data["block_chain_address"];
            $geometry = $data["property_geometry"] ?? null;
            $parent = $data["property_parent"] ?? null;
            $type = $data["property_type"];
            $propertyUUID = str_replace(".", "z", uniqid(uniqid(), true));

            if (self::isJSON($metadata)) { // checking for json data and converting to array
                if (is_string($metadata)) {
                    $metadata = str_replace('&#39;', '"', $metadata);
                    $metadata = str_replace('&#34;', '"', $metadata);
                    $metadata = html_entity_decode($metadata);
                    $metadata = json_decode($metadata, true);
                }

            }

            $geometry =  htmlentities(\KuboPlugin\Utils\Util::serializeObject($geometry), ENT_QUOTES);
            $linkedTimer = (int)time();
            $query = "INSERT INTO Properties.UserPropertyUnits (UserId, PropertyTitle, PropertyUUID, LinkedEntity, PropertyEstate, PropertyBlock, BlockChainAddress, EntityGeometry, PropertyType) VALUES ($user,'$title','$propertyUUID',$linkedTimer,$estateId,$blockId,$blockChainAddress,'$geometry','$type')";

            return $query;

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    // Redesigned newPropertyOnEntity
    public static function newPropertyOnEntity(array $data)
    {
        // Getting parameters
        $user = $data["user"] ?? 0;
        $metadata = $data["property_metadata"] ?? [];
        $title = $data["property_title"];
        $propertyId = $data["property_id"];
        $floorLevel = $data["floor_level"];
        $estateId = $data["property_estate_id"];
        $propertyUUID = str_replace(".", "z", uniqid(uniqid(), true));

        // getting old property data
        $query = "SELECT * FROM Properties.UserPropertyBlocks WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $entityId = $result[0]["LinkedEntity"] ?? 0;
        $propertyFloorCount = $floorLevel + 1;

        $inputData = [
            "UserId" => $user,
            "LinkedEntity" => $entityId,
            "PropertyFloor" => $floorLevel,
            "PropertyTitle" => QB::wrapString($title, "'"),
            "PropertyEstate" => $estateId,
            "PropertyUUID" => QB::wrapString($propertyUUID, "'"),
            "PropertyFloorCount" => $propertyFloorCount,
        ];

        // checking old property data
        $queryCheck = "SELECT PropertyId FROM Properties.UserPropertyBlocks WHERE PropertyFloor = $floorLevel AND LinkedEntity = $entityId";
        $resultCheck = DBConnectionFactory::getConnection()->query($queryCheck)->fetchAll(\PDO::FETCH_ASSOC);

        $propId = 0;
        if (count($resultCheck) > 0) {
            // updating old property data
            $queryUpdate = "UPDATE Properties.UserPropertyBlocks SET UserId = " . $inputData['UserId'] . ", LinkedEntity = " . $inputData['LinkedEntity'] . ", PropertyFloor = " . $inputData['PropertyFloor'] . ", PropertyTitle = " . $inputData['PropertyTitle'] . ", PropertyUUID = " . $inputData['PropertyUUID'] . ", PropertyEstate = " . $inputData['PropertyEstate'] . ", PropertyFloorCount = " . $inputData['PropertyFloorCount'] . " WHERE PropertyFloor = $floorLevel AND LinkedEntity = $entityId";
            $resultUpdate = DBConnectionFactory::getConnection()->exec($queryUpdate);
            $queryCheck = "SELECT PropertyId FROM Properties.UserPropertyBlocks WHERE PropertyFloor = $floorLevel AND LinkedEntity = $entityId";
            $resultCheck = DBConnectionFactory::getConnection()->query($queryCheck)->fetchAll(\PDO::FETCH_ASSOC);
            // getting property id
            $propId = $resultCheck[0]["PropertyId"];
        } else {
            // inserting new property data
            $result = DBQueryFactory::insert("[Properties].[UserPropertyBlocks]", $inputData, false);
            // updating former block property data
            $queryUpdateOld = "UPDATE Properties.UserPropertyBlocks SET PropertyFloorCount = " . $inputData['PropertyFloorCount'] . " WHERE PropertyFloor = $floorLevel - 1 AND LinkedEntity = $entityId";
            $resultUpdateOld = DBConnectionFactory::getConnection()->exec($queryUpdateOld);

            $propId = $result["lastInsertId"];
        }

        // checking json data and sanitizing html entities
        if (self::isJSON($metadata)) {
            $metadata = str_replace('&#34;', '"', $metadata);
            $metadata = str_replace('&#39;', '"', $metadata);
            $metadata = html_entity_decode($metadata);
            $metadata = json_decode($metadata, true);
        }

        $metadata["property_floor_count"] = $floorLevel + 1;

        // STEP 3: Index Metadata
        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            // checking existing metadata
            $queryChecker = "SELECT PropertyId,FieldName FROM Properties.UserPropertyMetadataBlocks WHERE PropertyId = $propId AND FieldName = '$key'";
            $resultChecker = DBConnectionFactory::getConnection()->query($queryChecker)->fetchAll(\PDO::FETCH_ASSOC);

            if (count($resultChecker) > 0) {
                if ($key == "property_floor_count") {

                } else {
                    //  Updating the existing field
                    $query = "UPDATE Properties.UserPropertyMetadataBlocks SET FieldValue = '$value' WHERE PropertyId = $propId AND FieldName = '$key'";
                    $result = DBConnectionFactory::getConnection()->exec($query);

                }

            } else {
                // Inserting a new field
                $query = "INSERT INTO Properties.UserPropertyMetadataBlocks (PropertyId, PropertyEstate, FieldName, FieldValue) VALUES ($propId, $estateId, '$key', '$value')"; // " . implode(",", $values);
                $result = DBConnectionFactory::getConnection()->exec($query);
                if ($key == "property_floor_count") {
                    //  Updating the existing field
                    $queryEstate = "UPDATE Properties.UserPropertyMetadata SET FieldValue = '$value' WHERE PropertyId = $estateId AND FieldName = '$key'";
                    $resultEstate = DBConnectionFactory::getConnection()->exec($queryEstate);

                    $queryPropEstate = "UPDATE Properties.UserProperty SET PropertyFloorCount = $propertyFloorCount WHERE PropertyId = $estateId";
                    $resultPropEstate = DBConnectionFactory::getConnection()->exec($queryPropEstate);

                }

            }

        }

        // getting previous unit data
        $propertyChildren = self::viewPropertyChildren((int) $propertyId, ["floorLevel" => (int) $floorLevel - 1, "floorSkip" => false, "propertyType" => "block"]);

        foreach ($propertyChildren as $property) {
            $title = $property["PropertyTitle"] . " - F" . $floorLevel;
            $entityId = $property["LinkedEntity"];
            $propertyUnitUUID = str_replace(".", "z", uniqid(uniqid(), true));

            $inputDataExtra = [
                "UserId" => $user,
                "LinkedEntity" => $entityId,
                "PropertyFloor" => $floorLevel,
                "PropertyTitle" => QB::wrapString($title, "'"),
                "PropertyEstate" => $estateId,
                "PropertyBlock" => $propId,
                "PropertyUUID" => QB::wrapString($propertyUnitUUID, "'"),
            ];

            // inserting new properties data
            DBQueryFactory::insert("[Properties].[UserPropertyUnits]", $inputDataExtra, false);
        }

        return $result;
    }

    // Redesigned newPropertyOnEntity
    public static function newPropertyOnEntityTest(array $data)
    {
        // Getting parameters
        $user = $data["user"] ?? 0;
        $metadata = $data["property_metadata"] ?? [];
        $title = $data["property_title"];
        $propertyId = $data["property_id"];
        $floorLevel = $data["floor_level"];
        $estateId = $data["property_estate_id"];
        $propertyUUID = str_replace(".", "z", uniqid(uniqid(), true));

        // getting old property data
        $query = "SELECT * FROM Properties.UserPropertyBlocks WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $entityId = $result[0]["LinkedEntity"] ?? 0;
        $geometry = $result[0]["EntityGeometry"] ?? "";
        $propertyFloorCount = $floorLevel + 1;

        $inputData = [
            "UserId" => $user,
            "LinkedEntity" => $entityId,
            "PropertyFloor" => $floorLevel,
            "PropertyTitle" => QB::wrapString($title, "'"),
            "PropertyEstate" => $estateId,
            "PropertyUUID" => QB::wrapString($propertyUUID, "'"),
            "PropertyFloorCount" => $propertyFloorCount,
            "EntityGeometry" => QB::wrapString($geometry, "'"),
        ];

        // checking old property data
        $queryCheck = "SELECT PropertyId FROM Properties.UserPropertyBlocks WHERE PropertyFloor = $floorLevel AND LinkedEntity = $entityId";
        $resultCheck = DBConnectionFactory::getConnection()->query($queryCheck)->fetchAll(\PDO::FETCH_ASSOC);

        $propId = 0;
        if (count($resultCheck) > 0) {
            // updating old property data
            $queryUpdate = "UPDATE Properties.UserPropertyBlocks SET UserId = " . $inputData['UserId'] . ", LinkedEntity = " . $inputData['LinkedEntity'] . ", PropertyFloor = " . $inputData['PropertyFloor'] . ", PropertyTitle = " . $inputData['PropertyTitle'] . ", PropertyUUID = " . $inputData['PropertyUUID'] . ", PropertyEstate = " . $inputData['PropertyEstate'] . ", EntityGeometry = " . $inputData['EntityGeometry'] . ", PropertyFloorCount = " . $inputData['PropertyFloorCount'] . " WHERE PropertyFloor = $floorLevel AND LinkedEntity = $entityId";
            $resultUpdate = DBConnectionFactory::getConnection()->exec($queryUpdate);
            $queryCheck = "SELECT PropertyId FROM Properties.UserPropertyBlocks WHERE PropertyFloor = $floorLevel AND LinkedEntity = $entityId";
            $resultCheck = DBConnectionFactory::getConnection()->query($queryCheck)->fetchAll(\PDO::FETCH_ASSOC);
            // getting property id
            $propId = $resultCheck[0]["PropertyId"];
        } else {
            // inserting new property data
            $result = DBQueryFactory::insert("[Properties].[UserPropertyBlocks]", $inputData, false);

            // updating former block property data
            $queryUpdateOld = "UPDATE Properties.UserPropertyBlocks SET PropertyFloorCount = " . $inputData['PropertyFloorCount'] . " WHERE PropertyFloor = $floorLevel - 1 AND LinkedEntity = $entityId";
            $resultUpdateOld = DBConnectionFactory::getConnection()->exec($queryUpdateOld);

            $propId = $result["lastInsertId"];
        }

        // checking json data and sanitizing html entities
        if (self::isJSON($metadata)) {
            $metadata = str_replace('&#34;', '"', $metadata);
            $metadata = str_replace('&#39;', '"', $metadata);
            $metadata = html_entity_decode($metadata);
            $metadata = json_decode($metadata, true);
        }

        $metadata["property_floor_count"] = $floorLevel + 1;

        // STEP 3: Index Metadata
        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            // checking existing metadata
            $queryChecker = "SELECT PropertyId,FieldName FROM Properties.UserPropertyMetadataBlocks WHERE PropertyId = $propId AND FieldName = '$key'";
            $resultChecker = DBConnectionFactory::getConnection()->query($queryChecker)->fetchAll(\PDO::FETCH_ASSOC);

            if (count($resultChecker) > 0) {
                if ($key == "property_floor_count") {

                } else {
                    //  Updating the existing field
                    $query = "UPDATE Properties.UserPropertyMetadataBlocks SET FieldValue = '$value' WHERE PropertyId = $propId AND FieldName = '$key'";
                    $result = DBConnectionFactory::getConnection()->exec($query);

                }

            } else {
                // Inserting a new field
                $query = "INSERT INTO Properties.UserPropertyMetadataBlocks (PropertyId, PropertyEstate, FieldName, FieldValue) VALUES ($propId, $estateId, '$key', '$value')"; // " . implode(",", $values);
                $result = DBConnectionFactory::getConnection()->exec($query);
                if ($key == "property_floor_count") {
                    //  Updating the existing field
                    $queryEstate = "UPDATE Properties.UserPropertyMetadata SET FieldValue = '$value' WHERE PropertyId = $estateId AND FieldName = '$key'";
                    $resultEstate = DBConnectionFactory::getConnection()->exec($queryEstate);

                    $queryPropEstate = "UPDATE Properties.UserProperty SET PropertyFloorCount = $propertyFloorCount WHERE PropertyId = $estateId";
                    $resultPropEstate = DBConnectionFactory::getConnection()->exec($queryPropEstate);

                }

            }

        }

        // getting previous unit data
        $propertyChildren = self::viewPropertyChildrenTest((int) $propertyId, ["floorLevel" => (int) $floorLevel - 1, "floorSkip" => false, "propertyType" => "block"]);

        foreach ($propertyChildren as $property) {
            $title = $property["PropertyTitle"] . " - F" . $floorLevel;
            $entityId = $property["LinkedEntity"];
            $geometry = $property["EntityGeometry"];
            $propertyUnitUUID = str_replace(".", "z", uniqid(uniqid(), true));

            $inputDataExtra = [
                "UserId" => $user,
                "LinkedEntity" => $entityId,
                "PropertyFloor" => $floorLevel,
                "PropertyTitle" => QB::wrapString($title, "'"),
                "PropertyEstate" => $estateId,
                "PropertyBlock" => $propId,
                "PropertyUUID" => QB::wrapString($propertyUnitUUID, "'"),
                "EntityGeometry" => QB::wrapString($geometry, "'"),
            ];

            // inserting new properties data
            DBQueryFactory::insert("[Properties].[UserPropertyUnits]", $inputDataExtra, false);
        }

        return $result;
    }

    // Redesigned viewProperties
    public static function viewProperties(int $userId, array $data = [])
    {
        $fetch = "FIRST";
        $offset = 0;

        if (isset($data['offset']) and $data['offset'] != 0) {
            $fetch = "NEXT";
            $offset = $data['offset'];
        }
        $query = "SELECT a.* FROM Properties.UserProperty a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.UserId = $userId AND b.EntityParent IS NULL ORDER BY a.PropertyId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";
        $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $unitQueries = [];
        $blockQueries = [];
        $metadata = [];
        $queryTotals = [];
        $queryAvailables = [];
        // looping through estates to get particular data
        foreach ($results as $key => $property) {
            $resultPropertyId = $property["PropertyId"];

            $resultPropertyFloor = $property["PropertyFloor"] ?? 0;

            // chaining queries for optimized operation
            $unitQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyId FROM Properties.UserPropertyMetadata a INNER JOIN Properties.UserProperty b ON a.PropertyId = b.PropertyId WHERE a.PropertyId = $resultPropertyId AND b.PropertyFloor = $resultPropertyFloor";

            // Fetch total estate property units
            $queryTotals[] = "SELECT PropertyEstate FROM Properties.UserPropertyUnits WHERE PropertyEstate = $resultPropertyId";

            $queryAvailables[] = "SELECT a.PropertyId, b.FieldName, b.FieldValue, b.PropertyEstate  FROM Properties.UserPropertyUnits a INNER JOIN Properties.UserPropertyMetadataUnits b ON a.PropertyId = b.PropertyId
            WHERE b.FieldName = 'property_status' AND b.FieldValue = 1 AND a.PropertyEstate = $resultPropertyId";

            $results[$key]["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $property["LinkedEntity"]]);

        }

        $unitQuery = implode(";", $unitQueries);
        $blockQuery = implode(";", $blockQueries);

        $unitResultSetArr = [];
        $blockResultSetArr = [];

        $resultSetArr = [];

        // looping and building result set through complex chain returned results
        $stmtResult = DBConnectionFactory::getConnection()->query($unitQuery);

        do {

            $unitResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
            if (count($unitResultArr) > 0) {
                // Add $rowset to array
                array_push($unitResultSetArr, $unitResultArr);

            }

        } while ($stmtResult->nextRowset());

        // connecting and building the results
        foreach ($results as $keySetId => $valueSetId) {

            foreach ($unitResultSetArr as $keySet => $valueSet) {

                foreach ($valueSet as $keyItemId => $valueItemId) {

                    if ($valueItemId["PropertyId"] == $valueSetId["PropertyId"]) {

                        $results[$keySetId]["Metadata"][$valueItemId["FieldName"]] = ["FieldValue" => $valueItemId["FieldValue"], "MetadataId" => $valueItemId["MetadataId"], "PropertyId" => $valueItemId["PropertyId"]];

                    }

                }

            }

        }

        $queryTotal = implode(";", $queryTotals);
        $propertyCounter = [];
        $totalResultSetArr = [];
        $stmtResultTotal = DBConnectionFactory::getConnection()->query($queryTotal);

        // looping and building result set through complex chain returned results
        do {

            $totalResultArr = $stmtResultTotal->fetchAll(\PDO::FETCH_ASSOC);
            if (count($totalResultArr) > 0) {
                // Add $rowset to array
                array_push($totalResultSetArr, $totalResultArr);

            }

        } while ($stmtResultTotal->nextRowset());
        // connecting and building the results
        foreach ($results as $keySetId => $valueSetId) {
            foreach ($totalResultSetArr as $keyItemId => $valueItemId) {
                if ($valueItemId[$keyItemId]["PropertyEstate"] == $valueSetId["PropertyId"]) {
                    $results[$keySetId]["PropertyTotal"] = count($valueItemId);
                    $propertyCounter[$keySetId] = count($valueItemId);
                }
            }

        }

        $queryAvailable = implode(";", $queryAvailables);
        $propertyAvailable = 0;
        $availableResultSetArr = [];
        $stmtResultAvailable = DBConnectionFactory::getConnection()->query($queryAvailable);

        do {

            $availableResultArr = $stmtResultAvailable->fetchAll(\PDO::FETCH_ASSOC);
            if (count($availableResultArr) > 0) {
                // Add $rowset to array
                array_push($availableResultSetArr, $availableResultArr);

            }

        } while ($stmtResultAvailable->nextRowset());

        // connecting and building the results
        if (count($availableResultSetArr) == 0) {
            foreach ($results as $keySetId => $valueSetId) {
                foreach ($totalResultSetArr as $keyItemId => $valueItemId) {
                    if ($valueItemId[$keyItemId]["PropertyEstate"] == $valueSetId["PropertyId"]) {
                        $results[$keySetId]["PropertyTotal"] = count($valueItemId);
                        $propertyCounter[$keySetId] = count($valueItemId);
                        $results[$keySetId]["PropertyAvailable"] = $propertyCounter[$keySetId] - 0;
                    }
                }
            }
        } else {
            foreach ($results as $keySetId => $valueSetId) {
                foreach ($availableResultSetArr as $keyItemId => $valueItemId) {
                    if ($valueItemId[$keyItemId]["PropertyEstate"] == $valueSetId["PropertyId"]) {
                        $results[$keySetId]["PropertyAvailable"] = $propertyCounter[$keySetId] - count($valueItemId);

                    }
                }

            }
        }

        return $results;
    }

    // Redesigned viewProperties
    public static function viewPropertiesTest(int $userId, array $data = [])
    {
        $fetch = "FIRST";
        $offset = 0;

        if (isset($data['offset']) and $data['offset'] != 0) {
            $fetch = "NEXT";
            $offset = $data['offset'];
        }
        $query = "SELECT * FROM Properties.UserProperty WHERE UserId = $userId ORDER BY PropertyId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";
        $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $unitQueries = [];
        $blockQueries = [];
        $metadata = [];
        $queryTotals = [];
        $queryAvailables = [];
        // looping through estates to get particular data
        foreach ($results as $key => $property) {
            $resultPropertyId = $property["PropertyId"];

            $resultPropertyFloor = $property["PropertyFloor"] ?? 0;

            // chaining queries for optimized operation
            $unitQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyId FROM Properties.UserPropertyMetadata a INNER JOIN Properties.UserProperty b ON a.PropertyId = b.PropertyId WHERE a.PropertyId = $resultPropertyId AND b.PropertyFloor = $resultPropertyFloor";

            // Fetch total estate property units
            $queryTotals[] = "SELECT PropertyEstate FROM Properties.UserPropertyUnits WHERE PropertyEstate = $resultPropertyId";

            $queryAvailables[] = "SELECT a.PropertyId, b.FieldName, b.FieldValue, b.PropertyEstate  FROM Properties.UserPropertyUnits a INNER JOIN Properties.UserPropertyMetadataUnits b ON a.PropertyId = b.PropertyId
            WHERE b.FieldName = 'property_status' AND b.FieldValue = 1 AND a.PropertyEstate = $resultPropertyId";

            $results[$key]["EntityGeometry"] = \KuboPlugin\Utils\Util::unserializeObject($property["EntityGeometry"]);

        }

        $unitQuery = implode(";", $unitQueries);
        $blockQuery = implode(";", $blockQueries);

        $unitResultSetArr = [];
        $blockResultSetArr = [];

        $resultSetArr = [];

        // looping and building result set through complex chain returned results
        $stmtResult = DBConnectionFactory::getConnection()->query($unitQuery);

        do {

            $unitResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
            if (count($unitResultArr) > 0) {
                // Add $rowset to array
                array_push($unitResultSetArr, $unitResultArr);

            }

        } while ($stmtResult->nextRowset());

        // connecting and building the results
        foreach ($results as $keySetId => $valueSetId) {

            foreach ($unitResultSetArr as $keySet => $valueSet) {

                foreach ($valueSet as $keyItemId => $valueItemId) {

                    if ($valueItemId["PropertyId"] == $valueSetId["PropertyId"]) {

                        $results[$keySetId]["Metadata"][$valueItemId["FieldName"]] = ["FieldValue" => $valueItemId["FieldValue"], "MetadataId" => $valueItemId["MetadataId"], "PropertyId" => $valueItemId["PropertyId"]];

                    }

                }

            }

        }

        $queryTotal = implode(";", $queryTotals);
        $propertyCounter = [];
        $totalResultSetArr = [];
        $stmtResultTotal = DBConnectionFactory::getConnection()->query($queryTotal);

        // looping and building result set through complex chain returned results
        do {

            $totalResultArr = $stmtResultTotal->fetchAll(\PDO::FETCH_ASSOC);
            if (count($totalResultArr) > 0) {
                // Add $rowset to array
                array_push($totalResultSetArr, $totalResultArr);

            }

        } while ($stmtResultTotal->nextRowset());
        // connecting and building the results
        foreach ($results as $keySetId => $valueSetId) {
            foreach ($totalResultSetArr as $keyItemId => $valueItemId) {
                if ($valueItemId[$keyItemId]["PropertyEstate"] == $valueSetId["PropertyId"]) {
                    $results[$keySetId]["PropertyTotal"] = count($valueItemId);
                    $propertyCounter[$keySetId] = count($valueItemId);
                }
            }

        }

        $queryAvailable = implode(";", $queryAvailables);
        $propertyAvailable = 0;
        $availableResultSetArr = [];
        $stmtResultAvailable = DBConnectionFactory::getConnection()->query($queryAvailable);

        do {

            $availableResultArr = $stmtResultAvailable->fetchAll(\PDO::FETCH_ASSOC);
            if (count($availableResultArr) > 0) {
                // Add $rowset to array
                array_push($availableResultSetArr, $availableResultArr);

            }

        } while ($stmtResultAvailable->nextRowset());

        // connecting and building the results
        if (count($availableResultSetArr) == 0) {
            foreach ($results as $keySetId => $valueSetId) {
                foreach ($totalResultSetArr as $keyItemId => $valueItemId) {
                    if ($valueItemId[$keyItemId]["PropertyEstate"] == $valueSetId["PropertyId"]) {
                        $results[$keySetId]["PropertyTotal"] = count($valueItemId);
                        $propertyCounter[$keySetId] = count($valueItemId);
                        $results[$keySetId]["PropertyAvailable"] = $propertyCounter[$keySetId] - 0;
                    }
                }
            }
        } else {
            foreach ($results as $keySetId => $valueSetId) {
                foreach ($availableResultSetArr as $keyItemId => $valueItemId) {
                    if ($valueItemId[$keyItemId]["PropertyEstate"] == $valueSetId["PropertyId"]) {
                        $results[$keySetId]["PropertyAvailable"] = $propertyCounter[$keySetId] - count($valueItemId);

                    }
                }

            }
        }

        return $results;
    }

    // Redesigned viewProperty
    public static function viewProperty(int $propertyId, array $data = [])
    {

        if (!isset($propertyId)) {
            return "Parameter not set";
        }

        // $propertyType = self::propertyChecker($propertyId, $data["propertyType"]);

        if ($data["propertyType"] == "estate") {
            $query = "SELECT * FROM Properties.UserProperty WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $queryFloor = "SELECT FieldValue FROM Properties.UserPropertyMetadata WHERE FieldName = 'property_floor_count' AND  PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorCount = $resultFloor['FieldValue'] ?? 1;

            $result = $result[0] ?? [];
            // getting particular data
            if (count($result) > 0) {
                $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["LinkedEntity"]]);
                $result["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], ["propertyType" => "estate"]);
                $result["FloorCount"] = $resultFloorCount;
            }

            return $result;
        }

        if ($data["propertyType"] == "block") {
            $query = "SELECT * FROM Properties.UserPropertyBlocks WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $queryFloor = "SELECT FieldValue FROM Properties.UserPropertyMetadataBlocks WHERE FieldName = 'property_floor_count' AND  PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorCount = $resultFloor['FieldValue'] ?? 1;

            $result = $result[0] ?? [];
            // getting particular data
            if (count($result) > 0) {
                $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["LinkedEntity"]]);
                $result["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], ["propertyType" => "block"]);
                $result["FloorCount"] = $resultFloorCount;
            }

            return $result;
        }

        if ($data["propertyType"] == "unit") {
            $query = "SELECT * FROM Properties.UserPropertyUnits WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $queryFloor = "SELECT FieldValue FROM Properties.UserPropertyMetadataUnits WHERE FieldName = 'property_floor_count' AND  PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorCount = $resultFloor['FieldValue'] ?? 1;

            $result = $result[0] ?? [];
            // getting particular data
            if (count($result) > 0) {
                $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["LinkedEntity"]]);
                $result["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], ["propertyType" => "unit"]);
                $result["FloorCount"] = $resultFloorCount;
            }

            return $result;
        }

        return "Invalid ID";
    }

    // Redesigned viewProperty
    public static function viewPropertyTest(int $propertyId, array $data = [])
    {

        if (!isset($propertyId)) {
            return "Parameter not set";
        }

        // $propertyType = self::propertyChecker($propertyId, $data["propertyType"]);

        if ($data["propertyType"] == "estate") {
            $query = "SELECT * FROM Properties.UserProperty WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $queryFloor = "SELECT FieldValue FROM Properties.UserPropertyMetadata WHERE FieldName = 'property_floor_count' AND  PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorCount = $resultFloor['FieldValue'] ?? 1;

            $result = $result[0] ?? [];
            // getting particular data
            if (count($result) > 0) {
                $result["EntityGeometry"] = \KuboPlugin\Utils\Util::unserializeObject($result["EntityGeometry"]);
                $result["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], ["propertyType" => "estate"]);
                $result["FloorCount"] = $resultFloorCount;
            }

            return $result;
        }

        if ($data["propertyType"] == "block") {
            $query = "SELECT * FROM Properties.UserPropertyBlocks WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $queryFloor = "SELECT FieldValue FROM Properties.UserPropertyMetadataBlocks WHERE FieldName = 'property_floor_count' AND  PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorCount = $resultFloor['FieldValue'] ?? 1;

            $result = $result[0] ?? [];
            // getting particular data
            if (count($result) > 0) {
                $result["EntityGeometry"] = \KuboPlugin\Utils\Util::unserializeObject($result["EntityGeometry"]);
                $result["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], ["propertyType" => "block"]);
                $result["FloorCount"] = $resultFloorCount;
            }

            return $result;
        }

        if ($data["propertyType"] == "unit") {
            $query = "SELECT * FROM Properties.UserPropertyUnits WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $queryFloor = "SELECT FieldValue FROM Properties.UserPropertyMetadataUnits WHERE FieldName = 'property_floor_count' AND  PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorCount = $resultFloor['FieldValue'] ?? 1;

            $result = $result[0] ?? [];
            // getting particular data
            if (count($result) > 0) {
                $result["EntityGeometry"] = \KuboPlugin\Utils\Util::unserializeObject($result["EntityGeometry"]);
                $result["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], ["propertyType" => "unit"]);
                $result["FloorCount"] = $resultFloorCount;
            }

            return $result;
        }

        return "Invalid ID";
    }

    // Redesigned listAllProperties
    public static function listAllProperties(int $propertyId = 1, array $data)
    {
        $fetch = "FIRST";
        $offset = 0;
        $limit = 10;

        if ($data['offset'] != 0) {
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        if ($data['limit'] == "") {
            $limit = 10;
        } else {
            $limit = $data['limit'] ?? 10;
        }

        if (!isset($propertyId) or empty($data)) {

            return "Parameters not set";
        }

        $query = "SELECT a.* FROM Properties.UserProperty a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE b.EntityType = 1 AND b.EntityParent IS NULL ORDER BY a.PropertyId DESC OFFSET $offset ROWS FETCH $fetch $limit ROWS ONLY";
        $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $blockQueries = [];
        $parentQueries = [];
        $metadata = [];
        // looping through estates to get particular data
        foreach ($results as $key => $result) {

            $resultPropertyId = $result["PropertyId"];

            $resultPropertyFloor = $result["PropertyFloor"] ?? 0;

            $blockQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyEstate FROM Properties.UserPropertyMetadataBlocks a INNER JOIN Properties.UserProperty b ON a.PropertyEstate = b.PropertyId  WHERE a.PropertyEstate = $resultPropertyId AND b.PropertyFloor = $resultPropertyFloor";

            $results[$key]["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["LinkedEntity"]]);
        }

        $blockQuery = implode(";", $blockQueries);

        $blockResultSetArr = [];

        $resultSetArr = [];

        // looping and building result set through complex chain returned results
        $stmtResult = DBConnectionFactory::getConnection()->query($blockQuery);

        do {

            $blockResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
            if (count($blockResultArr) > 0) {
                // Add $rowset to array
                array_push($blockResultSetArr, $blockResultArr);

            }

        } while ($stmtResult->nextRowset());

        // connecting and building result sets
        foreach ($results as $keySetId => $valueSetId) {

            foreach ($blockResultSetArr as $keySet => $valueSet) {

                foreach ($valueSet as $keyItemId => $valueItemId) {

                    if ($valueItemId["PropertyEstate"] == $valueSetId["PropertyId"]) {

                        $results[$keySetId]["Metadata"][$valueItemId["FieldName"]] = ["FieldValue" => $valueItemId["FieldValue"], "MetadataId" => $valueItemId["MetadataId"], "PropertyId" => $valueItemId["PropertyEstate"]];

                    }

                }

            }

        }

        return $results;
    }

    // Redesigned listAllProperties
    public static function listAllPropertiesTest(int $propertyId = 1, array $data)
    {
        $fetch = "FIRST";
        $offset = 0;
        $limit = 10;

        if ($data['offset'] != 0) {
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        if ($data['limit'] == "") {
            $limit = 10;
        } else {
            $limit = $data['limit'] ?? 10;
        }

        if (!isset($propertyId) or empty($data)) {

            return "Parameters not set";
        }

        $query = "SELECT * FROM Properties.UserProperty WHERE PropertyId IS NOT NULL ORDER BY PropertyId DESC OFFSET $offset ROWS FETCH $fetch $limit ROWS ONLY";
        $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $blockQueries = [];
        $parentQueries = [];
        $metadata = [];
        // looping through estates to get particular data
        foreach ($results as $key => $result) {

            $resultPropertyId = $result["PropertyId"];

            $resultPropertyFloor = $result["PropertyFloor"] ?? 0;

            $blockQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyEstate FROM Properties.UserPropertyMetadataBlocks a INNER JOIN Properties.UserProperty b ON a.PropertyEstate = b.PropertyId  WHERE a.PropertyEstate = $resultPropertyId AND b.PropertyFloor = $resultPropertyFloor";

            $results[$key]["EntityGeometry"] = \KuboPlugin\Utils\Util::unserializeObject($result["EntityGeometry"]);
        }

        $blockQuery = implode(";", $blockQueries);

        $blockResultSetArr = [];

        $resultSetArr = [];

        // looping and building result set through complex chain returned results
        $stmtResult = DBConnectionFactory::getConnection()->query($blockQuery);

        do {

            $blockResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
            if (count($blockResultArr) > 0) {
                // Add $rowset to array
                array_push($blockResultSetArr, $blockResultArr);

            }

        } while ($stmtResult->nextRowset());

        // connecting and building result sets
        foreach ($results as $keySetId => $valueSetId) {

            foreach ($blockResultSetArr as $keySet => $valueSet) {

                foreach ($valueSet as $keyItemId => $valueItemId) {

                    if ($valueItemId["PropertyEstate"] == $valueSetId["PropertyId"]) {

                        $results[$keySetId]["Metadata"][$valueItemId["FieldName"]] = ["FieldValue" => $valueItemId["FieldValue"], "MetadataId" => $valueItemId["MetadataId"], "PropertyId" => $valueItemId["PropertyEstate"]];

                    }

                }

            }

        }

        return $results;
    }

    public static function getPropertyMetadata(int $propertyId, int $floorLevel = 0)
    {
        $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $propertyId";
        $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

        $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;

        $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        // compensating unit with block data
        $blockQuery = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
        WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
        SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE PropertyId = $propertyId)) AND c.PropertyFloor = $resultFloorPoint";
        $blockResult = DBConnectionFactory::getConnection()->query($blockQuery)->fetchAll(\PDO::FETCH_ASSOC);

        $metadata = [];
        foreach ($result as $key => $value) {
            $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
        }

        // matching empty unit data with block data
        foreach ($blockResult as $keyItem => $valueItem) {
            if (!isset($metadata[$valueItem["FieldName"]])) {
                $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
            } else if (isset($metadata[$valueItem["FieldName"]]) and empty($metadata[$valueItem["FieldValue"]])) {
                //   $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
            }

        }

        return $metadata;
    }

    // Redesigned getPropertyMetadata not done yet
    public static function getPropertyMetadataSet(int $propertyId, int $floorLevel = 0)
    {
        $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $propertyId";
        $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

        $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;

        $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        // compensating unit with block data
        $blockQuery = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
        WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
        SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $propertyId)) AND c.PropertyFloor = $resultFloorPoint";
        $blockResult = DBConnectionFactory::getConnection()->query($blockQuery)->fetchAll(\PDO::FETCH_ASSOC);

        $metadata = [];
        foreach ($result as $key => $value) {
            $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
        }

        // matching empty unit data with block data
        foreach ($blockResult as $keyItem => $valueItem) {
            if (!isset($metadata[$valueItem["FieldName"]])) {
                $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
            } else if (isset($metadata[$valueItem["FieldName"]]) and empty($metadata[$valueItem["FieldValue"]])) {
                //  $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
            }

        }

        return $metadata;
    }

    // Redesigned viewPropertyByName
    public static function viewPropertyByName(array $data)
    {
        $name = $data["name"] ?? 0;
        $query = "SELECT * FROM Properties.UserProperty WHERE PropertyTitle = '$name'";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $result = $result[0] ?? [];
        // getting property data if exist
        if (count($result) > 0) {
            $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["LinkedEntity"]]);
            $result["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], ["propertyType" => "estate"]);
        }

        return $result;
    }

    // Redesigned viewPropertyByName
    public static function viewPropertyByNameTest(array $data)
    {
        $name = $data["name"] ?? 0;
        $query = "SELECT * FROM Properties.UserProperty WHERE PropertyTitle = '$name'";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $result = $result[0] ?? [];
        // getting property data if exist
        if (count($result) > 0) {
            $result["EntityGeometry"] = \KuboPlugin\Utils\Util::unserializeObject($result["EntityGeometry"]);
            $result["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], ["propertyType" => "estate"]);
        }

        return $result;
    }

    // Redesigned viewPropertyChildren
    public static function viewPropertyChildren(int $propertyId, array $floorData = [])
    {
        if (!isset($propertyId)) {
            return "Parameter not set";
        }

        // $propertyType = self::propertyChecker($propertyId);

        if ($floorData["propertyType"] == "estate") {

            $floorLevel = 0;

            // get property children
            $query = "SELECT a.*, b.EntityParent FROM Properties.UserPropertyBlocks a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyEstate = $propertyId AND b.EntityEstate = $propertyId";

            if (isset($floorData["floorLevel"])) {
                $floorLevel = $floorData["floorLevel"];
                $query .= " AND a.PropertyFloor = $floorLevel";
            }

            $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            if (count($results) == 0) {
                return "No Children Data";
            }
            if (isset($results[0])) {
                $propertyId = $results[0]["EntityParent"];
            }

            $propertyChildren = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntityChildren(["entityId" => $propertyId]);

            // $childrenMetadata = self::viewPropertyChildrenMetadata((int) $propertyId, $floorData, (int) $floorLevel);

            $unitQueries = [];
            $blockQueries = [];
            $metadata = [];

            // looping and building result set through complex chain queries
            foreach ($results as $key => $result) {
                $results[$key]["Entity"] = $propertyChildren[$result["LinkedEntity"]] ?? [];

                $resultPropertyId = $result["PropertyEstate"];

                $resultPropertyFloor = $result["PropertyFloor"] ?? 0;

                $blockQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyId, a.PropertyEstate FROM Properties.UserPropertyMetadataBlocks a INNER JOIN Properties.UserPropertyBlocks b ON a.PropertyId = b.PropertyId  WHERE a.PropertyEstate = $resultPropertyId AND b.PropertyFloor = $resultPropertyFloor";

                $parentQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyId FROM Properties.UserPropertyMetadata a INNER JOIN Properties.UserProperty b ON a.PropertyId = b.PropertyId WHERE a.PropertyId = $resultPropertyId AND b.PropertyFloor = $resultPropertyFloor";

            }

            $blockQuery = implode(";", $blockQueries);
            $parentQuery = implode(";", $parentQueries);

            $blockResultSetArr = [];
            $parentResultSetArr = [];

            $resultSetArr = [];

            // looping and building result set through complex chain returned results
            $stmtResult = DBConnectionFactory::getConnection()->query($blockQuery);

            do {

                $blockResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
                if (count($blockResultArr) > 0) {
                    // Add $rowset to array
                    array_push($blockResultSetArr, $blockResultArr);

                }

            } while ($stmtResult->nextRowset());

            $stmtParent = DBConnectionFactory::getConnection()->query($parentQuery);

            do {

                $parentResultArr = $stmtParent->fetchAll(\PDO::FETCH_ASSOC);
                if (count($parentResultArr) > 0) {
                    // Add $rowset to array
                    array_push($parentResultSetArr, $parentResultArr);

                }
            } while ($stmtParent->nextRowset());

            // connecting and building result sets
            foreach ($results as $keySetId => $valueSetId) {

                foreach ($blockResultSetArr as $keySet => $valueSet) {

                    foreach ($valueSet as $keyItemId => $valueItemId) {

                        if ($valueItemId["PropertyEstate"] == $valueSetId["PropertyEstate"]) {

                            $results[$keySetId]["Metadata"][$valueItemId["FieldName"]] = ["FieldValue" => $valueItemId["FieldValue"], "MetadataId" => $valueItemId["MetadataId"], "PropertyId" => $valueItemId["PropertyId"]];

                        }

                    }

                }

            }

            foreach ($results as $keySetId => $valueSetId) {

                foreach ($parentResultSetArr as $keyItem => $valueItem) {

                    foreach ($valueItem as $keyItemIdSet => $valueItemIdSet) {

                        if ($valueItemIdSet["PropertyId"] == $valueSetId['PropertyEstate']) {
                            if (!isset($results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]]) or !isset($results[$keySetId]["Metadata"])) {
                                $results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueItemIdSet["MetadataId"], "PropertyId" => $valueItemIdSet["PropertyId"]];
                            } else if (isset($results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]]) and empty($results[$keySetId]["Metadata"][$valueItemIdSet["FieldValue"]])) {
                                //    $results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueItemIdSet["MetadataId"], "PropertyId" => $valueItemIdSet["PropertyId"]];
                            }
                        }

                    }

                }

            }

            return $results;
        }

        if ($floorData["propertyType"] == "block") {

            $floorLevel = 0;

            // get property children
            $query = "SELECT a.*, b.EntityParent FROM Properties.UserPropertyUnits a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyBlock = $propertyId";

            if (isset($floorData["floorSkip"]) and $floorData["floorSkip"] == false) {

            } else {
                if (isset($floorData["floorLevel"])) {
                    $floorLevel = $floorData["floorLevel"];
                    $query .= " AND a.PropertyFloor = $floorLevel";
                }
            }

            $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            if (count($results) == 0) {
                return "No Children Data";
            }
            if (isset($results[0])) {
                $propertyId = $results[0]["EntityParent"];
            }

            $propertyChildren = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntityChildren(["entityId" => $propertyId]);

            // $childrenMetadata = self::viewPropertyChildrenMetadata((int) $propertyId, $floorData, (int) $floorLevel);

            $unitQueries = [];
            $blockQueries = [];
            $metadata = [];
            // looping and building result set through complex chain queries
            foreach ($results as $key => $result) {
                $results[$key]["Entity"] = $propertyChildren[$result["LinkedEntity"]] ?? [];

                $resultPropertyId = $result["PropertyId"];

                $resultPropertyFloor = $result["PropertyFloor"] ?? 0;

                $unitQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyId, a.PropertyBlock FROM Properties.UserPropertyMetadataUnits a INNER JOIN Properties.UserPropertyUnits b ON a.PropertyId = b.PropertyId  WHERE a.PropertyId = $resultPropertyId AND b.PropertyFloor = $resultPropertyFloor";

                $parentQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyId FROM Properties.UserPropertyMetadataBlocks a INNER JOIN Properties.UserPropertyBlocks b ON a.PropertyId = b.PropertyId WHERE a.PropertyId = $propertyId AND b.PropertyFloor = $resultPropertyFloor";

            }

            $unitQuery = implode(";", $unitQueries);
            $parentQuery = implode(";", $parentQueries);

            $unitResultSetArr = [];
            $parentResultSetArr = [];

            $resultSetArr = [];

            // looping and building result set through complex chain returned results
            $stmtResult = DBConnectionFactory::getConnection()->query($unitQuery);

            do {

                $unitResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
                if (count($unitResultArr) > 0) {
                    // Add $rowset to array
                    array_push($unitResultSetArr, $unitResultArr);

                }

            } while ($stmtResult->nextRowset());

            $stmtParent = DBConnectionFactory::getConnection()->query($parentQuery);

            do {

                $parentResultArr = $stmtParent->fetchAll(\PDO::FETCH_ASSOC);
                if (count($parentResultArr) > 0) {
                    // Add $rowset to array
                    array_push($parentResultSetArr, $parentResultArr);

                }
            } while ($stmtParent->nextRowset());

            // connecting and building result sets
            foreach ($results as $keySetId => $valueSetId) {

                foreach ($unitResultSetArr as $keySet => $valueSet) {

                    foreach ($valueSet as $keyItemId => $valueItemId) {

                        if ($valueItemId["PropertyId"] == $valueSetId["PropertyId"]) {

                            $results[$keySetId]["Metadata"][$valueItemId["FieldName"]] = ["FieldValue" => $valueItemId["FieldValue"], "MetadataId" => $valueItemId["MetadataId"], "PropertyId" => $valueItemId["PropertyId"]];

                        }

                    }

                }

            }

            foreach ($results as $keySetId => $valueSetId) {

                foreach ($parentResultSetArr as $keyItem => $valueItem) {

                    foreach ($valueItem as $keyItemIdSet => $valueItemIdSet) {

                        if ($valueItemIdSet["PropertyId"] == $valueSetId['PropertyBlock']) {
                            if (!isset($results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]]) or !isset($results[$keySetId]["Metadata"])) {
                                $results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueItemIdSet["MetadataId"], "PropertyId" => $valueItemIdSet["PropertyId"]];
                            } else if (isset($results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]]) and empty($results[$keySetId]["Metadata"][$valueItemIdSet["FieldValue"]])) {
                                //   $results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueItemIdSet["MetadataId"], "PropertyId" => $valueItemIdSet["PropertyId"]];
                            }
                        }

                    }

                }

            }

            return $results;
        }

        if ($floorData["propertyType"] == "unit") {

            return "No Children Data";
        }

        return "Invalid ID";

    }

    // Redesigned viewPropertyChildren
    public static function viewPropertyChildrenTest(int $propertyId, array $floorData = [])
    {
        if (!isset($propertyId)) {
            return "Parameter not set";
        }

        // $propertyType = self::propertyChecker($propertyId);

        if ($floorData["propertyType"] == "estate") {

            $floorLevel = 0;

            // get property children
            $query = "SELECT * FROM Properties.UserPropertyBlocks WHERE PropertyEstate = $propertyId";

            if (isset($floorData["floorLevel"])) {
                $floorLevel = $floorData["floorLevel"];
                $query .= " AND a.PropertyFloor = $floorLevel";
            }


            $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            if (count($results) == 0) {
                return "No Children Data";
            }
            if (isset($results[0])) {
                // $propertyId = $results[0]["EntityParent"];
            }

            // $propertyChildren = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntityChildren(["entityId" => $propertyId]);

            // $childrenMetadata = self::viewPropertyChildrenMetadata((int) $propertyId, $floorData, (int) $floorLevel);

            $unitQueries = [];
            $blockQueries = [];
            $metadata = [];

            // looping and building result set through complex chain queries
            foreach ($results as $key => $result) {

                $geometry = $result["EntityGeometry"] ?? [];

                $results[$key]["EntityGeometry"] = \KuboPlugin\Utils\Util::unserializeObject($geometry);

               // $result["EntityGeometry"] = null;

                $resultPropertyId = $result["PropertyEstate"] ?? 0;

                $resultPropertyFloor = $result["PropertyFloor"] ?? 0;

                $blockQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyId, a.PropertyEstate FROM Properties.UserPropertyMetadataBlocks a INNER JOIN Properties.UserPropertyBlocks b ON a.PropertyId = b.PropertyId  WHERE a.PropertyEstate = $resultPropertyId AND b.PropertyFloor = $resultPropertyFloor";

                $parentQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyId FROM Properties.UserPropertyMetadata a INNER JOIN Properties.UserProperty b ON a.PropertyId = b.PropertyId WHERE a.PropertyId = $resultPropertyId AND b.PropertyFloor = $resultPropertyFloor";

            }


            $blockQuery = implode(";", $blockQueries);
            $parentQuery = implode(";", $parentQueries);

            $blockResultSetArr = [];
            $parentResultSetArr = [];

            $resultSetArr = [];

            // looping and building result set through complex chain returned results
            $stmtResult = DBConnectionFactory::getConnection()->query($blockQuery);

            do {

                $blockResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
                if (count($blockResultArr) > 0) {
                    // Add $rowset to array
                    array_push($blockResultSetArr, $blockResultArr);

                }

            } while ($stmtResult->nextRowset());

            $stmtParent = DBConnectionFactory::getConnection()->query($parentQuery);

            do {

                $parentResultArr = $stmtParent->fetchAll(\PDO::FETCH_ASSOC);
                if (count($parentResultArr) > 0) {
                    // Add $rowset to array
                    array_push($parentResultSetArr, $parentResultArr);

                }
            } while ($stmtParent->nextRowset());

            // connecting and building result sets
            foreach ($results as $keySetId => $valueSetId) {

                foreach ($blockResultSetArr as $keySet => $valueSet) {

                    foreach ($valueSet as $keyItemId => $valueItemId) {

                        if ($valueItemId["PropertyEstate"] == $valueSetId["PropertyEstate"]) {

                            $results[$keySetId]["Metadata"][$valueItemId["FieldName"]] = ["FieldValue" => $valueItemId["FieldValue"], "MetadataId" => $valueItemId["MetadataId"], "PropertyId" => $valueItemId["PropertyId"]];

                        }

                    }

                }

            }

            foreach ($results as $keySetId => $valueSetId) {

                foreach ($parentResultSetArr as $keyItem => $valueItem) {

                    foreach ($valueItem as $keyItemIdSet => $valueItemIdSet) {

                        if ($valueItemIdSet["PropertyId"] == $valueSetId['PropertyEstate']) {
                            if (!isset($results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]]) or !isset($results[$keySetId]["Metadata"])) {
                                $results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueItemIdSet["MetadataId"], "PropertyId" => $valueItemIdSet["PropertyId"]];
                            } else if (isset($results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]]) and empty($results[$keySetId]["Metadata"][$valueItemIdSet["FieldValue"]])) {
                                //    $results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueItemIdSet["MetadataId"], "PropertyId" => $valueItemIdSet["PropertyId"]];
                            }
                        }

                    }

                }

            }

            return $results;
        }

        if ($floorData["propertyType"] == "block") {

            $floorLevel = 0;

            // get property children
            $query = "SELECT * FROM Properties.UserPropertyUnits WHERE PropertyBlock = $propertyId";

            if (isset($floorData["floorSkip"]) and $floorData["floorSkip"] == false) {

            } else {
                if (isset($floorData["floorLevel"])) {
                    $floorLevel = $floorData["floorLevel"];
                    $query .= " AND a.PropertyFloor = $floorLevel";
                }
            }

            $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            if (count($results) == 0) {
                return "No Children Data";
            }
            if (isset($results[0])) {
                //  $propertyId = $results[0]["EntityParent"];
            }

            // $propertyChildren = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntityChildren(["entityId" => $propertyId]);

            // $childrenMetadata = self::viewPropertyChildrenMetadata((int) $propertyId, $floorData, (int) $floorLevel);

            $unitQueries = [];
            $blockQueries = [];
            $metadata = [];
            // looping and building result set through complex chain queries
            foreach ($results as $key => $result) {
                $geometry = $result["EntityGeometry"] ?? [];

                $results[$key]["EntityGeometry"] = \KuboPlugin\Utils\Util::unserializeObject($geometry);

               // $result["EntityGeometry"] = null;

                $resultPropertyId = $result["PropertyId"];

                $resultPropertyFloor = $result["PropertyFloor"] ?? 0;

                $unitQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyId, a.PropertyBlock FROM Properties.UserPropertyMetadataUnits a INNER JOIN Properties.UserPropertyUnits b ON a.PropertyId = b.PropertyId  WHERE a.PropertyId = $resultPropertyId AND b.PropertyFloor = $resultPropertyFloor";

                $parentQueries[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, a.PropertyId FROM Properties.UserPropertyMetadataBlocks a INNER JOIN Properties.UserPropertyBlocks b ON a.PropertyId = b.PropertyId WHERE a.PropertyId = $propertyId AND b.PropertyFloor = $resultPropertyFloor";

            }

            $unitQuery = implode(";", $unitQueries);
            $parentQuery = implode(";", $parentQueries);

            $unitResultSetArr = [];
            $parentResultSetArr = [];

            $resultSetArr = [];

            // looping and building result set through complex chain returned results
            $stmtResult = DBConnectionFactory::getConnection()->query($unitQuery);

            do {

                $unitResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
                if (count($unitResultArr) > 0) {
                    // Add $rowset to array
                    array_push($unitResultSetArr, $unitResultArr);

                }

            } while ($stmtResult->nextRowset());

            $stmtParent = DBConnectionFactory::getConnection()->query($parentQuery);

            do {

                $parentResultArr = $stmtParent->fetchAll(\PDO::FETCH_ASSOC);
                if (count($parentResultArr) > 0) {
                    // Add $rowset to array
                    array_push($parentResultSetArr, $parentResultArr);

                }
            } while ($stmtParent->nextRowset());

            // connecting and building result sets
            foreach ($results as $keySetId => $valueSetId) {

                foreach ($unitResultSetArr as $keySet => $valueSet) {

                    foreach ($valueSet as $keyItemId => $valueItemId) {

                        if ($valueItemId["PropertyId"] == $valueSetId["PropertyId"]) {

                            $results[$keySetId]["Metadata"][$valueItemId["FieldName"]] = ["FieldValue" => $valueItemId["FieldValue"], "MetadataId" => $valueItemId["MetadataId"], "PropertyId" => $valueItemId["PropertyId"]];

                        }

                    }

                }

            }

            foreach ($results as $keySetId => $valueSetId) {

                foreach ($parentResultSetArr as $keyItem => $valueItem) {

                    foreach ($valueItem as $keyItemIdSet => $valueItemIdSet) {

                        if ($valueItemIdSet["PropertyId"] == $valueSetId['PropertyBlock']) {
                            if (!isset($results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]]) or !isset($results[$keySetId]["Metadata"])) {
                                $results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueItemIdSet["MetadataId"], "PropertyId" => $valueItemIdSet["PropertyId"]];
                            } else if (isset($results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]]) and empty($results[$keySetId]["Metadata"][$valueItemIdSet["FieldValue"]])) {
                                //   $results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueItemIdSet["MetadataId"], "PropertyId" => $valueItemIdSet["PropertyId"]];
                            }
                        }

                    }

                }

            }

            return $results;
        }

        if ($floorData["propertyType"] == "unit") {

            return "No Children Data";
        }

        return "Invalid ID";

    }

    // Redesigned viewPropertyMetadata
    public static function viewPropertyMetadata(int $propertyId, array $data, int $floorLevel = 0)
    {
        //  \KuboPlugin\Utils\Util::checkAuthorization();

        if (!isset($propertyId)) {
            return "Parameter not set";
        }

        // $propertyType = self::propertyChecker($propertyId);

        if ($data["propertyType"] == "estate") {
            $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;

            $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $metadata = [];
            $plotOfLand = false;
            foreach ($result as $key => $value) {
                $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
            }

            return $metadata;
        }

        if ($data["propertyType"] == "block") {
            $queryFloor = "SELECT PropertyFloor, PropertyEstate FROM Properties.UserPropertyBlocks WHERE PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;
            $resultEstate = $resultFloor['PropertyEstate'] ?? 0;

            $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadataBlocks WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            // getting parent data
            $parentQuery = "SELECT MetadataId, FieldName, FieldValue, PropertyId FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultEstate";
            $parentResult = DBConnectionFactory::getConnection()->query($parentQuery)->fetchAll(\PDO::FETCH_ASSOC);

            $metadata = [];
            $plotOfLand = false;
            foreach ($result as $key => $value) {
                $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
            }

            foreach ($parentResult as $keyItem => $valueItem) {
                if ($valueItem["FieldValue"] == "Plot of land") {
                    $plotOfLand = true;
                }
            }

            // compensating empty field data with parent data
            foreach ($parentResult as $keyItem => $valueItem) {
                if (!isset($metadata[$valueItem["FieldName"]])) {
                    if ($valueItem["FieldName"] == "property_bedroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_sittingroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_kitchen_count" and $plotOfLand or $valueItem["FieldName"] == "property_bathroom_count" and $plotOfLand) {

                    } else {
                        $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                    }
                } else if (isset($metadata[$valueItem["FieldName"]]) and empty($metadata[$valueItem["FieldValue"]])) {
                    // $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                }
            }

            return $metadata;
        }

        if ($data["propertyType"] == "unit") {
            $queryFloor = "SELECT PropertyFloor, PropertyBlock FROM Properties.UserPropertyUnits WHERE PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;
            $resultBlock = $resultFloor['PropertyBlock'] ?? 0;

            $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadataUnits WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            // getting parent data
            $parentQuery = "SELECT MetadataId, FieldName, FieldValue, PropertyId FROM Properties.UserPropertyMetadataBlocks WHERE PropertyId = $resultBlock";
            $parentResult = DBConnectionFactory::getConnection()->query($parentQuery)->fetchAll(\PDO::FETCH_ASSOC);

            $metadata = [];
            $plotOfLand = false;
            foreach ($result as $key => $value) {
                $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
            }

            foreach ($parentResult as $keyItem => $valueItem) {
                if ($valueItem["FieldValue"] == "Plot of land") {
                    $plotOfLand = true;
                }
            }

            // compensating empty unit data with parent data
            foreach ($parentResult as $keyItem => $valueItem) {
                if (!isset($metadata[$valueItem["FieldName"]])) {
                    if ($valueItem["FieldName"] == "property_bedroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_sittingroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_kitchen_count" and $plotOfLand or $valueItem["FieldName"] == "property_bathroom_count" and $plotOfLand) {

                    } else {
                        $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                    }
                } else if (isset($metadata[$valueItem["FieldName"]]) and empty($metadata[$valueItem["FieldValue"]])) {
                    // $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                }
            }

            return $metadata;
        }

        return "Invalid ID";
    }

    // Redesigned viewPropertyMetadata
    public static function viewPropertyMetadataTest(int $propertyId, array $data, int $floorLevel = 0)
    {
        //  \KuboPlugin\Utils\Util::checkAuthorization();

        if (!isset($propertyId)) {
            return "Parameter not set";
        }

        // $propertyType = self::propertyChecker($propertyId);

        if ($data["propertyType"] == "estate") {
            $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;

            $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $metadata = [];
            $plotOfLand = false;
            foreach ($result as $key => $value) {
                $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
            }

            return $metadata;
        }

        if ($data["propertyType"] == "block") {
            $queryFloor = "SELECT PropertyFloor, PropertyEstate FROM Properties.UserPropertyBlocks WHERE PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;
            $resultEstate = $resultFloor['PropertyEstate'] ?? 0;

            $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadataBlocks WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            // getting parent data
            $parentQuery = "SELECT MetadataId, FieldName, FieldValue, PropertyId FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultEstate";
            $parentResult = DBConnectionFactory::getConnection()->query($parentQuery)->fetchAll(\PDO::FETCH_ASSOC);

            $metadata = [];
            $plotOfLand = false;
            foreach ($result as $key => $value) {
                $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
            }

            foreach ($parentResult as $keyItem => $valueItem) {
                if ($valueItem["FieldValue"] == "Plot of land") {
                    $plotOfLand = true;
                }
            }

            // compensating empty field data with parent data
            foreach ($parentResult as $keyItem => $valueItem) {
                if (!isset($metadata[$valueItem["FieldName"]])) {
                    if ($valueItem["FieldName"] == "property_bedroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_sittingroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_kitchen_count" and $plotOfLand or $valueItem["FieldName"] == "property_bathroom_count" and $plotOfLand) {

                    } else {
                        $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                    }
                } else if (isset($metadata[$valueItem["FieldName"]]) and empty($metadata[$valueItem["FieldValue"]])) {
                    // $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                }
            }

            return $metadata;
        }

        if ($data["propertyType"] == "unit") {
            $queryFloor = "SELECT PropertyFloor, PropertyBlock FROM Properties.UserPropertyUnits WHERE PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;
            $resultBlock = $resultFloor['PropertyBlock'] ?? 0;

            $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadataUnits WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            // getting parent data
            $parentQuery = "SELECT MetadataId, FieldName, FieldValue, PropertyId FROM Properties.UserPropertyMetadataBlocks WHERE PropertyId = $resultBlock";
            $parentResult = DBConnectionFactory::getConnection()->query($parentQuery)->fetchAll(\PDO::FETCH_ASSOC);

            $metadata = [];
            $plotOfLand = false;
            foreach ($result as $key => $value) {
                $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
            }

            foreach ($parentResult as $keyItem => $valueItem) {
                if ($valueItem["FieldValue"] == "Plot of land") {
                    $plotOfLand = true;
                }
            }

            // compensating empty unit data with parent data
            foreach ($parentResult as $keyItem => $valueItem) {
                if (!isset($metadata[$valueItem["FieldName"]])) {
                    if ($valueItem["FieldName"] == "property_bedroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_sittingroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_kitchen_count" and $plotOfLand or $valueItem["FieldName"] == "property_bathroom_count" and $plotOfLand) {

                    } else {
                        $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                    }
                } else if (isset($metadata[$valueItem["FieldName"]]) and empty($metadata[$valueItem["FieldValue"]])) {
                    // $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                }
            }

            return $metadata;
        }

        return "Invalid ID";
    }

    // Redesigned viewPropertyMetadata Testing
    public static function viewPropertyMetadataTester(int $propertyId, array $data, int $floorLevel = 0)
    {
        \KuboPlugin\Utils\Util::checkAuthorization();

        if (!isset($propertyId)) {
            return "Parameter not set";
        }

        if ($data["propertyType"] == "estate") {
            $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;

            $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $metadata = [];
            $plotOfLand = false;
            foreach ($result as $key => $value) {
                $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
            }

            return $metadata;
        }

        if ($data["propertyType"] == "block") {
            $queryFloor = "SELECT PropertyFloor, PropertyEstate FROM Properties.UserPropertyBlocks WHERE PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;
            $resultEstate = $resultFloor['PropertyEstate'] ?? 0;

            $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadataBlocks WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            // getting parent data
            $parentQuery = "SELECT MetadataId, FieldName, FieldValue, PropertyId FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultEstate";
            $parentResult = DBConnectionFactory::getConnection()->query($parentQuery)->fetchAll(\PDO::FETCH_ASSOC);

            $metadata = [];
            $plotOfLand = false;
            foreach ($result as $key => $value) {
                $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
            }

            foreach ($parentResult as $keyItem => $valueItem) {
                if ($valueItem["FieldValue"] == "Plot of land") {
                    $plotOfLand = true;
                }
            }

            // compensating empty field data with parent data
            foreach ($parentResult as $keyItem => $valueItem) {
                if (!isset($metadata[$valueItem["FieldName"]])) {
                    if ($valueItem["FieldName"] == "property_bedroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_sittingroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_kitchen_count" and $plotOfLand or $valueItem["FieldName"] == "property_bathroom_count" and $plotOfLand) {

                    } else {
                        $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                    }
                } else if (isset($metadata[$valueItem["FieldName"]]) and empty($metadata[$valueItem["FieldValue"]])) {
                    // $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                }
            }

            return $metadata;
        }

        if ($data["propertyType"] == "unit") {
            $queryFloor = "SELECT PropertyFloor, PropertyBlock FROM Properties.UserPropertyUnits WHERE PropertyId = $propertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;
            $resultBlock = $resultFloor['PropertyBlock'] ?? 0;

            $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadataUnits WHERE PropertyId = $propertyId";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            // getting parent data
            $parentQuery = "SELECT MetadataId, FieldName, FieldValue, PropertyId FROM Properties.UserPropertyMetadataBlocks WHERE PropertyId = $resultBlock";
            $parentResult = DBConnectionFactory::getConnection()->query($parentQuery)->fetchAll(\PDO::FETCH_ASSOC);

            $metadata = [];
            $plotOfLand = false;
            foreach ($result as $key => $value) {
                $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
            }

            foreach ($parentResult as $keyItem => $valueItem) {
                if ($valueItem["FieldValue"] == "Plot of land") {
                    $plotOfLand = true;
                }
            }

            // compensating empty unit data with parent data
            foreach ($parentResult as $keyItem => $valueItem) {
                if (!isset($metadata[$valueItem["FieldName"]])) {
                    if ($valueItem["FieldName"] == "property_bedroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_sittingroom_count" and $plotOfLand or $valueItem["FieldName"] == "property_kitchen_count" and $plotOfLand or $valueItem["FieldName"] == "property_bathroom_count" and $plotOfLand) {

                    } else {
                        $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                    }
                } else if (isset($metadata[$valueItem["FieldName"]]) and empty($metadata[$valueItem["FieldValue"]])) {
                    // $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
                }
            }

            return $metadata;
        }

        return "Invalid ID";
    }

    // Redesigned viewPropertyChildrenMetadata
    public static function viewPropertyChildrenMetadata(int $parentId, array $data, int $floorLevel = 0)
    {
        if (!isset($parentId)) {
            return "Parameter not set";
        }
        //  $propertyType = self::propertyChecker($parentId);

        if ($data["propertyType"] == "estate") {
            // Get children data
            $query = "SELECT a.* FROM Properties.UserPropertyMetadataBlocks a
        INNER JOIN Properties.UserPropertyBlocks b ON a.PropertyId = b.PropertyId
        WHERE b.PropertyFloor = $floorLevel";

            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            // getting parent data
            $propertyParentQuery = "SELECT a.* FROM Properties.UserPropertyMetadata a
        INNER JOIN Properties.UserProperty b ON a.PropertyId = b.PropertyId
        WHERE b.PropertyFloor = $floorLevel";
            $propertyParentResult = DBConnectionFactory::getConnection()->query($propertyParentQuery)->fetchAll(\PDO::FETCH_ASSOC);
            $metadata = [];

            // building result set
            foreach ($result as $key => $value) {
                if (!isset($metadata[$value["PropertyId"]])) {
                    $propertyId = $value["PropertyId"];
                    $metadata[$value["PropertyId"]] = [];
                }

                $metadata[$value["PropertyId"]][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];

                // compensating for empty unit data
                foreach ($propertyParentResult as $key => $value) {
                    if (!isset($metadata[$propertyId][$value["FieldName"]])) {
                        $metadata[$propertyId][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
                    } else if (isset($metadata[$propertyId][$value["FieldName"]]) and empty($metadata[$propertyId][$value["FieldValue"]])) {
                        // $metadata[$propertyId][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
                    }
                }
            }

            return $metadata;

        } else if ($data["propertyType"] == "block") {
            // Get children data
            $query = "SELECT a.* FROM Properties.UserPropertyMetadataUnits a
        INNER JOIN Properties.UserPropertyUnits b ON a.PropertyId = b.PropertyId
        WHERE b.PropertyFloor = $floorLevel";

            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            // getting parent data
            $propertyParentQuery = "SELECT a.* FROM Properties.UserPropertyMetadataBlocks a
        INNER JOIN Properties.UserPropertyBlocks b ON a.PropertyId = b.PropertyId
        WHERE b.PropertyFloor = $floorLevel";
            $propertyParentResult = DBConnectionFactory::getConnection()->query($propertyParentQuery)->fetchAll(\PDO::FETCH_ASSOC);
            $metadata = [];

            // building result set
            foreach ($result as $key => $value) {
                if (!isset($metadata[$value["PropertyId"]])) {
                    $propertyId = $value["PropertyId"];
                    $metadata[$value["PropertyId"]] = [];
                }

                $metadata[$value["PropertyId"]][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];

                // compensating for empty unit data
                foreach ($propertyParentResult as $key => $value) {
                    if (!isset($metadata[$propertyId][$value["FieldName"]])) {
                        $metadata[$propertyId][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
                    } else if (isset($metadata[$propertyId][$value["FieldName"]]) and empty($metadata[$propertyId][$value["FieldValue"]])) {
                        //  $metadata[$propertyId][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
                    }
                }
            }

            return $metadata;

        } else if ($data["propertyType"] == "unit") {

            return "Property Unit - No Children";

        }

        return "No Children Data";
    }

    // Redesigned viewPropertyChildrenMetadata
    public static function viewPropertyChildrenMetadataTest(int $parentId, array $data, int $floorLevel = 0)
    {
        if (!isset($parentId)) {
            return "Parameter not set";
        }
        //  $propertyType = self::propertyChecker($parentId);

        if ($data["propertyType"] == "estate") {
            // Get children data
            $query = "SELECT a.* FROM Properties.UserPropertyMetadataBlocks a
         INNER JOIN Properties.UserPropertyBlocks b ON a.PropertyId = b.PropertyId
         WHERE b.PropertyFloor = $floorLevel";

            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            // getting parent data
            $propertyParentQuery = "SELECT a.* FROM Properties.UserPropertyMetadata a
         INNER JOIN Properties.UserProperty b ON a.PropertyId = b.PropertyId
         WHERE b.PropertyFloor = $floorLevel";
            $propertyParentResult = DBConnectionFactory::getConnection()->query($propertyParentQuery)->fetchAll(\PDO::FETCH_ASSOC);
            $metadata = [];

            // building result set
            foreach ($result as $key => $value) {
                if (!isset($metadata[$value["PropertyId"]])) {
                    $propertyId = $value["PropertyId"];
                    $metadata[$value["PropertyId"]] = [];
                }

                $metadata[$value["PropertyId"]][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];

                // compensating for empty unit data
                foreach ($propertyParentResult as $key => $value) {
                    if (!isset($metadata[$propertyId][$value["FieldName"]])) {
                        $metadata[$propertyId][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
                    } else if (isset($metadata[$propertyId][$value["FieldName"]]) and empty($metadata[$propertyId][$value["FieldValue"]])) {
                        // $metadata[$propertyId][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
                    }
                }
            }

            return $metadata;

        } else if ($data["propertyType"] == "block") {
            // Get children data
            $query = "SELECT a.* FROM Properties.UserPropertyMetadataUnits a
         INNER JOIN Properties.UserPropertyUnits b ON a.PropertyId = b.PropertyId
         WHERE b.PropertyFloor = $floorLevel";

            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            // getting parent data
            $propertyParentQuery = "SELECT a.* FROM Properties.UserPropertyMetadataBlocks a
         INNER JOIN Properties.UserPropertyBlocks b ON a.PropertyId = b.PropertyId
         WHERE b.PropertyFloor = $floorLevel";
            $propertyParentResult = DBConnectionFactory::getConnection()->query($propertyParentQuery)->fetchAll(\PDO::FETCH_ASSOC);
            $metadata = [];

            // building result set
            foreach ($result as $key => $value) {
                if (!isset($metadata[$value["PropertyId"]])) {
                    $propertyId = $value["PropertyId"];
                    $metadata[$value["PropertyId"]] = [];
                }

                $metadata[$value["PropertyId"]][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];

                // compensating for empty unit data
                foreach ($propertyParentResult as $key => $value) {
                    if (!isset($metadata[$propertyId][$value["FieldName"]])) {
                        $metadata[$propertyId][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
                    } else if (isset($metadata[$propertyId][$value["FieldName"]]) and empty($metadata[$propertyId][$value["FieldValue"]])) {
                        //  $metadata[$propertyId][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
                    }
                }
            }

            return $metadata;

        } else if ($data["propertyType"] == "unit") {

            return "Property Unit - No Children";

        }

        return "No Children Data";
    }

    // Redesigned editPropertyMetadata Estate Tester
    public static function editPropertyMetadataEstateTester(int $propertyId, array $metadata = [])
    {

        if ($propertyId == 0 or empty($metadata)) {
            return "Parameters not set";
        }

        if (!isset($propertyId)) {
            return "Parameter not set";
        }

        $queries = [];

        // create counters
        $counter = 0;
        $counterExtra = 0;
        $initialCheck = false;

        foreach ($metadata as $key => $value) {
            /**@algo: Storing images and other base64 objects in the DB is inefficient.
             *  Check if $value is a base64 encoded object, export object to solution storage and store ref to this object as $key.
             *
             * Base64 String Format: <data_type>;base64,<md or a SHA component>
             * Split string using ;base64, and expect two components in an array to conclude
             * that we have a base64
             * **/

            if (self::isJSON($value)) { // check for json and array conversion
                $value = str_replace('&#39;', '"', $value);
                $value = str_replace('&#34;', '"', $value);
                $value = html_entity_decode($value);
                $value = json_decode($value, true);

            }

            if (is_array($value)) {

                // check for images and their handling ...
                foreach ($value as $keyItem => $valueItem) {
                    if (is_string($valueItem)) {
                        $value[$keyItem] = $valueItem;
                    } else {
                        $value[$keyItem] = json_encode($valueItem);
                    }

                }

                $value = json_encode($value);

            } else {
                if ($key == "propertyFeaturePhoto") {

                    if (file_exists($_FILES["propertyFeaturePhotoImg"]["tmp_name"])) {
                        $uploadDir = '/var/www/html/kubo-core/uploads/';
                        $uploadFile = $uploadDir . $_FILES['propertyFeaturePhotoImg']['name'];

                        if (move_uploaded_file($_FILES["propertyFeaturePhotoImg"]["tmp_name"], $uploadFile)) {
                            $dataImg = [
                                "singleFile" => $uploadFile,
                            ];

                            $imageDataResult = self::uploadSingleImage($dataImg);

                            $value = $imageDataResult;
                        }

                    }

                }

                if ($key == "propertyPhotos") {

                    if (!empty($_FILES["propertyPhotosImgs"]["tmp_name"])) {
                        //  $files = array_filter($_FILES["propertyPhotosImgs"]);

                        $dataImg = [
                            "multipleFiles" => $_FILES,
                        ];

                        $imageDataResult = self::uploadMultipleImages($dataImg);

                        if ($imageDataResult == "failed") { // @todo: check properly to ensure
                            $value = "failed";
                        } else {
                            $value = json_encode($imageDataResult);
                        }
                    }
                }

                if ($key == "propertyTitlePhotos") {

                    if (!empty($_FILES["propertyTitlePhotosImgs"]["tmp_name"])) {
                        //  $files = array_filter($_FILES["propertyTitlePhotosImgs"]);

                        if (is_array($_FILES["propertyTitlePhotosImgs"]["tmp_name"])) {

                            $dataImg = [
                                "multipleFiles" => $_FILES,
                            ];

                            $imageDataResult = self::uploadMultipleImages($dataImg);
                            return $imageDataResult;
                            if ($imageDataResult == "failed") { // @todo: check properly to ensure
                                $value = "failed";
                            } else {
                                $value = json_encode($imageDataResult);
                            }
                        } else {
                            $dataImg = [
                                "singleFile" => $_FILES["propertyTitlePhotosImgs"]["tmp_name"],
                            ];

                            $imageDataResult = self::uploadSingleImage($dataImg);

                            if ($imageDataResult == "failed") { // @todo: check properly to ensure
                                $value = "failed";
                            } else {
                                $value = $imageDataResult;
                            }
                        }

                    }
                }

            }

            $keyId = self::camelToSnakeCase($key);

            $counter++;

            // chaining queries for optimized operation
            $queries[] = "BEGIN TRANSACTION;" .
                "DECLARE @rowcount" . $counter . " INT;" .
                "UPDATE Properties.UserPropertyMetadata SET FieldValue='$value' WHERE FieldName='$keyId' AND PropertyId=$propertyId " .
                "SET @rowcount" . $counter . " = @@ROWCOUNT " .
                "BEGIN TRY " .
                "IF @rowcount" . $counter . " = 0 BEGIN INSERT INTO Properties.UserPropertyMetadata (PropertyId, FieldName, FieldValue) VALUES ($propertyId, '$keyId', '$value') END;" .
                "END TRY BEGIN CATCH SELECT ERROR_NUMBER() AS ErrorNumber,ERROR_MESSAGE() AS ErrorMessage; END CATCH " .
                "COMMIT TRANSACTION;";

        }

        $query = implode(";", $queries);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;

    }

    // Redesigned editPropertyMetadata Estate
    public static function editPropertyMetadataEstate(int $propertyId, array $metadata = [])
    {

        if ($propertyId == 0 or empty($metadata)) {
            return "Parameters not set";
        }

        if (!isset($propertyId)) {
            return "Parameter not set";
        }

        // $propertyType = self::propertyChecker($propertyId);

        $queries = [];

        // create counters
        $counter = 0;
        $counterExtra = 0;
        $initialCheck = false;

        foreach ($metadata as $key => $value) {
            /**@algo: Storing images and other base64 objects in the DB is inefficient.
             *  Check if $value is a base64 encoded object, export object to solution storage and store ref to this object as $key.
             *
             * Base64 String Format: <data_type>;base64,<md or a SHA component>
             * Split string using ;base64, and expect two components in an array to conclude
             * that we have a base64
             * **/

            if (self::isJSON($value)) { // check for json and array conversion
                $value = str_replace('&#39;', '"', $value);
                $value = str_replace('&#34;', '"', $value);
                $value = html_entity_decode($value);
                $value = json_decode($value, true);

            }

            if (is_array($value)) {

                // check for images and their handling ...
                foreach ($value as $keyItem => $valueItem) {
                    if (is_string($valueItem)) {
                        $value[$keyItem] = $valueItem;
                    } else {
                        $value[$keyItem] = json_encode($valueItem);
                    }

                }

                $value = json_encode($value);

            } else {
                if ($key == "propertyFeaturePhoto") {

                    if (file_exists($_FILES["propertyFeaturePhotoImg"]["tmp_name"])) {
                        $uploadDir = '/var/www/html/kubo-core/uploads/';
                        $uploadFile = $uploadDir . $_FILES['propertyFeaturePhotoImg']['name'];

                        if (move_uploaded_file($_FILES["propertyFeaturePhotoImg"]["tmp_name"], $uploadFile)) {
                            $dataImg = [
                                "singleFile" => $uploadFile,
                            ];

                            $imageDataResult = self::uploadSingleImage($dataImg);
                            return $imageDataResult;
                            $value = $imageDataResult;
                        }

                    }

                }

                if ($key == "propertyPhotos") {

                    if (!empty($_FILES["propertyPhotosImgs"]["tmp_name"])) {
                        //  $files = array_filter($_FILES["propertyPhotosImgs"]);

                        $dataImg = [
                            "multipleFiles" => $_FILES,
                        ];

                        $imageDataResult = self::uploadMultipleImages($dataImg);
                        return $imageDataResult;
                        if ($imageDataResult == "failed") { // @todo: check properly to ensure
                            $value = "failed";
                        } else {
                            $value = json_encode($imageDataResult);
                        }
                    }
                }

                if ($key == "propertyTitlePhotos") {

                    if (!empty($_FILES["propertyTitlePhotosImgs"]["tmp_name"])) {
                        //  $files = array_filter($_FILES["propertyTitlePhotosImgs"]);

                        if (is_array($_FILES["propertyTitlePhotosImgs"]["tmp_name"])) {

                            $dataImg = [
                                "multipleFiles" => $_FILES,
                            ];

                            $imageDataResult = self::uploadMultipleImages($dataImg);
                            return $imageDataResult;
                            if ($imageDataResult == "failed") { // @todo: check properly to ensure
                                $value = "failed";
                            } else {
                                $value = json_encode($imageDataResult);
                            }
                        } else {
                            $dataImg = [
                                "singleFile" => $_FILES["propertyTitlePhotosImgs"]["tmp_name"],
                            ];

                            $imageDataResult = self::uploadSingleImage($dataImg);
                            return $imageDataResult;
                            if ($imageDataResult == "failed") { // @todo: check properly to ensure
                                $value = "failed";
                            } else {
                                $value = $imageDataResult;
                            }
                        }

                    }
                }

            }

            $keyId = self::camelToSnakeCase($key);

            $counter++;

            // chaining queries for optimized operation
            $queries[] = "BEGIN TRANSACTION;" .
                "DECLARE @rowcount" . $counter . " INT;" .
                "UPDATE Properties.UserPropertyMetadata SET FieldValue='$value' WHERE FieldName='$keyId' AND PropertyId=$propertyId " .
                "SET @rowcount" . $counter . " = @@ROWCOUNT " .
                "BEGIN TRY " .
                "IF @rowcount" . $counter . " = 0 BEGIN INSERT INTO Properties.UserPropertyMetadata (PropertyId, FieldName, FieldValue) VALUES ($propertyId, '$keyId', '$value') END;" .
                "END TRY BEGIN CATCH SELECT ERROR_NUMBER() AS ErrorNumber,ERROR_MESSAGE() AS ErrorMessage; END CATCH " .
                "COMMIT TRANSACTION;";

        }

        $query = implode(";", $queries);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;

    }

    // Redesigned editPropertyMetadata Block
    public static function editPropertyMetadataBlock(int $propertyId, array $metadata = [])
    {

        if ($propertyId == 0 or empty($metadata)) {
            return "Parameters not set";
        }

        if (!isset($propertyId)) {
            return "Parameter not set";
        }

        // $propertyType = self::propertyChecker($propertyId);

        $queries = [];

        // create counters
        $counter = 0;
        $counterExtra = 0;
        $initialCheck = false;

        foreach ($metadata as $key => $value) {
            /**@algo: Storing images and other base64 objects in the DB is inefficient.
             *  Check if $value is a base64 encoded object, export object to solution storage and store ref to this object as $key.
             *
             * Base64 String Format: <data_type>;base64,<md or a SHA component>
             * Split string using ;base64, and expect two components in an array to conclude
             * that we have a base64
             * **/

            if (self::isJSON($value)) { // check for json and array conversion
                $value = str_replace('&#39;', '"', $value);
                $value = str_replace('&#34;', '"', $value);
                $value = html_entity_decode($value);
                $value = json_decode($value, true);

            }

            if (is_array($value)) {

                // check for images and their handling ...
                foreach ($value as $keyItem => $valueItem) {
                    if (is_string($valueItem)) {
                        $value[$keyItem] = $valueItem;
                    } else {
                        $value[$keyItem] = json_encode($valueItem);
                    }

                }

                $value = json_encode($value);

            } else {
                if ($key == "propertyFeaturePhoto") {

                    if (file_exists($_FILES["propertyFeaturePhotoImg"]["tmp_name"])) {
                        $uploadDir = '/var/www/html/kubo-core/uploads/';
                        $uploadFile = $uploadDir . $_FILES['propertyFeaturePhotoImg']['name'];

                        if (move_uploaded_file($_FILES["propertyFeaturePhotoImg"]["tmp_name"], $uploadFile)) {
                            $dataImg = [
                                "singleFile" => $uploadFile,
                            ];

                            $imageDataResult = self::uploadSingleImage($dataImg);
                            return $imageDataResult;
                            $value = $imageDataResult;
                        }

                    }

                }

                if ($key == "propertyPhotos") {

                    if (!empty($_FILES["propertyPhotosImgs"]["tmp_name"])) {
                        //  $files = array_filter($_FILES["propertyPhotosImgs"]);

                        $dataImg = [
                            "multipleFiles" => $_FILES,
                        ];

                        $imageDataResult = self::uploadMultipleImages($dataImg);
                        return $imageDataResult;
                        if ($imageDataResult == "failed") { // @todo: check properly to ensure
                            $value = "failed";
                        } else {
                            $value = json_encode($imageDataResult);
                        }
                    }
                }

                if ($key == "propertyTitlePhotos") {

                    if (!empty($_FILES["propertyTitlePhotosImgs"]["tmp_name"])) {
                        //  $files = array_filter($_FILES["propertyTitlePhotosImgs"]);

                        if (is_array($_FILES["propertyTitlePhotosImgs"]["tmp_name"])) {

                            $dataImg = [
                                "multipleFiles" => $_FILES,
                            ];

                            $imageDataResult = self::uploadMultipleImages($dataImg);
                            return $imageDataResult;
                            if ($imageDataResult == "failed") { // @todo: check properly to ensure
                                $value = "failed";
                            } else {
                                $value = json_encode($imageDataResult);
                            }
                        } else {
                            $dataImg = [
                                "singleFile" => $_FILES["propertyTitlePhotosImgs"]["tmp_name"],
                            ];

                            $imageDataResult = self::uploadSingleImage($dataImg);
                            return $imageDataResult;
                            if ($imageDataResult == "failed") { // @todo: check properly to ensure
                                $value = "failed";
                            } else {
                                $value = $imageDataResult;
                            }
                        }

                    }
                }

                if ($key == "propertyDisplayTitle") {
                    $queryUpdate = "UPDATE Properties.UserPropertyUnits SET PropertyTitle = '$value' WHERE PropertyId = $propertyId";
                    $queryResult = DBConnection::getConnection()->exec($queryUpdate);
                    $key = "propertyName";

                }

            }

            $keyId = self::camelToSnakeCase($key);

            $counter++;

            $selectBlockQuery = "SELECT PropertyEstate FROM Properties.UserPropertyBlocks WHERE PropertyId = $propertyId";
            $resultBlockQuery = DBConnectionFactory::getConnection()->query($selectBlockQuery)->fetch(\PDO::FETCH_ASSOC);

            $resultBlockEstate = $resultBlockQuery["PropertyEstate"];

            // chaining queries for optimized operation
            $queries[] = "BEGIN TRANSACTION;" .
                "DECLARE @rowcount" . $counter . " INT;" .
                "UPDATE Properties.UserPropertyMetadataBlocks SET FieldValue='$value' WHERE FieldName='$keyId' AND PropertyId=$propertyId " .
                "SET @rowcount" . $counter . " = @@ROWCOUNT " .
                "BEGIN TRY " .
                "IF @rowcount" . $counter . " = 0 BEGIN INSERT INTO Properties.UserPropertyMetadataBlocks (PropertyId, PropertyEstate, FieldName, FieldValue) VALUES ($propertyId, $resultBlockEstate, '$keyId', '$value') END;" .
                "END TRY BEGIN CATCH SELECT ERROR_NUMBER() AS ErrorNumber,ERROR_MESSAGE() AS ErrorMessage; END CATCH " .
                "COMMIT TRANSACTION;";

        }

        $query = implode(";", $queries);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;

    }

    // Redesigned editPropertyMetadata Unit
    public static function editPropertyMetadataUnit(int $propertyId, array $metadata = [])
    {

        if ($propertyId == 0 or empty($metadata)) {
            return "Parameters not set";
        }

        if (!isset($propertyId)) {
            return "Parameter not set";
        }

        // $propertyType = self::propertyChecker($propertyId);

        $queries = [];

        // create counters
        $counter = 0;
        $counterExtra = 0;
        $initialCheck = false;

        foreach ($metadata as $key => $value) {
            /**@algo: Storing images and other base64 objects in the DB is inefficient.
             *  Check if $value is a base64 encoded object, export object to solution storage and store ref to this object as $key.
             *
             * Base64 String Format: <data_type>;base64,<md or a SHA component>
             * Split string using ;base64, and expect two components in an array to conclude
             * that we have a base64
             * **/

            if (self::isJSON($value)) { // check for json and array conversion
                $value = str_replace('&#39;', '"', $value);
                $value = str_replace('&#34;', '"', $value);
                $value = html_entity_decode($value);
                $value = json_decode($value, true);

            }

            if (is_array($value)) {

                // check for images and their handling ...
                foreach ($value as $keyItem => $valueItem) {
                    if (is_string($valueItem)) {
                        $value[$keyItem] = $valueItem;
                    } else {
                        $value[$keyItem] = json_encode($valueItem);
                    }

                }

                $value = json_encode($value);

            } else {
                if ($key == "propertyFeaturePhoto") {

                    if (file_exists($_FILES["propertyFeaturePhotoImg"]["tmp_name"])) {
                        $uploadDir = '/var/www/html/kubo-core/uploads/';
                        $uploadFile = $uploadDir . $_FILES['propertyFeaturePhotoImg']['name'];

                        if (move_uploaded_file($_FILES["propertyFeaturePhotoImg"]["tmp_name"], $uploadFile)) {
                            $dataImg = [
                                "singleFile" => $uploadFile,
                            ];

                            $imageDataResult = self::uploadSingleImage($dataImg);
                            return $imageDataResult;
                            $value = $imageDataResult;
                        }

                    }

                }

                if ($key == "propertyPhotos") {

                    if (!empty($_FILES["propertyPhotosImgs"]["tmp_name"])) {
                        //  $files = array_filter($_FILES["propertyPhotosImgs"]);

                        $dataImg = [
                            "multipleFiles" => $_FILES,
                        ];

                        $imageDataResult = self::uploadMultipleImages($dataImg);
                        return $imageDataResult;
                        if ($imageDataResult == "failed") { // @todo: check properly to ensure
                            $value = "failed";
                        } else {
                            $value = json_encode($imageDataResult);
                        }
                    }
                }

                if ($key == "propertyTitlePhotos") {

                    if (!empty($_FILES["propertyTitlePhotosImgs"]["tmp_name"])) {
                        //  $files = array_filter($_FILES["propertyTitlePhotosImgs"]);

                        if (is_array($_FILES["propertyTitlePhotosImgs"]["tmp_name"])) {

                            $dataImg = [
                                "multipleFiles" => $_FILES,
                            ];

                            $imageDataResult = self::uploadMultipleImages($dataImg);
                            return $imageDataResult;
                            if ($imageDataResult == "failed") { // @todo: check properly to ensure
                                $value = "failed";
                            } else {
                                $value = json_encode($imageDataResult);
                            }
                        } else {
                            $dataImg = [
                                "singleFile" => $_FILES["propertyTitlePhotosImgs"]["tmp_name"],
                            ];

                            $imageDataResult = self::uploadSingleImage($dataImg);
                            return $imageDataResult;
                            if ($imageDataResult == "failed") { // @todo: check properly to ensure
                                $value = "failed";
                            } else {
                                $value = $imageDataResult;
                            }
                        }

                    }
                }

                if ($key == "propertyDisplayTitle") {
                    $queryUpdate = "UPDATE Properties.UserPropertyUnits SET PropertyTitle = '$value' WHERE PropertyId = $propertyId";
                    $queryResult = DBConnection::getConnection()->exec($queryUpdate);
                    $key = "propertyName";

                }

            }

            $keyId = self::camelToSnakeCase($key);

            $counter++;

            $selectQuery = "SELECT PropertyEstate,PropertyBlock FROM Properties.UserPropertyUnits WHERE PropertyId = $propertyId";
            $resultSelect = DBConnectionFactory::getConnection()->query($selectQuery)->fetch(\PDO::FETCH_ASSOC);

            $resultUnitEstate = $resultSelect["PropertyEstate"];
            $resultUnitBlock = $resultSelect["PropertyBlock"];

            // chaining queries for optimized operation
            $queries[] = "BEGIN TRANSACTION;" .
                "DECLARE @rowcount" . $counter . " INT;" .
                "UPDATE Properties.UserPropertyMetadataUnits SET FieldValue='$value' WHERE FieldName='$keyId' AND PropertyId=$propertyId " .
                "SET @rowcount" . $counter . " = @@ROWCOUNT " .
                "BEGIN TRY " .
                "IF @rowcount" . $counter . " = 0 BEGIN INSERT INTO Properties.UserPropertyMetadataUnits (PropertyId, PropertyEstate, PropertyBlock, FieldName, FieldValue) VALUES ($propertyId, $resultUnitEstate, $resultUnitBlock, '$keyId', '$value') END;" .
                "END TRY BEGIN CATCH SELECT ERROR_NUMBER() AS ErrorNumber,ERROR_MESSAGE() AS ErrorMessage; END CATCH " .
                "COMMIT TRANSACTION;";

        }

        $query = implode(";", $queries);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;

    }

    protected static function checkForAndStoreBase64String($string)
    {
        $base64Components = explode(";base64,", $string);
        $result = [];
        if (
            count($base64Components) == 2 &&
            ((explode(":", $base64Components[0]))[0] == "data")
        ) {
            // we have a base64. Call Storage abstraction.
            $result = \KuboPlugin\Utils\Storage::storeBase64(["object" => $string]);
        } else {
            $result = [
                "status" => false,
                "message" => "Not an image",
            ];
        }

        return $result;
    }

    // @todo refactor methods below

    // Redesigned getDashBoardTotal
    public static function getDashBoardTotal(int $userId)
    {
        $resultArr = [];
        $estateType = 1;
        $unitType = 3;

        if ($userId == 0) {
            return "Parameter not set";
        }

        //fetching estate count
        $resultArr['estate'] = self::getPropertyCount($userId, $estateType);

        //fetching property count
        $resultArr['property'] = self::getPropertyCount($userId, $unitType);

        //fetching mortgage count
        $resultArr['mortgages'] = self::getMortgageCount($userId);

        //fetching reservations count
        $resultArr['reservations'] = self::getReservationCount($userId);

        return $resultArr;

    }

    // Redesigned getEstatePropertyTotalData
    public static function getEstatePropertyTotal(int $propertyId)
    {
        // @todo refactor later

        if ($propertyId == 0) {
            return "Parameter not set";
        }

        //Fetch total estate property units

        $query = "SELECT PropertyId FROM Properties.UserPropertyUnits WHERE PropertyEstate = $propertyId";

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_NUM);

        $propertyCount = count($result);
        return $propertyCount;

    }

    // Redesigned getEstatePropertyAvailableData
    public static function getEstatePropertyAvailable(int $propertyId)
    {
        // @todo refactor later
        $result = [];

        if ($propertyId == 0) {
            return "Parameter not set";
        }

        //Fetch total estate property units
        $query = "SELECT PropertyId FROM Properties.UserPropertyUnits WHERE PropertyEstate = $propertyId";

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_NUM);

        $propertyCount = count($result);

        $query = "SELECT a.PropertyId, b.FieldName, b.FieldValue, b.PropertyEstate  FROM Properties.UserPropertyUnits a INNER JOIN Properties.UserPropertyMetadataUnits b ON a.PropertyId = b.PropertyId
        WHERE b.FieldName = 'property_status' AND b.FieldValue = 1 AND a.PropertyEstate = $propertyId";

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_NUM);
        $propertyTotal = count($result);

        return $propertyCount - $propertyTotal;

    }

    // Redesigned getPropertyCount
    protected static function getPropertyCount(int $userId, int $entityType)
    {
        if ($entityType == 1) {
            // Fetching property count by type
            $query = "SELECT LinkedEntity FROM Properties.UserProperty WHERE UserId = $userId";
        } else if ($entityType == 3) {
            // Fetching property count by type
            $query = "SELECT LinkedEntity FROM Properties.UserPropertyUnits WHERE UserId = $userId";
        } else {
            return "N/A";
        }

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $propertyCount = count($result);
        return $propertyCount;
    }

    // Redesigned getMortgageCount
    protected static function getMortgageCount(int $userId)
    {
        // Fetching property count
        $query = "SELECT PropertyId FROM Properties.Mortgages  WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserPropertyUnits WHERE UserId = $userId)";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $mortCount = count($result);
        return $mortCount;
    }

    // Redesigned getReservationCount
    protected static function getReservationCount(int $userId)
    {
        // Fetching reservation count
        $query = "SELECT EnquiryId FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserPropertyUnits WHERE UserId = $userId)";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $reserveCount = count($result);
        return $reserveCount;
    }

    public static function searchEstateClient(int $userId, array $data)
    {
        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }

        $fetch = "FIRST";
        $offset = 0;
        $searchTerm = $data['searchTerm'];
        if ($data['offset'] != 0) {
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $resultArr = [];

        // Getting all related estates data
        $query = "SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId AND PropertyId LIKE '%$searchTerm%' OR PropertyTitle LIKE '%$searchTerm%' ORDER BY PropertyId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";
        $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $result) {
            // Getting all related properties metadata
            $query1 = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $result";
            $result1 = DBConnectionFactory::getConnection()->query($query1)->fetchAll(\PDO::FETCH_ASSOC);
            // Getting all Client Enquiry related data
            $query2 = "SELECT Name,EmailAddress,PhoneNumber,MessageJson FROM Properties.Enquiries WHERE  PropertyId = $result AND Name LIKE '%$searchTerm%' OR EmailAddress LIKE '%$searchTerm%' OR PhoneNumber LIKE '%$searchTerm%' ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";
            $result2 = DBConnectionFactory::getConnection()->query($query2)->fetchAll(\PDO::FETCH_ASSOC);
            // Getting all Client Mortgage related data
            $query3 = "SELECT UserParams,PropertyName,MortgageeName FROM Properties.Mortgages WHERE  PropertyId = $result AND Name LIKE '%$searchTerm%' OR EmailAddress LIKE '%$searchTerm%' OR PhoneNumber LIKE '%$searchTerm%' ORDER BY MortgageId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";
            $result3 = DBConnectionFactory::getConnection()->query($query3)->fetchAll(\PDO::FETCH_ASSOC);
            // Returning all data
            $resultArr[$result1["FieldName"]] = ["FieldValue" => $result1["FieldValue"]];
            $resultArr["Client Enquirer"] = $result2;
            $resultArr["Client Mortgagee"] = $result3;
        }

        return $resultArr;

    }

    public static function addAllocation(int $userId, array $data)
    {
        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }
        $queries = [];
        $metadata = [];

        // getting params
        $clientName = $data["clientFullName"] ?? null;
        $phone = $data["phoneNumber"] ?? null;
        $email = $data["email"] ?? null;
        $propertyId = $data["propertyId"] ?? null;

        $metadata["uploadContractSale"] = $data["uploadContractSale"] ?? null;
        $metadata["uploadHoa"] = $data["uploadHoa"] ?? null;
        $metadata["uploadId"] = $data["uploadId"] ?? null;
        $metadata["uploadSignedDeed"] = $data["uploadSignedDeed"] ?? null;

        $inputData = [
            "Recipient" => QB::wrapString($clientName, "'"),
            "Email" => QB::wrapString($email, "'"),
            "Phone" => QB::wrapString($phone, "'"),
            "PropertyId" => $propertyId,
        ];

        // Inserting Allocations Data
        $queries[] = "BEGIN TRANSACTION;" .
            "INSERT INTO Properties.Allocations (UserId, PropertyId, Recipient, Phone, Email) VALUES ($userId, $propertyId, " . $inputData['Recipient'] . ", " . $inputData['Phone'] . ", " . $inputData['Email'] . ");" .
            "COMMIT TRANSACTION;";

        foreach ($metadata as $key => $value) {
            // Inserting Allocations MetaData

            $base64DataResult = self::checkForAndStoreBase64String($value);
            if ($base64DataResult["status"]) { // @todo: check properly to ensure
                $value = $base64DataResult["ref"];
            }

            $keyId = self::camelToSnakeCase($key);
            $queries[] = "BEGIN TRANSACTION;" .
                "UPDATE Properties.AllocationsMetadata SET FieldValue='$value' WHERE FieldName='$keyId' AND PropertyId=$propertyId; " .
                "IF @@ROWCOUNT = 0
            BEGIN INSERT INTO Properties.AllocationsMetadata (PropertyId, FieldName, FieldValue) VALUES ($propertyId, '$keyId', '$value')
            END;" .
                "COMMIT TRANSACTION;";

        }

        $query = implode(";", $queries);

        $resultData = DBConnectionFactory::getConnection()->exec($query);

        if ($resultData) {

            return "Allocation Successful!";

        } else {
            return "Allocation Not Successful!";
        }

    }

    public static function viewUnitAllocationsData(int $propertyId)
    {
        // $metadata = [];
        // Getting Allocations Data
        if ($propertyId == 0) {
            return "Parameter not set";
        }
        $query = "SELECT * FROM Properties.Allocations WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if ($result) {
            $query1 = "SELECT FieldName, FieldValue FROM Properties.AllocationsMetadata WHERE PropertyId = $propertyId";
            $result1 = DBConnectionFactory::getConnection()->query($query1)->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($result1 as $key => $value) {
                $result[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"]];
            }

            return $result;
        } else {
            return false;
        }

    }

    public static function viewDeveloperName(int $userId)
    {
        if ($userId == 0) {
            return "Parameter not set";
        }
        $userId = $userId ?? null;

        // return data
        $query = "SELECT * FROM Users.UserInfoFieldValues WHERE UserId = $userId AND FieldId = 2";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    // Redesigned uploadEstateData
    public static function uploadEstateData(int $userId, array $data)
    {

        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }

        // Collect inputs
        $username = $data["inputEmail"] ?? null;
        $password = $data["inputPassword"] ?? null;
        $foldername = $data["inputName"] ?? null;
        $initials = $data["inputInitials"] ?? null;
        $metaType = (string) $data["metaType"] ?? "";

        if ($username == null or $password == null or $foldername == null or $initials == null) {
            return "Parameters not set";
        }

        if (isset($_POST["uploadBtn"])) {

            if ($_FILES["geojsons"]["name"] !== "") {

                $fileNameParts = explode(".", $_FILES["geojsons"]["name"]);
                if ($fileNameParts[1] == "zip") {
                    if (file_exists("./tmp/data")) {

                    } else {
                        mkdir("tmp/data");
                    }

                    $path = "tmp/data/";
                    $location = $path . $_FILES["geojsons"]["name"];

                    try {

                        // moved uploaded file
                        if (move_uploaded_file($_FILES["geojsons"]["tmp_name"], $location)) {
                            $zip = new \ZipArchive();
                            if ($zip->open($location)) {
                                $zip->extractTo($path);
                                $zip->close();
                            }

                            $files = scandir($path . $fileNameParts[0]);
                            $fileNames = [];
                            $fileBlockNames = [];
                            foreach ($files as $key => $value) {
                                $fileNames[] = $value;
                            }

                            $blockLength = 0;
                            $blockNumberLength = 0;
                            // validation check
                            if (in_array("ESTATE_BOUNDARY.geojson", $fileNames) and in_array("BLOCKS", $fileNames)) {

                                foreach ($fileNames as $key => $value) {
                                    if ($value == "BLOCKS") {
                                        $blocks = scandir($path . $fileNames[$key]);
                                        $blockLength = count($blocks);
                                    }

                                    if ($value == "BLOCK NUMBERS") {
                                        $blockNumbers = scandir($path . $fileNames[$key]);
                                        $blockNumberLength = count($blockNumbers);
                                    }

                                    if ($blockLength == $blockNumberLength) {

                                        $login = self::scriptLogin($username, $password);
                                        $login = $login["contentData"];

                                        $boundary_geojson = file_get_contents(
                                            "tmp/data/$foldername/ESTATE_BOUNDARY.geojson"
                                        );

                                        try {
                                            // inserting ESTATE_BOUNDARY.geojson
                                            $result = self::indexPropertyEstate($login, $boundary_geojson, $foldername, $metaType, 0);
                                        } catch (Exception $e) {
                                            return " Failed  \n" . $e->getMessage(); // @todo  return the Exception error and/or terminate
                                        }
                                        $selectQuery = "SELECT UserId FROM Properties.MapDataUploadStata WHERE UserId = $userId AND FolderName = '$foldername' AND Initials = '$initials' AND UploadStatus = 'processing' OR UserId = $userId AND FolderName = '$foldername' AND Initials = '$initials' AND UploadStatus = 'uploaded' OR UserId = $userId AND FolderName = '$foldername' AND Initials = '$initials' AND UploadStatus = 'uploading'";
                                        $selectExec = DBConnectionFactory::getConnection()->query($selectQuery)->fetchAll(\PDO::FETCH_ASSOC);

                                        if (count($selectExec) > 1) {
                                            // verify multiple progress check
                                            $updateQuery = "UPDATE Properties.MapDataUploadStata SET UploadStatus = 'processing' WHERE UserId = $userId AND FolderName = '$foldername' AND Initials = '$initials'";
                                            $updateExec = DBConnectionFactory::getConnection()->exec($updateQuery);
                                        } else {
                                            // progress check
                                            $insertQuery = "INSERT INTO Properties.MapDataUploadStata (UserId,FolderName,Initials,UploadStatus) VALUES ($userId,'$foldername','$initials','processing')";
                                            $resultExec = DBConnectionFactory::getConnection()->exec($insertQuery);

                                        }

                                        $data = json_encode($data);
                                        $result["data"] = $data;
                                        return $result;

                                    }

                                }

                            } else {
                                return "Estate Boundary File not found !!! \n";
                            }

                        } else {
                            return "File not Uploaded ! \n";
                        }
                    } catch (\Exception $e) {
                        return $e->getMessage();
                    }
                } else {
                    return "File not Zip ! \n";
                }
            } else {
                return "File Error ! \n";
            }
        } else {
            return "No Data ! \n";
        }
    }

    // Redesigned uploadEstateData
    public static function uploadEstateDataTest(int $userId, array $data)
    {

        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }

        // Collect inputs
        $username = $data["inputEmail"] ?? null;
        $password = $data["inputPassword"] ?? null;
        $foldername = $data["inputName"] ?? null;
        $initials = $data["inputInitials"] ?? null;
        $metaType = (string) $data["metaType"] ?? "";

        if ($username == null or $password == null or $foldername == null or $initials == null) {
            return "Parameters not set";
        }

        if (isset($_POST["uploadBtn"])) {

            if ($_FILES["geojsons"]["name"] !== "") {

                $fileNameParts = explode(".", $_FILES["geojsons"]["name"]);
                if ($fileNameParts[1] == "zip") {
                    if (file_exists("./tmp/data")) {

                    } else {
                        mkdir("tmp/data");
                    }

                    $path = "tmp/data/";
                    $location = $path . $_FILES["geojsons"]["name"];

                    try {

                        // moved uploaded file
                        if (move_uploaded_file($_FILES["geojsons"]["tmp_name"], $location)) {
                            $zip = new \ZipArchive();
                            if ($zip->open($location)) {
                                $zip->extractTo($path);
                                $zip->close();
                            }

                            $files = scandir($path . $fileNameParts[0]);
                            $fileNames = [];
                            $fileBlockNames = [];
                            foreach ($files as $key => $value) {
                                $fileNames[] = $value;
                            }

                            $blockLength = 0;
                            $blockNumberLength = 0;
                            // validation check
                            if (in_array("ESTATE_BOUNDARY.geojson", $fileNames) and in_array("BLOCKS", $fileNames)) {

                                foreach ($fileNames as $key => $value) {
                                    if ($value == "BLOCKS") {
                                        $blocks = scandir($path . $fileNames[$key]);
                                        $blockLength = count($blocks);
                                    }

                                    if ($value == "BLOCK NUMBERS") {
                                        $blockNumbers = scandir($path . $fileNames[$key]);
                                        $blockNumberLength = count($blockNumbers);
                                    }

                                    if ($blockLength == $blockNumberLength) {

                                        // $login = self::scriptLogin($username, $password);
                                        // $login = $login["contentData"];

                                        $boundary_geojson = file_get_contents(
                                            "tmp/data/$foldername/ESTATE_BOUNDARY.geojson"
                                        );

                                        try {
                                            // inserting ESTATE_BOUNDARY.geojson
                                            $result = self::indexPropertyEstateTest($userId, $boundary_geojson, $foldername, $metaType, 0);
                                        } catch (Exception $e) {
                                            return " Failed  \n" . $e->getMessage(); // @todo  return the Exception error and/or terminate
                                        }
                                        $selectQuery = "SELECT UserId FROM Properties.MapDataUploadStata WHERE UserId = $userId AND FolderName = '$foldername' AND Initials = '$initials' AND UploadStatus = 'processing' OR UserId = $userId AND FolderName = '$foldername' AND Initials = '$initials' AND UploadStatus = 'uploaded' OR UserId = $userId AND FolderName = '$foldername' AND Initials = '$initials' AND UploadStatus = 'uploading'";
                                        $selectExec = DBConnectionFactory::getConnection()->query($selectQuery)->fetchAll(\PDO::FETCH_ASSOC);

                                        if (count($selectExec) > 1) {
                                            // verify multiple progress check
                                            $updateQuery = "UPDATE Properties.MapDataUploadStata SET UploadStatus = 'processing' WHERE UserId = $userId AND FolderName = '$foldername' AND Initials = '$initials'";
                                            $updateExec = DBConnectionFactory::getConnection()->exec($updateQuery);
                                        } else {
                                            // progress check
                                            $insertQuery = "INSERT INTO Properties.MapDataUploadStata (UserId,FolderName,Initials,UploadStatus) VALUES ($userId,'$foldername','$initials','processing')";
                                            $resultExec = DBConnectionFactory::getConnection()->exec($insertQuery);

                                        }

                                        $data = json_encode($data);
                                        $result["data"] = $data;
                                        return $result;

                                    }

                                }

                            } else {
                                return "Estate Boundary File not found !!! \n";
                            }

                        } else {
                            return "File not Uploaded ! \n";
                        }
                    } catch (\Exception $e) {
                        return $e->getMessage();
                    }
                } else {
                    return "File not Zip ! \n";
                }
            } else {
                return "File Error ! \n";
            }
        } else {
            return "No Data ! \n";
        }
    }

    // Redesigned uploadBlockData
    public static function uploadBlockData(int $userId, array $data)
    {

        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }

        // Collecting inputs
        $username = $data["inputEmail"] ?? null;
        $password = $data["inputPassword"] ?? null;
        $foldername = $data["inputName"] ?? null;
        $initials = $data["inputInitials"] ?? null;
        $estateData = $data["estateData"] ?? [];
        $metaType = (string) $data["metaType"] ?? "";

        if (self::isJSON($estateData)) { // json check and array conversion
            if (is_string($estateData)) {
                $estateData = str_replace('&#39;', '"', $estateData);
                $estateData = str_replace('&#34;', '"', $estateData);
                $estateData = html_entity_decode($estateData);
                $estateData = json_decode($estateData, true);
            }

        }

        if ($username == null or $password == null or $foldername == null or $initials == null) {
            return "Parameters not set";
        }

        $login = self::scriptLogin($username, $password);
        $login = $login["contentData"];

        $dir = "tmp/data/$foldername/BLOCKS/";
        $files = scandir($dir);
        $blocks = [];
        $blockIds = [];
        $result = [];
        if (count($files) > 0) {

            // looping and inserting values
            foreach ($files as $key => $file) {
                if (pathinfo($dir . $file, PATHINFO_EXTENSION) == "geojson") {
                    $geojson = file_get_contents($dir . $file);
                    $geojson = str_replace("\"", "'", $geojson);
                    try {
                        $file = str_replace(".geojson", " $initials " . time(), $file);
                        $result = self::indexPropertyBlock($login, $geojson, $file, $metaType, $estateData['EstateId'], $estateData['EntityId']); // edit last insert entityId of Estate
                        // $blocks["BLOCK $key"] = $result['contentData']['EntityId']; // @todo build $blocks array

                    } catch (Exception $e) {
                        return $file . " failed  \n" . $e->getMessage(); // @todo  return the Exception error and/or terminate
                    }

                }
            }

            // returning block data array
            $queryBlocks = "SELECT EntityName,EntityId,EntityBlock FROM SpatialEntities.Entities WHERE EntityParent = " . $estateData['EntityId'];
            $resultBlocks = DBConnectionFactory::getConnection()->query($queryBlocks)->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($resultBlocks as $keyBlock => $blockValue) {
                $blocks[$blockValue["EntityName"]] = $blockValue["EntityId"];
                $blockIds[$blockValue["EntityName"]] = $blockValue["EntityBlock"];
            }

            // checking for extra empty blocks
            $dir = "tmp/data/$foldername/BLOCK EXTRA/";
            $files = scandir($dir);
            if (count($files) > 0) {
                foreach ($files as $file) {
                    if (pathinfo($dir . $file, PATHINFO_EXTENSION) == "geojson") {
                        $geojson = file_get_contents($dir . $file);
                        $geojson = str_replace("\"", "'", $geojson);
                        try {
                            $file = str_replace(".geojson", " $initials", $file);
                            $result = self::indexPropertyBlock($login, $geojson, $file, $metaType, $estateData['EstateId'], $estateData['EntityId']); // edit last insert entityId of Estate
                            // @todo no build $blocks array
                        } catch (Exception $e) {
                            return $file . " failed  \n" . $e->getMessage(); // @todo  return the Exception error and/or terminate
                        }

                    }
                }
            }
        }

        // progress check
        $insertQuery = "UPDATE Properties.MapDataUploadStata SET UploadStatus = 'uploading' WHERE UserId = $userId AND FolderName = '$foldername' AND Initials =  '$initials'";
        $resultExec = DBConnectionFactory::getConnection()->exec($insertQuery);

        $resultData = [];
        $data = json_encode($data);
        $resultData["data"] = $data;
        $blocks = json_encode($blocks);
        $resultData["blocks"] = $blocks;
        $resultData["blockIds"] = $blockIds;

        return $resultData;
    }

    // Redesigned uploadBlockData
    public static function uploadBlockDataTest(int $userId, array $data)
    {

        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }

        // Collecting inputs
        $username = $data["inputEmail"] ?? null;
        $password = $data["inputPassword"] ?? null;
        $foldername = $data["inputName"] ?? null;
        $initials = $data["inputInitials"] ?? null;
        $estateData = $data["estateData"] ?? [];
        $metaType = (string) $data["metaType"] ?? "";

        if (self::isJSON($estateData)) { // json check and array conversion
            if (is_string($estateData)) {
                $estateData = str_replace('&#39;', '"', $estateData);
                $estateData = str_replace('&#34;', '"', $estateData);
                $estateData = html_entity_decode($estateData);
                $estateData = json_decode($estateData, true);
            }

        }

        if ($username == null or $password == null or $foldername == null or $initials == null) {
            return "Parameters not set";
        }

        // $login = self::scriptLogin($username, $password);
        // $login = $login["contentData"];

        $dir = "tmp/data/$foldername/BLOCKS/";
        $files = scandir($dir);
        $blocks = [];
        $blockIds = [];
        $result = [];
        if (count($files) > 0) {

            // looping and inserting values
            foreach ($files as $key => $file) {
                if (pathinfo($dir . $file, PATHINFO_EXTENSION) == "geojson") {
                    $geojson = file_get_contents($dir . $file);
                    $geojson = str_replace("\"", "'", $geojson);
                    try {
                        $file = str_replace(".geojson", "", $file);
                        $result[] = self::indexPropertyBlockTest($userId, $geojson, $file, $metaType, (int)$estateData['EstateId']); // edit last insert entityId of Estate
                        // $blocks["BLOCK $key"] = $result['contentData']['EntityId']; // @todo build $blocks array

                    } catch (Exception $e) {
                        return $file . " failed  \n" . $e->getMessage(); // @todo  return the Exception error and/or terminate
                    }

                }
            }

            $queries = [];

            foreach ($result as $keyItem => $valueItem) {
                $queries[] = self::newPropertyBlockTest($valueItem);
            }

            $queryInsertBlocks = implode(";", $queries);

            $resultSet = DBConnectionFactory::getConnection()->exec($queryInsertBlocks);

            // returning block data array
            $queryBlocks = "SELECT PropertyTitle,PropertyId FROM Properties.UserPropertyBlocks WHERE PropertyEstate = " . (int)$estateData['EstateId'];
            $resultBlocks = DBConnectionFactory::getConnection()->query($queryBlocks)->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($resultBlocks as $keyBlock => $blockValue) {
                $blockIds[$blockValue["PropertyTitle"]] = $blockValue["PropertyId"];
            }

            // checking for extra empty blocks
            $dir = "tmp/data/$foldername/BLOCK EXTRA/";
            $files = scandir($dir);
            $resultExtra = [];
            if (count($files) > 0) {
                foreach ($files as $file) {
                    if (pathinfo($dir . $file, PATHINFO_EXTENSION) == "geojson") {
                        $geojson = file_get_contents($dir . $file);
                        $geojson = str_replace("\"", "'", $geojson);
                        try {
                            $file = str_replace(".geojson", " $initials", $file);
                            $resultExtra = self::indexPropertyBlockTest($userId, $geojson, $file, $metaType, (int)$estateData['EstateId']); // edit last insert entityId of Estate
                            // @todo no build $blocks array
                        } catch (Exception $e) {
                            return $file . " failed  \n" . $e->getMessage(); // @todo  return the Exception error and/or terminate
                        }

                    }
                }
            }
        }

        // progress check
        $insertQuery = "UPDATE Properties.MapDataUploadStata SET UploadStatus = 'uploading' WHERE UserId = $userId AND FolderName = '$foldername' AND Initials =  '$initials'";
        $resultExec = DBConnectionFactory::getConnection()->exec($insertQuery);

        $resultData = [];
        $data = json_encode($data);
        $resultData["data"] = $data;
        $resultData['estateId'] = $estateData['EstateId'];
        $resultData["blockIds"] = $blockIds;
        $resultData["result"] = $resultSet;

        return $resultData;

    }

    // Redesigned uploadUnitData
    public static function uploadUnitData(int $userId, array $data)
    {

        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }

        // collecting inputs
        $username = $data["inputEmail"] ?? null;
        $password = $data["inputPassword"] ?? null;
        $foldername = $data["inputName"] ?? null;
        $initials = $data["inputInitials"] ?? null;
        $blockers = $data["blockData"] ?? [];
        $blockersIds = $data["blockDataIds"] ?? [];
        $estateId = $data["estateId"] ?? 0;
        $metaType = (string) $data["metaType"] ?? "";

        $blocks = [];
        $blockIds = [];

        $login = self::scriptLogin($username, $password);
        $login = $login["contentData"];

        if (self::isJSON($blockers)) { // json check and array conversion
            if (is_string($blockers)) {
                $blockers = str_replace('&#39;', '"', $blockers);
                $blockers = str_replace('&#34;', '"', $blockers);
                $blockers = html_entity_decode($blockers);
                $blockers = json_decode($blockers, true);
            }

        }

        if (self::isJSON($blockersIds)) { // json check and array conversion
            if (is_string($blockersIds)) {
                $blockersIds = str_replace('&#39;', '"', $blockersIds);
                $blockersIds = str_replace('&#34;', '"', $blockersIds);
                $blockersIds = html_entity_decode($blockersIds);
                $blockersIds = json_decode($blockersIds, true);
            }

        }

        foreach ($blockers as $keyBlock => $blockValue) { // rebuilding block array
            $blocks[explode(" $initials", $keyBlock)[0]] = $blockValue;
        }

        foreach ($blockersIds as $keyItemBlock => $blockItemValue) { // rebuilding block array
            $blockIds[explode(" $initials", $keyItemBlock)[0]] = $blockItemValue;
        }

        if ($username == null or $password == null or $foldername == null or $initials == null) {
            return "Parameters not set";
        }

        for ($i = 1; $i <= count($blocks); $i++) {
            $block = "BLOCK $i";
            $dir = "tmp/data/$foldername/BLOCK NUMBERS/$block/";
            $files = scandir($dir);
            foreach ($files as $file) {
                if (pathinfo($dir . $file, PATHINFO_EXTENSION) == "geojson") {
                    $geojson = file_get_contents($dir . $file);
                    $geojson = str_replace("\"", "'", $geojson);
                    try {
                        $file = str_replace("Name_", "$block (", $file);
                        $file = str_replace(".geojson", "", $file);
                        // inserting values
                        $result = self::indexPropertyUnit($login, $geojson, "$initials " . time() . $file, $metaType, $estateId, $blockIds[$block], $blocks[$block]);

                    } catch (Exception $e) {
                        return $file . " failed  \n" . $e->getMessage(); // @todo  return the Exception error and/or terminate
                    }

                    // echo "\nDone with " . $file; // @todo  return the success data
                    if ($i > 7) {
                        sleep(5);
                    }

                }
            }

            // echo "\nDone with $block"; // @todo  return the success data
        }

        // progress check
        $insertQuery = "UPDATE Properties.MapDataUploadStata SET UploadStatus = 'uploaded' WHERE UserId = $userId AND FolderName = '$foldername' AND Initials = '$initials'";
        $resultExec = DBConnectionFactory::getConnection()->exec($insertQuery);

        // Deleting uploaded folder and zip file
        \KuboPlugin\Utils\Util::recurseRmdir("tmp/data/$foldername");
        unlink("tmp/data/$foldername" . ".zip");

        return "Successfully Uploaded";
    }

    // Redesigned uploadUnitData
    public static function uploadUnitDataTest(int $userId, array $data)
    {

        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }

        // collecting inputs
        $username = $data["inputEmail"] ?? null;
        $password = $data["inputPassword"] ?? null;
        $foldername = $data["inputName"] ?? null;
        $initials = $data["inputInitials"] ?? null;
        $blockersIds = $data["blockDataIds"] ?? [];
        $estateId = $data["estateId"] ?? 0;
        $startCounter = (int) $data["startCounter"] ?? 1;
        $metaType = (string) $data["metaType"] ?? "";

        // $login = self::scriptLogin($username, $password);
        // $login = $login["contentData"];

        if (self::isJSON($blockersIds)) { // json check and array conversion
            if (is_string($blockersIds)) {
                $blockersIds = str_replace('&#39;', '"', $blockersIds);
                $blockersIds = str_replace('&#34;', '"', $blockersIds);
                $blockersIds = html_entity_decode($blockersIds);
                $blockersIds = json_decode($blockersIds, true);
            }

        }


        if ($username == null or $password == null or $foldername == null or $initials == null) {
            return "Parameters not set";
        }

        for ($i = $startCounter; $i <= count($blockersIds); $i++) {
            $block = "BLOCK $i";
            $dir = "tmp/data/$foldername/BLOCK NUMBERS/$block/";
            $files = scandir($dir);
            $result = [];
            foreach ($files as $file) {
                if (pathinfo($dir . $file, PATHINFO_EXTENSION) == "geojson") {
                    $geojson = file_get_contents($dir . $file);
                    $geojson = str_replace("\"", "'", $geojson);
                    try {
                        $file = str_replace("name_", "$block (", $file);
                        $file = str_replace(".geojson", ")", $file);
                        // inserting values
                        $result[] = self::indexPropertyUnitTest($userId, $geojson, $file, $metaType, (int)$estateId, $blockersIds[$block]);

                    } catch (Exception $e) {
                        return $file . " failed  \n" . $e->getMessage(); // @todo  return the Exception error and/or terminate
                    }

                }
            }

            $queries = [];

            foreach ($result as $keyItem => $valueItem) {
                $queries[] = self::newPropertyUnitTest($valueItem);
            }

            $queryInsertUnits = implode(";", $queries);

            $resultSet = DBConnectionFactory::getConnection()->exec($queryInsertUnits);
        }

        // progress check
        $insertQuery = "UPDATE Properties.MapDataUploadStata SET UploadStatus = 'uploaded' WHERE UserId = $userId AND FolderName = '$foldername' AND Initials = '$initials'";
        $resultExec = DBConnectionFactory::getConnection()->exec($insertQuery);

        // Deleting uploaded folder and zip file
        \KuboPlugin\Utils\Util::recurseRmdir("tmp/data/$foldername");
        unlink("tmp/data/$foldername" . ".zip");

        return "Successfully Uploaded";
    }

    public static function viewMapDataUploadStatus(int $userId, array $data)
    {

        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }

        // get inputs
        $foldername = $data["inputName"] ?? null;
        $initials = $data["inputInitials"] ?? null;
        $level = $data["uploadLevel"] ?? null;
        $userId = $userId ?? null;

        // return progress data
        $query = "SELECT * FROM Properties.MapDataUploadStata WHERE UserId = $userId AND FolderName = '$foldername' AND Initials = '$initials'";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if ($level == "estate") {
            // return estate data
            $query = "SELECT * FROM Properties.UserProperty WHERE PropertyTitle = '$foldername'";
            $result["estate"] = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        } else if ($level == "block") {
            // return estate data
            $query = "SELECT PropertyId FROM Properties.UserProperty WHERE PropertyTitle = '$foldername'";
            $estateData = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $estateId = $estateData[0]['PropertyId'];

            // return block data
            $query = "SELECT * FROM Properties.UserPropertyBlocks WHERE PropertyEstate = $estateId";
            $result["block"] = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        } else if ($level == "unit") {
            // return estate data
            $query = "SELECT PropertyId FROM Properties.UserProperty WHERE PropertyTitle = '$foldername'";
            $estateData = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $estateId = $estateData[0]['PropertyId'];
            // return block Id data
            $query = "SELECT PropertyId FROM Properties.UserPropertyBlocks WHERE PropertyEstate = $estateId";
            $resultBlock = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($resultBlock as $key => $value) {
                $valueId = $value['PropertyId'];
                // return unit data
                $query = "SELECT * FROM Properties.UserPropertyUnits WHERE PropertyEstate = $estateId AND PropertyBlock = $valueId";
                $result[$valueId]["unit"] = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            }

        } else {

        }

        return $result;
    }

    public static function deleteUploadData(int $userId, array $data)
    {

        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }

        // get inputs
        $folderName = $data["folderName"] ?? "";
        // $propertyId = $data["propertyId"] ?? 0;
        $userId = $userId ?? 0;

        // pik up property data
        $selectQuery = "SELECT PropertyId FROM Properties.UserProperty WHERE PropertyTitle = '$folderName'";
        $resultSelect = DBConnectionFactory::getConnection()->query($selectQuery)->fetchAll(\PDO::FETCH_ASSOC);

        $resultPropertyId = $resultSelect[0]['PropertyId'];

        $queries = [];

        // prepare query
        $queries[] = "DELETE FROM Properties.UserProperty WHERE PropertyId = $resultPropertyId";

        $queries[] = "DELETE FROM SpatialEntities.Entities WHERE EntityName = '$folderName'";

        $queries[] = "DELETE FROM SpatialEntities.Entities WHERE EntityEstate = $resultPropertyId";

        $queries[] = "DELETE FROM Properties.MapDataUploadStata WHERE FolderName = '$folderName'";

        $query = implode(";", $queries);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;
    }

    public static function deleteUploadDataTest(int $userId, array $data)
    {

        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }

        // get inputs
        $folderName = $data["folderName"] ?? "";
        // $propertyId = $data["propertyId"] ?? 0;
        $userId = $userId ?? 0;

        // pik up property data
        $selectQuery = "SELECT PropertyId FROM Properties.UserProperty WHERE PropertyTitle = '$folderName'";
        $resultSelect = DBConnectionFactory::getConnection()->query($selectQuery)->fetchAll(\PDO::FETCH_ASSOC);

        $resultPropertyId = $resultSelect[0]['PropertyId'];

        $queries = [];

        // prepare query
        $queries[] = "DELETE FROM Properties.UserProperty WHERE PropertyId = $resultPropertyId";

        $queries[] = "DELETE FROM Properties.UserPropertyBlocks WHERE PropertyEstate = $resultPropertyId";

        $queries[] = "DELETE FROM Properties.UserPropertyUnits WHERE PropertyEstate = $resultPropertyId";

        $queries[] = "DELETE FROM Properties.MapDataUploadStata WHERE FolderName = '$folderName'";

        $query = implode(";", $queries);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;
    }

    protected static function camelToSnakeCase($string, $sc = "_")
    {
        return strtolower(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', $sc, $string));
    }

    protected static function isJSON($stringData)
    {
        if (is_string($stringData)) {
            $stringData = str_replace('&#39;', '"', $stringData);
            $stringData = str_replace('&#34;', '"', $stringData);
            $stringData = html_entity_decode($stringData);
            return is_string($stringData) && is_array(json_decode($stringData, true)) ? true : false;
        } else {
            return false;
        }

    }

    protected static function getPropertyChildrenIds(int $propertyId, array $floorData = [])
    {
        $floorLevel = 0;

        // build query
        $query = "SELECT PropertyId FROM Properties.UserPropertyBlocks WHERE PropertyEstate = $propertyId";

        if (isset($floorData["floorLevel"])) {
            $floorLevel = $floorData["floorLevel"];
            $query .= " AND a.PropertyFloor = $floorLevel";
        }

        $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $results;
    }

    protected static function inArrayRec($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) { // recursively checking of in_array
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && self::inArrayRec($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

    protected static function scriptLogin($username, $password)
    {
        $host = "https://rest.sytemap.com/v1/login"; //"http://127.0.0.1:5464/v1/login";
        // $host = "http://localhost:9000/v1/login"; //"http://127.0.0.1:5464/v1/login";
        $data = [
            "username" => $username,
            "password" => $password,
        ];
        $response = \KuboPlugin\Utils\Util::clientRequest($host, "POST", $data); // http call

        $accountData = json_decode($response, true);

        return $accountData;
    }

    // Redesigned indexProperty
    protected static function indexPropertyEstate(array $login, string $geojson, string $title, string $metaType, int $parent = 0)
    {
        $data = [
            "user" => $login["userId"],
            "property_title" => $title,
            "property_type" => 1,
            "property_geometry" => $geojson,
            "property_metadata" => [
                "property_description" => "",
                "property_type" => $metaType,
            ],
        ];

        if ($parent != 0) {
            $data["property_type"] = 3;
            $data["property_parent"] = $parent;
        }

        $host = "https://rest.sytemap.com/v1/properties/user-property/new-property-estate"; //"http://127.0.0.1:5464/v1/properties/user-property/new-property-estate";
        // $host = "http://localhost:9000/v1/properties/user-property/new-property-estate"; //"http://127.0.0.1:5464/v1/properties/user-property/new-property-estate";

        $header = "Authorization: " . $login["sessionData"]["token"] . "," . $login["sessionId"] . "," . $login["userId"];

        $response = \KuboPlugin\Utils\Util::clientRequest($host, "POST", $data, $header); // http request

        $response = json_decode($response, true);

        return $response;

        if ($response["errorStatus"] == false) {
            return $response;
        } else {
            self::indexPropertyEstate($login, $geojson, $title, $metaType, $parent);
        }
    }

    // Redesigned indexProperty
    protected static function indexPropertyEstateTest(int $userId, string $geojson, string $title, string $metaType, int $parent = 0)
    {
        $data = [
            "user" => $userId,
            "property_title" => $title,
            "property_type" => $metaType,
            "property_geometry" => $geojson,
            "property_metadata" => [
                "property_description" => "",
                "property_type" => $metaType,
            ],
        ];

        return self::newPropertyEstateTest($data);

    }

    // Redesigned indexBlock
    protected static function indexPropertyBlock(array $login, string $geojson, string $title, string $metaType, int $estateId, int $parent = 0)
    {
        $data = [
            "user" => $login["userId"],
            "property_title" => $title,
            "property_estate_id" => $estateId,
            "property_type" => 1,
            "property_geometry" => $geojson,
            "property_metadata" => [
                "property_description" => "",
                "property_type" => $metaType,
            ],
        ];

        if ($parent != 0) {
            $data["property_type"] = 2;
            $data["property_parent"] = $parent;
        }

        $host = "https://rest.sytemap.com/v1/properties/user-property/new-property-block"; //"http://127.0.0.1:5464/v1/properties/user-property/new-property-block";
        // $host = "http://localhost:9000/v1/properties/user-property/new-property-block"; //"http://127.0.0.1:5464/v1/properties/user-property/new-property-block";

        $header = "Authorization: " . $login["sessionData"]["token"] . "," . $login["sessionId"] . "," . $login["userId"];
        $response = \KuboPlugin\Utils\Util::clientRequest($host, "POST", $data, $header); // http call

        $response = json_decode($response, true);

        return $response;

        if ($response["errorStatus"] == false) {
            return $response;
        } else {
            self::indexPropertyBlock($login, $geojson, $title, $metaType, $estateId, $parent);
        }

    }

    // Redesigned indexBlock
    protected static function indexPropertyBlockTest(int $userId, string $geojson, string $title, string $metaType, int $estateId, int $parent = 0)
    {
        $data = [
            "user" => $userId,
            "property_title" => $title,
            "property_estate_id" => $estateId,
            "property_type" => $metaType,
            "property_geometry" => $geojson,
            "property_metadata" => [
                "property_description" => "",
                "property_type" => $metaType,
            ],
        ];

        return $data;

    }

    // Redesigned indexProperty for units
    protected static function indexPropertyUnit(array $login, string $geojson, string $title, string $metaType, int $estateId, array $blockId, int $parent = 0)
    {
        $data = [
            "user" => $login["userId"],
            "property_title" => $title,
            "property_estate_id" => $estateId,
            "property_block_id" => $blockId,
            "property_type" => 1,
            "property_geometry" => $geojson,
            "property_metadata" => [
                "property_description" => "",
                "property_type" => $metaType,
            ],
        ];

        if ($parent != 0) {
            $data["property_type"] = 3;
            $data["property_parent"] = $parent;
        }

        $host = "https://rest.sytemap.com/v1/properties/user-property/new-property-unit"; //"http://127.0.0.1:5464/v1/properties/user-property/new-property-unit";
        // $host = "http://localhost:9000/v1/properties/user-property/new-property-unit"; //"http://127.0.0.1:5464/v1/properties/user-property/new-property-unit";

        $header = "Authorization: " . $login["sessionData"]["token"] . "," . $login["sessionId"] . "," . $login["userId"];
        $response = \KuboPlugin\Utils\Util::clientRequest($host, "POST", $data, $header); // http request

        $response = json_decode($response, true);

        return $response;

        if ($response["errorStatus"] == false) {
            return $response;
        } else {
            self::indexPropertyUnit($login, $geojson, $title, $metaType, $estateId, $blockId, $parent);
        }
    }

    // Redesigned indexProperty for units
    protected static function indexPropertyUnitTest(int $userId, string $geojson, string $title, string $metaType, int $estateId, string $blockId, int $parent = 0)
    {
        $data = [
            "user" => $userId,
            "property_title" => $title,
            "property_estate_id" => $estateId,
            "property_block_id" => $blockId,
            "property_type" => $metaType,
            "property_geometry" => $geojson,
            "property_metadata" => [
                "property_description" => "",
                "property_type" => $metaType,
            ],
        ];

        return $data;

    }

    protected static function getUploadServerToken()
    {

        $host = "http://ec2-44-201-189-208.compute-1.amazonaws.com/";

        $response = \KuboPlugin\Utils\Util::clientRequest($host, "GET"); // http request

        $response = json_decode($response, true);

        if ($response[0]["status"] == "success") {
            return $response[0]['token'];
        } else {
            return "failed";
        }

    }

    protected static function uploadSingleImage(array $data)
    {

        $token = self::getUploadServerToken();

        if ($token == "failed") {
            return "token error";
        }

        $timerNow = time();

        // $fileOldName = __DIR__ . DIRECTORY_SEPARATOR . "uploads/".$_FILES['propertyFeaturePhotoImg']['name']; // File to upload
        $fileOldName = $data["singleFile"]; // File to upload
        $fileNewName = $_FILES['propertyFeaturePhotoImg']['name']; // File name to be uploaded as

        // if (function_exists('curl_file_create')) {
        //    $filer = \curl_file_create($_FILES["propertyFeaturePhotoImg"]["tmp_name"]);
        //  } else { //
        //    $filer = '@' . realpath($_FILES["propertyFeaturePhotoImg"]["tmp_name"]);
        //  }

        $file_to_upload = new \CURLFile($fileOldName, mime_content_type($fileOldName), $fileNewName);

        $file = $file_to_upload;
        $action = "single";
        $requestType = $data["imageInfo"] ?? "";
        $endpoint = $data["endpoint"] ?? "";

        $data = [
            "fileUpload" => $file,
            "action" => $action,
            "token" => $token,
            "requestType" => $requestType,
            "endpoint" => $endpoint,
        ];

        // return $data;

        /*
        $dataItem = [
        "action" => $action,
        "token" => $token,
        "requestType" => $requestType,
        "endpoint" => $endpoint,
        ];

         */

        // $dataItem = json_encode($dataItem);

        // $dataItem = http_build_query($dataItem);

        $host = "http://ec2-44-201-189-208.compute-1.amazonaws.com/";

        $header = "Content-Type: multipart/form-data; boundary=687898976465498929523510456, Content-Length:" . filesize($fileOldName);

        // $outputRes = shell_exec("curl -X POST $host -H $header -F $dataItem -F fileUpload=@$file");
        // $response = json_decode($outputRes, true);

        $response = \KuboPlugin\Utils\Util::clientRequest($host, "POST", $data, $header); // http request

        $response = json_decode($response, true);

        if ($response[0]["status"] == "success") {
            return $response[0]['filename'];
        } else {
            return "failed";
        }

    }

    protected static function uploadMultipleImages(array $data)
    {

        $token = self::getUploadServerToken();

        if ($token == "failed") {
            return "token error";
        }

        $filer = [];

        foreach ($_FILES["propertyTitlePhotosImgs"]["tmp_name"] as $key => $value) {
            if (function_exists('curl_file_create')) {
                $filer[] = \curl_file_create($value);
            } else { //
                $filer[] = '@' . realpath($value);
            }
        }

        $file = $filer ?? [];
        $action = "multiple";
        $requestType = $data["imageInfo"] ?? "";
        $endpoint = $data["endpoint"] ?? "";

        $data = [
            "fileUpload[]" => $file,
            "action" => $action,
            "token" => $token,
            "requestType" => $requestType,
            "endpoint" => $endpoint,
        ];

        $host = "http://ec2-44-201-189-208.compute-1.amazonaws.com/";

        $header = "Content-Type: multipart/form-data; boundary=687898976465498929523510456";

        $response = \KuboPlugin\Utils\Util::clientRequest($host, "POST", $data, $header); // http request

        $response = json_decode($response, true);

        if ($response[0]["status"] == "success") {
            return $response[0]['data'];
        } else {
            return "failed";
        }

    }

    public static function updateDbEstate(int $resourceId)
    {

        // get property estate data
        $queryProperty = "SELECT * FROM Properties.UserProperty WHERE PropertyId IS NOT NULL";
        $resultProperty = DBConnectionFactory::getConnection()->query($queryProperty)->fetchAll(\PDO::FETCH_ASSOC);

        $queryEntity = "SELECT * FROM SpatialEntities.Entities WHERE EntityParent IS NULL";
        $resultEntity = DBConnectionFactory::getConnection()->query($queryEntity)->fetchAll(\PDO::FETCH_ASSOC);

        $queryUpdate = [];
        foreach ($resultProperty as $key => $value) {
            foreach ($resultEntity as $keyId => $valueId) {
                if ($value["LinkedEntity"] == $valueId["EntityId"]) {
                    $entityGeometry = $valueId["EntityGeometry"];
                    $linkedEntity = $value["LinkedEntity"];
                    $queryUpdate[] = "UPDATE Properties.UserProperty SET EntityGeometry = '$entityGeometry' WHERE LinkedEntity = $linkedEntity";
                }

            }

        }

        $query = implode(";", $queryUpdate);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;

    }

    public static function updateDbBlock(int $resourceId)
    {
        // get property estate data
        $queryProperty = "SELECT * FROM Properties.UserPropertyBlocks";
        $resultProperty = DBConnectionFactory::getConnection()->query($queryProperty)->fetchAll(\PDO::FETCH_ASSOC);

        $queryEntity = "SELECT * FROM SpatialEntities.Entities WHERE EntityType = 2";
        $resultEntity = DBConnectionFactory::getConnection()->query($queryEntity)->fetchAll(\PDO::FETCH_ASSOC);

        $queryUpdate = [];
        foreach ($resultProperty as $key => $value) {
            foreach ($resultEntity as $keyId => $valueId) {
                if ($value["LinkedEntity"] == $valueId["EntityId"]) {
                    $entityGeometry = $valueId["EntityGeometry"];
                    $linkedEntity = $value["LinkedEntity"];
                    $queryUpdate[] = "UPDATE Properties.UserPropertyBlocks SET EntityGeometry = '$entityGeometry' WHERE LinkedEntity = $linkedEntity";
                }

            }

        }

        $query = implode(";", $queryUpdate);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;

    }

    public static function updateDbUnit(int $resourceId)
    {
        // get property estate data
        $queryProperty = "SELECT * FROM Properties.UserPropertyUnits";
        $resultProperty = DBConnectionFactory::getConnection()->query($queryProperty)->fetchAll(\PDO::FETCH_ASSOC);

        $queryEntity = "SELECT * FROM SpatialEntities.Entities WHERE EntityType = 3";
        $resultEntity = DBConnectionFactory::getConnection()->query($queryEntity)->fetchAll(\PDO::FETCH_ASSOC);

        $queryUpdate = [];
        foreach ($resultProperty as $key => $value) {
            foreach ($resultEntity as $keyId => $valueId) {
                if ($value["LinkedEntity"] == $valueId["EntityId"]) {
                    $entityGeometry = $valueId["EntityGeometry"];
                    $linkedEntity = $value["LinkedEntity"];
                    $queryUpdate[] = "UPDATE Properties.UserPropertyUnits SET EntityGeometry = '$entityGeometry' WHERE LinkedEntity = $linkedEntity";
                }

            }

        }

        $query = implode(";", $queryUpdate);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;

    }

}
