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

        return $result;
    }

    public static function newPropertyOnEntity(array $data)
    {
        $user = $data["user"] ?? 0;
        $metadata = $data["property_metadata"] ?? [];
        $title = $data["property_title"];
        $propertyId = $data["property_id"];
        $floorLevel = $data["floor_level"];

        $query = "SELECT * FROM Properties.UserProperty WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $entityId = $result[0]["LinkedEntity"] ?? 0;

        $inputData = [
            "UserId" => $user,
            "LinkedEntity" => $entityId,
            "PropertyFloor" => $floorLevel,
            "PropertyTitle" => QB::wrapString($title, "'"),
        ];

        $result = DBQueryFactory::insert("[Properties].[UserProperty]", $inputData, false);

        $propId = $result["lastInsertId"];

        //STEP 3: Index Metadata
        $values = [];
        foreach ($metadata as $key => $value) {
            $values[] .= "($propId, '$key', '$value')";
        }

        $query = "INSERT INTO Properties.UserPropertyMetadata (PropertyId, FieldName, FieldValue) VALUES " . implode(",", $values);

        $result = DBConnectionFactory::getConnection()->exec($query);

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
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $key => $property) {
            $result[$key]["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $property["LinkedEntity"]]);
            $result[$key]["Metadata"] = self::viewPropertyMetadata((int) $property["PropertyId"]);
            $result[$key]["PropertyTotal"] = self::getEstatePropertyTotal((int) $property["PropertyId"]);
            $result[$key]["PropertyAvailable"] = self::getEstatePropertyAvailable((int) $property["PropertyId"]);
        }

        return $result;
    }

    public static function viewProperty(int $propertyId)
    {
        $query = "SELECT * FROM Properties.UserProperty WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $result = $result[0] ?? [];
        if (count($result) > 0) {
            $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["LinkedEntity"]]);
            $result["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"]);
        }

        return $result;
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

        foreach ($results as $key => $result) {
            $results[$key]["Entity"] = $propertyChildren[$result["LinkedEntity"]] ?? [];
            $results[$key]["Metadata"] = self::viewPropertyMetadata((int) $result["PropertyId"], (int) $floorLevel);
        }

        return $results;
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

        $unitQueries = [];
        $blockQueries = [];
        $metadata = [];
        foreach ($results as $key => $result) {
            $results[$key]["Entity"] = $propertyChildren[$result["LinkedEntity"]] ?? [];

            $resultPropertyId = $result["PropertyId"];

            $resultPropertyFloor = $result["PropertyFloor"];

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
                $connectChecker = "";
                foreach ($valueSet as $keyItemId => $valueItemId) {
                    $connectChecker = "";

                        if ($valueSetId['PropertyId'] == $valueItemId['PropertyId']) {
                            $connectChecker = $valueItemId['PropertyId'];
                            $metadata[$valueItemId["FieldName"]] = ["FieldValue" => $valueItemId["FieldValue"], "MetadataId" => $valueItemId["MetadataId"], "PropertyId" => $valueItemId["PropertyId"]];
                        }

                }

                

            }

            $resultSetArr[$keySetId] = $metadata;
        }

        $metadata = $resultSetArr;

        return $metadata;

        foreach ($results as $keySetId => $valueSetId) {


        foreach ($metadata as $keyItemSet => $valueItemSet) {
            foreach ($blockResultSetArr as $keyItem => $valueItem) {
                foreach ($valueItem as $keyItemIdSet => $valueItemIdSet) {

                        if (!self::inArrayRec($valueSetId['PropertyId'],$metadata)) {   // if ($keyItem == $keyItemSet) {
                            if ($valueItemIdSet["ConnectId"] == $valueSetId['PropertyId']) {
                                if (!$metadata[$valueItemIdSet["FieldName"]]) {
                                    $metadata[$valueItemIdSet["FieldName"]] = ["FieldValue" => $valueItemIdSet["FieldValue"], "MetadataId" => $valueSetId["MetadataId"]];
                                }
                            }

                        }

                }

            }
        }

            $results[$keySetId]["Metadata"] = $metadata;

        }


        return $results;

    }

    public static function viewPropertyMetadata(int $propertyId, int $floorLevel = 0)
    {
        $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $propertyId";
        $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

        $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $blockQuery = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
        WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
        SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $propertyId)) AND c.PropertyFloor = ".$resultFloor['PropertyFloor']." ";
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
        //$test = print_r($metadata, true);
        //return $test;
        $counter = 0;
        $counterExtra = 0;
        $initialCheck = false;

        if ($propertyId == 0 or empty($metadata)) {
            return "Parameters not set";
        }

        // fetching block children IDs
        // $blockChildrenIds = self::getPropertyChildrenIds($propertyId);
        // die(var_dump(count($blockChildrenIds)));
        /*
        if (count($blockChildrenIds) > 0) {
            $initialQuery = "SELECT Initial FROM Properties.UserProperty WHERE PropertyId = $propertyId";
            $resultInitial = DBConnectionFactory::getConnection()->query($initialQuery)->fetch(\PDO::FETCH_ASSOC);
            // die(var_dump(isset($resultInitial)));

            if (isset($resultInitial) and is_null($resultInitial["Initial"])) {
                $initialCheck = true;

                $queries[] = "BEGIN TRANSACTION;" .
                    "UPDATE Properties.UserProperty SET Initial='true' WHERE PropertyId=$propertyId;" .
                    "COMMIT TRANSACTION;";
            }

        }
        */

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
                $value = json_decode($value, true);

                //  print_r($value);
                // die();

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

            /* 
            if (count($blockChildrenIds) > 0 and $initialCheck) {

                foreach ($blockChildrenIds as $keyUnit => $valueUnit) {
                    // $valueUnit = json_decode($valueUnit, true);
                    //die(var_dump($valueUnit));

                    $counterExtra++;

                    $queries[] = "BEGIN TRANSACTION;" .
                        "DECLARE @rowcounter" . $counterExtra . " INT;" .
                        "UPDATE Properties.UserPropertyMetadata SET FieldValue='$value' WHERE FieldName='$keyId' AND PropertyId=$valueUnit[PropertyId] " .
                        "SET @rowcounter" . $counterExtra . " = @@ROWCOUNT " .
                        "BEGIN TRY " .
                        "IF @rowcounter" . $counterExtra . " = 0 BEGIN INSERT INTO Properties.UserPropertyMetadata (PropertyId, FieldName, FieldValue) VALUES ($valueUnit[PropertyId], '$keyId', '$value') END;" .
                        "END TRY BEGIN CATCH SELECT ERROR_NUMBER() AS ErrorNumber,ERROR_MESSAGE() AS ErrorMessage; END CATCH " .
                        "COMMIT TRANSACTION;";

                }

            }
            */

        }

        $query = implode(";", $queries);
        die(var_dump($query));

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
        if($entityType == 1){
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

    protected static function camelToSnakeCase($string, $sc = "_")
    {
        return strtolower(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', $sc, $string));
    }

    protected static function isJSON($stringData)
    {
        $string = str_replace('&#39;', '"', $stringData);
        $string = str_replace('&#34;', '"', $stringData);
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
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

    protected static function inArrayRec($needle, $haystack, $strict = false) {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && self::inArrayRec($needle, $item, $strict))) {
                return true;
            }
        }
    
        return false;
    }

}
