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
    public static function newProperty(array $data)
    {
        $user = $data["user"];
        $metadata = $data["property_metadata"] ?? [];
        $title = $data["property_title"];
        $geometry = $data["property_geometry"] ?? null;
        $parent = $data["property_parent"] ?? null;
        $type = $data["property_type"];

        //STEP 1: Index Spatial Entity
        $entity = [
            "entityName" => $title,
            "entityType" => $type,
            "entityParentId" => $parent,
            "entityGeometry" => $geometry,
        ];

        $indexEntityResult = \KuboPlugin\SpatialEntity\Entity\Entity::newEntity($entity);
        $entityId = $indexEntityResult["lastInsertId"];

        //STEP 2: Index User Property
        $inputData = [
            "UserId" => $user,
            "LinkedEntity" => $entityId,
            "PropertyTitle" => QB::wrapString($title, "'"),
        ];
        $result = DBQueryFactory::insert("[Properties].[UserProperty]", $inputData, false);

        $propertyId = $result["lastInsertId"];

        //STEP 3: Index Metadata
        $values = [];
        foreach ($metadata as $key => $value) {
            $values[] .= "($propertyId, '$key', '$value')";
        }

        $query = "INSERT INTO Properties.UserPropertyMetadata (PropertyId, FieldName, FieldValue) VALUES " . implode(",", $values);

        $result = DBConnectionFactory::getConnection()->exec($query);

        $result['EntityId'] = $entityId;

        return $result;
    }

    public static function newPropertyOnEntity(array $data)
    {
        $user = $data["user"] ?? 0;
        $metadata = $data["property_metadata"] ?? [];
        $title = $data["property_title"];
        $propertyId = $data["property_id"];
        $floorLevel = $data["floor_level"];

        // getting old property data
        $query = "SELECT * FROM Properties.UserProperty WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $entityId = $result[0]["LinkedEntity"] ?? 0;

        $inputData = [
            "UserId" => $user,
            "LinkedEntity" => $entityId,
            "PropertyFloor" => $floorLevel,
            "PropertyTitle" => QB::wrapString($title, "'"),
        ];

        // checking old property data
        $queryCheck = "SELECT PropertyId FROM Properties.UserProperty WHERE PropertyFloor = $floorLevel AND LinkedEntity = $entityId";
        $resultCheck = DBConnectionFactory::getConnection()->query($queryCheck)->fetchAll(\PDO::FETCH_ASSOC);

        $propId = 0;
        if (count($resultCheck) > 0) {
            // updating old property data
            $queryUpdate = "UPDATE Properties.UserProperty SET UserId = " . $inputData['UserId'] . ", LinkedEntity = " . $inputData['LinkedEntity'] . ", PropertyFloor = " . $inputData['PropertyFloor'] . ", PropertyTitle = " . $inputData['PropertyTitle'] . " WHERE PropertyFloor = $floorLevel AND LinkedEntity = $entityId";
            $resultUpdate = DBConnectionFactory::getConnection()->exec($queryUpdate);
            $queryCheck = "SELECT PropertyId FROM Properties.UserProperty WHERE PropertyFloor = $floorLevel AND LinkedEntity = $entityId";
            $resultCheck = DBConnectionFactory::getConnection()->query($queryCheck)->fetchAll(\PDO::FETCH_ASSOC);
            // getting property id
            $propId = $resultCheck[0]["PropertyId"];
        } else {
            // inserting new property data
            $result = DBQueryFactory::insert("[Properties].[UserProperty]", $inputData, false);

            $propId = $result["lastInsertId"];
        }

        // checking json data and sanitizing html entities
        if (self::isJSON($metadata)) {
            $metadata = str_replace('&#34;', '"', $metadata);
            $metadata = str_replace('&#39;', '"', $metadata);
            $metadata = html_entity_decode($metadata);
            $metadata = json_decode($metadata, true);
        }

        // STEP 3: Index Metadata
        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            // checking exisiting metadata
            $queryChecker = "SELECT PropertyId,FieldName FROM Properties.UserPropertyMetadata WHERE PropertyId = $propId AND FieldName = '$key'";
            $resultChecker = DBConnectionFactory::getConnection()->query($queryChecker)->fetchAll(\PDO::FETCH_ASSOC);

            if (count($resultChecker) > 0) {
                //  Updating the existing field
                $query = "UPDATE Properties.UserPropertyMetadata SET FieldValue = '$value' WHERE PropertyId = $propId AND FieldName = '$key'";
                $result = DBConnectionFactory::getConnection()->exec($query);

            } else {
                // Inserting a new field
                $query = "INSERT INTO Properties.UserPropertyMetadata (PropertyId, FieldName, FieldValue) VALUES ($propId, '$key', '$value')"; // " . implode(",", $values);
                $result = DBConnectionFactory::getConnection()->exec($query);
            }

        }

        // getting previous unit data
        $propertyChildren = self::viewPropertyChildren((int) $propertyId, ["floorLevel" => (int) $floorLevel - 1]);

        foreach ($propertyChildren as $property) {
            $title = $property["PropertyTitle"] . " - F" . $floorLevel;
            $entityId = $property["LinkedEntity"];

            $inputData = [
                "UserId" => $user,
                "LinkedEntity" => $entityId,
                "PropertyFloor" => $floorLevel,
                "PropertyTitle" => QB::wrapString($title, "'"),
            ];

            // inserting new properties data
            DBQueryFactory::insert("[Properties].[UserProperty]", $inputData, false);
        }

        return $result;
    }

    public static function viewProperties(int $userId, array $data = [])
    {
        $fetch = "FIRST";
        $offset = 0;

        if ($data['offset'] != 0) {
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
        $queryAvailableExtras = [];
        foreach ($results as $key => $property) {
            $resultPropertyId = $property["PropertyId"];

            $resultPropertyFloor = $property["PropertyFloor"] ?? 0;

            $unitQueries[] = "SELECT MetadataId, FieldName, FieldValue, PropertyId FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultPropertyId";

            $blockQueries[] = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId, ($resultPropertyId) as ConnectId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
            WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
            SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $resultPropertyId)) AND c.PropertyFloor = $resultPropertyFloor";


           // $results[$key]["Metadata"] = self::viewPropertyMetadata((int) $property["PropertyId"]);

            //Fetch total estate property units
           $queryTotals[] = "SELECT * FROM Properties.UserProperty a
            INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId
            WHERE b.EntityType = 3 AND b.EntityParent
            IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultPropertyId))";

           // $results[$key]["PropertyTotal"] = self::getEstatePropertyTotal((int) $property["PropertyId"]);

            $queryAvailables[] = "SELECT b.PropertyId FROM SpatialEntities.Entities a
            INNER JOIN Properties.UserProperty b ON a.EntityId = b.LinkedEntity
            INNER JOIN Properties.UserPropertyMetadata c ON b.PropertyId = c.PropertyId
            WHERE c.FieldName = 'property_status' AND c.FieldValue = 1 AND a.EntityParent IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultPropertyId))";

           // $results[$key]["PropertyAvailable"] = self::getEstatePropertyAvailable((int) $property["PropertyId"]);

           $results[$key]["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $property["LinkedEntity"]]);

        }

        $unitQuery = implode(";", $unitQueries);
        $blockQuery = implode(";", $blockQueries);

        $unitResultSetArr = [];
        $blockResultSetArr = [];

        $resultSetArr = [];

        $stmtResult = DBConnectionFactory::getConnection()->query($unitQuery);

        do {

            $unitResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
            if (count($unitResultArr) > 0) {
                // Add $rowset to array
                array_push($unitResultSetArr, $unitResultArr);

            }

        } while ($stmtResult->nextRowset());

        $stmtBlock = DBConnectionFactory::getConnection()->query($blockQuery);

        do {

            $blockResultArr = $stmtBlock->fetchAll(\PDO::FETCH_ASSOC);
            if (count($blockResultArr) > 0) {
                // Add $rowset to array
                array_push($blockResultSetArr, $blockResultArr);

            }
        } while ($stmtBlock->nextRowset());

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

            foreach ($blockResultSetArr as $keyItem => $valueItem) {

                foreach ($valueItem as $keyItemIdSet => $valueItemIdSet) {

                    if ($valueItemIdSet["ConnectId"] == $valueSetId['PropertyId']) {
                        if (!isset($results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]]) or !isset($results[$keySetId]["Metadata"])) {
                            $results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueItemIdSet["MetadataId"], "PropertyId" => $valueItemIdSet["ConnectId"]];
                        }
                    }

                }

            }

        }


        $queryTotal = implode(";", $queryTotals);
        $propertyCounter = 0;
        $totalResultSetArr = [];
        $stmtResultTotal = DBConnectionFactory::getConnection()->query($queryTotal);

        do {

            $totalResultArr = $stmtResultTotal->fetchAll(\PDO::FETCH_ASSOC);
            if (count($totalResultArr) > 0) {
                // Add $rowset to array
                array_push($totalResultSetArr, $totalResultArr);

            }

        } while ($stmtResultTotal->nextRowset());

        return $totalResultSetArr;

        foreach ($results as $keySetId => $valueSetId) {
             foreach ($totalResultSetArr as $keyItemId => $valueItemId) {
                 return $valueItemId['PropertyId'];
                 if ($valueItemId["PropertyId"] == $valueSetId["PropertyId"]) {
                     $results[$keySetId]["PropertyTotal"] = count($valueItemId);

                 }
             }

        }

        $queryAvailable = implode(";", $queryAvailables);
        $availableResultSetArr = [];
        $stmtResultAvailable = DBConnectionFactory::getConnection()->query($queryAvailable);

        do {

            $availableResultArr = $stmtResultAvailable->fetchAll(\PDO::FETCH_ASSOC);
            if (count($availableResultArr) > 0) {
                // Add $rowset to array
                array_push($availableResultSetArr, $availableResultArr);

            }

        } while ($stmtResultAvailable->nextRowset());

        foreach ($results as $keySetId => $valueSetId) {
              foreach ($availableResultSetArr as $keyItemId => $valueItemId) {
                  if ($valueItemId["PropertyId"] == $valueSetId["PropertyId"]) {
                      $results[$keySetId]["PropertyAvailable"] = count($valueItemId);

                  }
              }

          }


        return $results;
    }

    public static function viewProperty(int $propertyId)
    {
        $query = "SELECT * FROM Properties.UserProperty WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $queryFloor = "SELECT a.*, b.EntityParent FROM Properties.UserProperty a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE b.EntityParent = (SELECT LinkedEntity FROM Properties.UserProperty WHERE PropertyId = $propertyId)";
        $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

        $resultFloorData = [];
        foreach ($resultFloor as $key => $floor) {
            $resultFloorData[] = $floor['PropertyFloor'];
        }

        $resultFloorCount = count(array_unique($resultFloorData));

        $result = $result[0] ?? [];
        if (count($result) > 0) {
            $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["LinkedEntity"]]);
            $result["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"]);
            $result["FloorCount"] = $resultFloorCount;
        }

        return $result;
    }

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

        $unitQueries = [];
        $blockQueries = [];
        $metadata = [];
        foreach ($results as $key => $result) {

            $resultPropertyId = $result["PropertyId"];

            $resultPropertyFloor = $result["PropertyFloor"] ?? 0;

            $unitQueries[] = "SELECT MetadataId, FieldName, FieldValue, PropertyId FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultPropertyId";

            $blockQueries[] = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId, ($resultPropertyId) as ConnectId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
            WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
            SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $resultPropertyId)) AND c.PropertyFloor = $resultPropertyFloor";

            // $results[$key]["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], (int) $floorLevel);

            $results[$key]["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["LinkedEntity"]]);
        }

        $unitQuery = implode(";", $unitQueries);
        $blockQuery = implode(";", $blockQueries);

        $unitResultSetArr = [];
        $blockResultSetArr = [];

        $resultSetArr = [];

        $stmtResult = DBConnectionFactory::getConnection()->query($unitQuery);

        do {

            $unitResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
            if (count($unitResultArr) > 0) {
                // Add $rowset to array
                array_push($unitResultSetArr, $unitResultArr);

            }

        } while ($stmtResult->nextRowset());

        $stmtBlock = DBConnectionFactory::getConnection()->query($blockQuery);

        do {

            $blockResultArr = $stmtBlock->fetchAll(\PDO::FETCH_ASSOC);
            if (count($blockResultArr) > 0) {
                // Add $rowset to array
                array_push($blockResultSetArr, $blockResultArr);

            }
        } while ($stmtBlock->nextRowset());

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

            foreach ($blockResultSetArr as $keyItem => $valueItem) {

                foreach ($valueItem as $keyItemIdSet => $valueItemIdSet) {

                    if ($valueItemIdSet["ConnectId"] == $valueSetId['PropertyId']) {
                        if (!isset($results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]]) or !isset($results[$keySetId]["Metadata"])) {
                            $results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueItemIdSet["MetadataId"], "PropertyId" => $valueItemIdSet["ConnectId"]];
                        }
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

        $blockQuery = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
        WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
        SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $propertyId)) AND c.PropertyFloor = $resultFloorPoint";
        $blockResult = DBConnectionFactory::getConnection()->query($blockQuery)->fetchAll(\PDO::FETCH_ASSOC);

        $metadata = [];
        foreach ($result as $key => $value) {
            $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
        }

        foreach ($blockResult as $keyItem => $valueItem) {
            if (!isset($metadata[$valueItem["FieldName"]])) {
                $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
            }
        }

        return $metadata;
    }

    public static function viewPropertyByName(array $data)
    {
        $name = $data["name"] ?? 0;
        $query = "SELECT * FROM Properties.UserProperty WHERE PropertyTitle = '$name'";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $result = $result[0] ?? [];
        if (count($result) > 0) {
            $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["LinkedEntity"]]);
            $result["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"]);
        }

        return $result;
    }

    public static function viewPropertyChildrenTest(int $propertyId, array $floorData = [])
    {
        $floorLevel = 0;

        $query = "SELECT a.*, b.EntityParent FROM Properties.UserProperty a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE b.EntityParent = (SELECT LinkedEntity FROM Properties.UserProperty WHERE PropertyId = $propertyId)";

        if (isset($floorData["floorLevel"])) {
            $floorLevel = $floorData["floorLevel"];
            $query .= " AND a.PropertyFloor = $floorLevel";
        }

        $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if (isset($results[0])) {
            $propertyId = $results[0]["EntityParent"];
        }

        $propertyChildren = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntityChildren(["entityId" => $propertyId]);

        $childrenMetadata = self::viewPropertyChildrenMetadata((int) $propertyId, (int) $floorLevel);

        foreach ($results as $key => $result) {
            $results[$key]["Entity"] = $propertyChildren[$result["LinkedEntity"]] ?? [];
            $results[$key]["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], (int) $floorLevel);
        }

        return $results;
    }

    public static function viewPropertyChildren(int $propertyId, array $floorData = [])
    {
        $floorLevel = 0;

        $query = "SELECT a.*, b.EntityParent FROM Properties.UserProperty a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE b.EntityParent = (SELECT LinkedEntity FROM Properties.UserProperty WHERE PropertyId = $propertyId)";

        if (isset($floorData["floorLevel"])) {
            $floorLevel = $floorData["floorLevel"];
            $query .= " AND a.PropertyFloor = $floorLevel";
        }

        $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if (isset($results[0])) {
            $propertyId = $results[0]["EntityParent"];
        }

        $propertyChildren = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntityChildren(["entityId" => $propertyId]);

        $childrenMetadata = self::viewPropertyChildrenMetadata((int) $propertyId, (int) $floorLevel);

        $unitQueries = [];
        $blockQueries = [];
        $metadata = [];
        foreach ($results as $key => $result) {
            $results[$key]["Entity"] = $propertyChildren[$result["LinkedEntity"]] ?? [];

            $resultPropertyId = $result["PropertyId"];

            $resultPropertyFloor = $result["PropertyFloor"] ?? 0;

            $unitQueries[] = "SELECT MetadataId, FieldName, FieldValue, PropertyId FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultPropertyId";

            $blockQueries[] = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId, ($resultPropertyId) as ConnectId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
            WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
            SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $resultPropertyId)) AND c.PropertyFloor = $resultPropertyFloor";

            // $results[$key]["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], (int) $floorLevel);
        }

        $unitQuery = implode(";", $unitQueries);
        $blockQuery = implode(";", $blockQueries);

        $unitResultSetArr = [];
        $blockResultSetArr = [];

        $resultSetArr = [];

        $stmtResult = DBConnectionFactory::getConnection()->query($unitQuery);

        do {

            $unitResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
            if (count($unitResultArr) > 0) {
                // Add $rowset to array
                array_push($unitResultSetArr, $unitResultArr);

            }

        } while ($stmtResult->nextRowset());

        $stmtBlock = DBConnectionFactory::getConnection()->query($blockQuery);

        do {

            $blockResultArr = $stmtBlock->fetchAll(\PDO::FETCH_ASSOC);
            if (count($blockResultArr) > 0) {
                // Add $rowset to array
                array_push($blockResultSetArr, $blockResultArr);

            }
        } while ($stmtBlock->nextRowset());

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

            foreach ($blockResultSetArr as $keyItem => $valueItem) {

                foreach ($valueItem as $keyItemIdSet => $valueItemIdSet) {

                    if ($valueItemIdSet["ConnectId"] == $valueSetId['PropertyId']) {
                        if (!isset($results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]]) or !isset($results[$keySetId]["Metadata"])) {
                            $results[$keySetId]["Metadata"][$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueItemIdSet["MetadataId"], "PropertyId" => $valueItemIdSet["ConnectId"]];
                        }
                    }

                }

            }

        }

        return $results;

    }

    public static function viewPropertyMetadata(int $propertyId, int $floorLevel = 0)
    {
        $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $propertyId";
        $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

        $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;

        $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $blockQuery = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
        WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
        SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $propertyId)) AND c.PropertyFloor = $resultFloorPoint";
        $blockResult = DBConnectionFactory::getConnection()->query($blockQuery)->fetchAll(\PDO::FETCH_ASSOC);

        $metadata = [];
        foreach ($result as $key => $value) {
            $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
        }

        foreach ($blockResult as $keyItem => $valueItem) {
            if (!isset($metadata[$valueItem["FieldName"]])) {
                $metadata[$valueItem["FieldName"]] = ["FieldValue" => $valueItem["FieldValue"], "MetadataId" => $valueItem["MetadataId"]];
            }
        }

        return $metadata;
    }

    public static function viewPropertyChildrenMetadata(int $parentId, int $floorLevel = 0)
    {
        $query = "SELECT a.* FROM Properties.UserPropertyMetadata a
        INNER JOIN Properties.UserProperty b ON a.PropertyId = b.PropertyId
        INNER JOIN SpatialEntities.Entities c ON b.LinkedEntity = c.EntityId
        WHERE c.EntityParent = $parentId AND b.PropertyFloor = $floorLevel";

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $propertyParentQuery = "SELECT a.* FROM Properties.UserPropertyMetadata a
        INNER JOIN Properties.UserProperty b ON a.PropertyId = b.PropertyId
        INNER JOIN SpatialEntities.Entities c ON b.LinkedEntity = c.EntityId
        WHERE b.LinkedEntity = $parentId AND b.PropertyFloor = $floorLevel";
        $propertyParentResult = DBConnectionFactory::getConnection()->query($propertyParentQuery)->fetchAll(\PDO::FETCH_ASSOC);
        $metadata = [];

        foreach ($result as $key => $value) {
            if (!isset($metadata[$value["PropertyId"]])) {
                $propertyId = $value["PropertyId"];
                $metadata[$value["PropertyId"]] = [];
            }

            $metadata[$value["PropertyId"]][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];

            foreach ($propertyParentResult as $key => $value) {
                if (!isset($metadata[$propertyId][$value["FieldName"]])) {
                    $metadata[$propertyId][$value["FieldName"]] = ["FieldValue" => $value["FieldValue"], "MetadataId" => $value["MetadataId"]];
                }
            }
        }

        return $metadata;
    }

    public static function editPropertyMetadata(int $propertyId, array $metadata = [])
    {
        $queries = [];

        $counter = 0;
        $counterExtra = 0;
        $initialCheck = false;

        if ($propertyId == 0 or empty($metadata)) {
            return "Parameters not set";
        }

        foreach ($metadata as $key => $value) {
            /**@algo: Storing images and other base64 objects in the DB is inefficient.
             *  Check if $value is a base64 encoded object, export object to solution storage and store ref to this object as $key.
             *
             * Base64 String Format: <data_type>;base64,<md or a SHA component>
             * Split string using ;base64, and expect two components in an array to conclude
             * that we have a base64
             * **/

            if (self::isJSON($value)) {
                $value = str_replace('&#39;', '"', $value);
                $value = str_replace('&#34;', '"', $value);
                $value = html_entity_decode($value);
                $value = json_decode($value, true);

            }

            if (is_array($value)) {

                foreach ($value as $keyItem => $valueItem) {
                    if (is_string($valueItem)) {
                        $base64DataResult = self::checkForAndStoreBase64String($valueItem);
                        if ($base64DataResult["status"]) { // @todo: check properly to ensure
                            $value[$keyItem] = $base64DataResult["ref"];
                        } else {
                            $value[$keyItem] = $valueItem;
                        }
                    } else {
                        $value[$keyItem] = json_encode($valueItem);
                    }

                }

                $value = json_encode($value);

            } else {
                $base64DataResult = self::checkForAndStoreBase64String($value);
                if ($base64DataResult["status"]) { // @todo: check properly to ensure
                    $value = $base64DataResult["ref"];
                }
            }

            $keyId = self::camelToSnakeCase($key);

            if ($keyId == "property_title_photos_data") {
                foreach ($value as $keyItem => $valueItem) {
                    $queries[] = "BEGIN TRANSACTION;" .
                        "DELETE FROM Properties.UserPropertyMetadata WHERE FieldName='property_title_photos' AND FieldValue='$valueItem' AND PropertyId=$propertyId " .
                        "END TRY BEGIN CATCH SELECT ERROR_NUMBER() AS ErrorNumber,ERROR_MESSAGE() AS ErrorMessage; END CATCH " .
                        "COMMIT TRANSACTION;";
                    unlink("files/$valueItem");
                }

            }

            $counter++;

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

    public static function getEstatePropertyTotal(int $propertyId)
    {
        // @todo refactor later

        if ($propertyId == 0) {
            return "Parameter not set";
        }

        //Fetch total estate property units

        $query = "SELECT * FROM Properties.UserProperty a
        INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId
        WHERE b.EntityType = 3 AND b.EntityParent
        IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
        WHERE SpatialEntities.Entities.EntityParent
        IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
        WHERE PropertyId = $propertyId))";

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_NUM);

        $propertyCount = count($result);
        return $propertyCount;

    }

    public static function getEstatePropertyAvailable(int $propertyId)
    {
        // @todo refactor later
        $result = [];

        if ($propertyId == 0) {
            return "Parameter not set";
        }

        //Fetch total estate property units
        $query = "SELECT * FROM Properties.UserProperty a
        INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId
        WHERE b.EntityType = 3 AND b.EntityParent
        IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
        WHERE SpatialEntities.Entities.EntityParent
        IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
        WHERE PropertyId = $propertyId))";

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_NUM);

        $propertyCount = count($result);

        $query = "SELECT EntityId FROM SpatialEntities.Entities a
        INNER JOIN Properties.UserProperty b ON a.EntityId = b.LinkedEntity
        INNER JOIN Properties.UserPropertyMetadata c ON b.PropertyId = c.PropertyId
        WHERE c.FieldName = 'property_status' AND c.FieldValue = 1 AND a.EntityParent IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
        WHERE SpatialEntities.Entities.EntityParent
        IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
        WHERE PropertyId = $propertyId))";

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_NUM);
        $propertyTotal = count($result);

        return $propertyCount - $propertyTotal;

    }

    protected static function getPropertyCount(int $userId, int $entityType)
    {
        if ($entityType == 1) {
            // Fetching property count by type
            $query = "SELECT EntityType FROM SpatialEntities.Entities WHERE EntityId
            IN (SELECT LinkedEntity FROM Properties.UserProperty
             WHERE UserId = $userId) AND EntityType = $entityType";
        } else {
            // Fetching property count by type
            $query = "SELECT a.PropertyId FROM Properties.UserProperty a
            INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId
            WHERE b.EntityType = $entityType AND b.EntityParent
            IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE UserId = $userId))";
        }

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $propertyCount = count($result);
        return $propertyCount;
    }

    protected static function getMortgageCount(int $userId)
    {
        // Fetching property count
        $query = "SELECT PropertyId FROM Properties.Mortgages  WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId)";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $mortCount = count($result);
        return $mortCount;
    }

    protected static function getReservationCount(int $userId)
    {
        // Fetching reservation count
        $query = "SELECT EnquiryId FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId)";
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
        $query = "SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId AND LinkedEntity IN (SELECT EntityType FROM SpatialEntities.Entities WHERE EntityType = 1) AND PropertyId LIKE '%$searchTerm%' OR PropertyTitle LIKE '%$searchTerm%' ORDER BY PropertyId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";
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

        // die(print_r($inputData));

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

        $query = "SELECT * FROM Users.UserInfoFieldValues WHERE UserId = $userId AND FieldId = 2";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public static function uploadMapData(int $userId, array $data)
    {

        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }

        $username = $data["inputEmail"] ?? null;
        $password = $data["inputPassword"] ?? null;
        $foldername = $data["inputName"] ?? null;
        $initials = $data["inputInitials"] ?? null;

        if ($username == null or $password == null or $foldername == null or $initials == null) {
            return "Parameters not set";
        }

        if (isset($_POST["uploadBtn"])) {
            if ($_FILES["geojsons"]["name"] !== "") {

                return $data;

                $fileNameParts = explode(".", $_FILES["geojsons"]["name"]);
                if ($fileNameParts[1] == "zip") {
                    if (file_exists("./tmp/data")) {

                    } else {
                        mkdir("tmp/data");
                    }

                    $path = "tmp/data";
                    $location = $path . $_FILES["geojsons"]["name"];

                    if (move_uploaded_file($_FILES["geojsons"]["tmp_name"], $location)) {
                        $zip = new ZipArchive();
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

                                    $result = self::indexProperty($login, $boundary_geojson, $foldername);
                                    // @todo retrieve the last inserted Id of the property estate entityId
                                    // for the next BLOCK stage

                                    sleep(10);

                                    $dir = "tmp/data/$foldername/BLOCKS/";
                                    $files = scandir($dir);
                                    $blocks = [];
                                    foreach ($files as $file) {
                                        if (pathinfo($dir . $file, PATHINFO_EXTENSION) == "geojson") {
                                            $geojson = file_get_contents($dir . $file);
                                            $geojson = str_replace("\"", "'", $geojson);
                                            try {
                                                $file = str_replace(".geojson", " $initials", $file);
                                                $result = self::indexBlock($login, $geojson, $file, $result['EntityId']); // edit last insert entityId of Estate
                                                $blocks[] = $result['Entityname'] . " => " . $result['EntityId']; // @todo build $blocks array
                                            } catch (Exception $e) {
                                                return $file . " failed  \n" . $e->getMessage(); // @todo  return the Exception error and/or terminate
                                            }

                                        }
                                        if ($value == "BLOCK EXTRA") {
                                            $dir = "tmp/data/$foldername/BLOCK EXTRA/";
                                            $files = scandir($dir);
                                            $blocks = [];
                                            foreach ($files as $file) {
                                                if (pathinfo($dir . $file, PATHINFO_EXTENSION) == "geojson") {
                                                    $geojson = file_get_contents($dir . $file);
                                                    $geojson = str_replace("\"", "'", $geojson);
                                                    try {
                                                        $file = str_replace(".geojson", " $initials", $file);
                                                        $result = self::indexBlock($login, $geojson, $file, $result['EntityId']); // edit last insert entityId of Estate
                                                        $blocks[] = $result['Entityname'] . " => " . $result['EntityId']; // @todo build $blocks array
                                                    } catch (Exception $e) {
                                                        return $file . " failed  \n" . $e->getMessage(); // @todo  return the Exception error and/or terminate
                                                    }
    
                                                }
                                            }
                                        }
                                    }

                                    sleep(10);

                                    for ($i = 1; $i <= count($blocks); $i++) {
                                        $block = "BLOCK $i";
                                        $dir = "tmp/data/$foldername/$block/PLOTS/";
                                        $files = scandir($dir);
                                        foreach ($files as $file) {
                                            if (pathinfo($dir . $file, PATHINFO_EXTENSION) == "geojson") {
                                                $geojson = file_get_contents($dir . $file);
                                                $geojson = str_replace("\"", "'", $geojson);
                                                try {
                                                    $file = str_replace("Name_", "$block (", $file);
                                                    $file = str_replace(".geojson", "", $file);
                                                    $result = self::indexProperty($login, $geojson, "$initials " . $file, $blocks[$block]);
                                                } catch (Exception $e) {
                                                    return $file . " failed  \n" . $e->getMessage(); // @todo  return the Exception error and/or terminate
                                                }

                                                echo "\nDone with " . $file; // @todo  return the success data
                                                sleep(5);
                                            }
                                        }

                                        echo "\nDone with $block"; // @todo  return the success data
                                    }

                                }

                            }

                        } else {
                            return "Estate Boundary File not found !!! \n";
                        }

                    } else {
                        return "File not Uploaded ! \n";
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

    protected static function camelToSnakeCase($string, $sc = "_")
    {
        return strtolower(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', $sc, $string));
    }

    protected static function isJSON($stringData)
    {
        $stringData = str_replace('&#39;', '"', $stringData);
        $stringData = str_replace('&#34;', '"', $stringData);
        $stringData = html_entity_decode($stringData);
        return is_string($stringData) && is_array(json_decode($stringData, true)) ? true : false;
    }

    protected static function getPropertyChildrenIds(int $propertyId, array $floorData = [])
    {
        $floorLevel = 0;

        $query = "SELECT a.PropertyId FROM Properties.UserProperty a
        INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId
         WHERE b.EntityParent = (SELECT LinkedEntity FROM Properties.UserProperty
          WHERE PropertyId = $propertyId)";

        if (isset($floorData["floorLevel"])) {
            $floorLevel = $floorData["floorLevel"];
            $query .= " AND a.PropertyFloor = $floorLevel";
        }

        $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $results;
    }

    protected static function inArrayRec($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
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
        $response = \KuboPlugin\Utils\Util::clientRequest($host, "POST", $data);

        $accountData = json_decode($response, true);

        return $accountData;
    }

    protected static function indexProperty($login, $geojson, $title, $parent = 0)
    {
        $data = [
            "user" => $login["userId"],
            "property_title" => $title,
            "property_type" => 1,
            "property_geometry" => $geojson,
            "property_metadata" => [
                "property_description" => "",
            ],
        ];

        if ($parent != 0) {
            $data["property_type"] = 3;
            $data["property_parent"] = $parent;
        }

        $host = "https://rest.sytemap.com/v1/properties/user-property/new-property"; //"http://127.0.0.1:5464/v1/properties/user-property/new-property";
        // $host = "http://localhost:9000/v1/properties/user-property/new-property"; //"http://127.0.0.1:5464/v1/properties/user-property/new-property";

        $header = "Authorization: " . $login["sessionData"]["token"] . "," . $login["sessionId"] . "," . $login["userId"];
        $response = \KuboPlugin\Utils\Util::clientRequest($host, "POST", $data, $header);

        $response = json_decode($response, true);

        if ($response["errorStatus"] == false and $response["contentData"] == true) {
            return $response;
        } else {
            self::indexProperty($login, $geojson, $title, $parent);
        }
    }

    protected static function indexBlock($login, $geojson, $title, $parent = 0)
    {
        $data = [
            "user" => $login["userId"],
            "property_title" => $title,
            "property_type" => 1,
            "property_geometry" => $geojson,
            "property_metadata" => [
                "property_description" => "",
            ],
        ];

        if ($parent != 0) {
            $data["property_type"] = 2;
            $data["property_parent"] = $parent;
        }

        $host = "https://rest.sytemap.com/v1/properties/user-property/new-property"; //"http://127.0.0.1:5464/v1/properties/user-property/new-property";
        // $host = "http://localhost:9000/v1/properties/user-property/new-property"; //"http://127.0.0.1:5464/v1/properties/user-property/new-property";

        $header = "Authorization: " . $login["sessionData"]["token"] . "," . $login["sessionId"] . "," . $login["userId"];
        $response = \KuboPlugin\Utils\Util::clientRequest($host, "POST", $data, $header);

        $response = json_decode($response, true);
        if ($response["errorStatus"] == false and $response["contentData"] == true) {
            return $response;
        } else {
            self::indexBlock($login, $geojson, $title, $parent);
        }

    }

}
