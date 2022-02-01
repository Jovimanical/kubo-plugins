<?php declare (strict_types=1);
/**
 * Controller Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\Properties\UserEnquiry;

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;
use KuboPlugin\Properties\UserProperty;

/**
 * class KuboPlugin\Properties\UserEnquiry
 *
 * Enquiry Controller
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 24/08/2021 08:10
 */
class Enquiry {
    public static function newEnquiry(array $data){
        if(empty($data)){
            return "Parameter not set";
        }
        $propertyId = $data["propertyId"];
        $messagePayload = $data["message_payload"] ?? [];
        $name =  $data["name"];
        $email = $data["email"] ?? null;
        $phone = $data["phone"] ?? null;
        $email = $data["budget"] ?? null;
        $msg = $data["msg"] ?? null;
        $estateId =  $data["estate_id"] ?? 0;

        $messageJson = serialize($messagePayload);

        $inputData = [
            "PropertyId"=>$propertyId,
            "Name"=>QB::wrapString($name, "'"),
            "EmailAddress"=>QB::wrapString($email, "'"),
            "PhoneNumber"=>QB::wrapString($phone, "'"),
            "MessageJson"=>QB::wrapString($messageJson, "'"),
            "EstateId"=>$estateId
        ];

        $result = DBQueryFactory::insert("[Properties].[Enquiries]", $inputData, false);

        return $result;
    }


    public static function viewEnquiries(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;
        $limit = $data['limit'] ?? 1000;

        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch $limit ROWS ONLY"; 
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $resultArr = [];

        $resultKey = [];

        $propQuery = [];
        $blockQuery = [];

        $totalQuery = [];
        $availQuery = [];

        foreach($result as $resultum){

            //Fetching estate property data
            $resultPropertyId = $resultum['PropertyId'];
            $resultEstateId = $resultum['EstateId'];

            $queryFloor = "SELECT a.PropertyFloor,b.EntityName FROM Properties.UserProperty a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $resultPropertyId AND b.EntityId IN(SELECT LinkedEntity FROM Properties.UserProperty WHERE PropertyId = $resultPropertyId)";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor[0]['PropertyFloor'] ?? 0;
            $resultFloorEntityName = $resultFloor[0]['EntityName'] ?? "No Data";

            $propQuery[] = "SELECT a.MetadataId, a.FieldName, a.FieldValue, ($resultFloorEntityName) as EntityName FROM Properties.UserPropertyMetadata a LEFT JOIN Properties.UserProperty b ON a.PropertyId = b.PropertyId WHERE a.PropertyId = $resultPropertyId";

            $blockQuery[] = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
            WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
                SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $resultPropertyId)) AND c.PropertyFloor = $resultFloorPoint";

            array_push($resultKey,$resultum['PropertyId']);

            $totalQuery[] = "SELECT count(a.PropertyId) FROM Properties.UserProperty a
            INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId
            WHERE b.EntityType = 3 AND b.EntityParent
            IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            $availQuery[] = "SELECT COUNT(EntityId) FROM SpatialEntities.Entities a
            INNER JOIN Properties.UserProperty b ON a.EntityId = b.LinkedEntity
            INNER JOIN Properties.UserPropertyMetadata c ON b.PropertyId = c.PropertyId
            WHERE c.FieldName = 'property_status' AND c.FieldValue = 1 AND a.EntityParent IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            // $resultum["Property"] = UserProperty::viewPropertyInfo((int) $resultum["PropertyId"]);

            $resultMsg = $resultum['MessageJson'];

            $resultum['MessageJsonX'] = str_replace("&#39;","'",unserialize($resultMsg));
            // $result["PropertyData"] = UserProperty::viewProperty((int)$result["PropertyId"]);
            array_push($resultArr,$resultum);

        }

        $propQueries = implode(";", $propQuery);
        $blockQueries = implode(";", $blockQuery);
        $totalQueries = implode(";", $totalQuery);
        $availQueries = implode(";", $availQuery);

        $propResultArr = [];
        $blockResultArr = [];
        $totalResultArr = [];
        $availResultArr = [];

        $stmtProp = DBConnectionFactory::getConnection()->query($propQueries);

        do {

            $propResult = $stmtProp->fetchAll(\PDO::FETCH_ASSOC);
            if($propResult) {
                // Add $rowset to array
                array_push($propResultArr,$propResult);
            }
        } while($stmtProp->nextRowset());

        $stmtBlock = DBConnectionFactory::getConnection()->query($blockQueries);

        do {

            $blockResult = $stmtBlock->fetchAll(\PDO::FETCH_ASSOC);
            if($blockResult) {
                // Add $rowset to array
                array_push($blockResultArr,$blockResult);
            }
        } while($stmtBlock->nextRowset());

        $stmtTotal = DBConnectionFactory::getConnection()->query($totalQueries);

        do {
            $totalResult = $stmtTotal->fetchAll(\PDO::FETCH_ASSOC);
            if($totalResult) {
                // Add $rowset to array
                array_push($totalResultArr,$totalResult);
            }
        } while($stmtTotal->nextRowset());

        $stmtAvail = DBConnectionFactory::getConnection()->query($availQueries);

        do {
            $availResult = $stmtAvail->fetchAll(\PDO::FETCH_ASSOC);
            if($availResult) {
                // Add $rowset to array
                array_push($availResultArr,$availResult);
            }
        } while($stmtAvail->nextRowset());


        $metadata = [];

        $metadata['PropertyUnit'] = array_combine($resultKey,$propResultArr);
        $metadata['PropertyUnitBlock'] = array_combine($resultKey,$blockResultArr);
        $metadata['PropertyTotal'] = array_combine($resultKey,$totalResultArr);
        $metadata['PropertySold'] = array_combine($resultKey,$availResultArr);

        foreach($result as $resultum){
            foreach($metadata['PropertyTotal'] as $key => $value){
                if($key == $resultum['PropertyId']){
                    $resultum['PropertyTotal'] = $value[0][''];
                }

            }
            foreach($metadata['PropertySold'] as $keyId => $valueId){
                if($keyId == $resultum['PropertyId']){
                    $resultum['PropertySold'] = $valueId[0][''];
                }

            }

           // $metadata['PropertyUnit'] = (array)json_decode($metadata['PropertyUnit'],true);

            foreach($metadata['PropertyUnit'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnit'] = $valueItem;
                }

            }

          //  $metadata['PropertyUnitBlock'] = (array)json_decode($metadata['PropertyUnitBlock'],true);

            foreach($metadata['PropertyUnitBlock'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnitBlock'] = $valueItem;
                }

            }
             array_push($resultArr,$resultum);

        }

        // die(var_dump($metadata));

       // array_push($resultArr,$metadata);

       // $result = $result[0] ?? [];
       // if (count($result) > 0){
       //    $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["PropertyId"]]);  // $result["LinkedEntity"]
          // $result["Metadata"] = self::viewEnquiryMetadata((int)$result["EnquiryId"]);
       // }

       // die(var_dump($resultArr));


        return $resultArr;
    }

    public static function viewEnquiryByDate(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;
        $limit = $data['limit'] ?? 1000;

        $dateTerm = $data['dateTerm'] ?? "-1 days";

        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d');
        $toDate = date('Y-m-d', strtotime($dateTerm, strtotime(date('Y-m-d'))));

        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateCreated >= $toDate AND DateCreated < $fromDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch $limit ROWS ONLY"; 
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        //  WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND
        // WHERE DateCreated  >= $fromDate AND DateCreated  < $toDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY"
        if(count($result) == 0){
            return "No enquiries found";
        }
        $resultArr = [];

        $resultKey = [];

        $propQuery = [];
        $blockQuery = [];

        $totalQuery = [];
        $availQuery = [];

        foreach($result as $resultum){

            //Fetching estate property data
            $resultPropertyId = $resultum['PropertyId'];
            $resultEstateId = $resultum['EstateId'];

            $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $resultPropertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;

            $propQuery[] = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultPropertyId";

            $blockQuery[] = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
            WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
                SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $resultPropertyId)) AND c.PropertyFloor = $resultFloorPoint";

            array_push($resultKey,$resultum['PropertyId']);

            $totalQuery[] = "SELECT count(a.PropertyId) FROM Properties.UserProperty a 
            INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId
            WHERE b.EntityType = 3 AND b.EntityParent 
            IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            $availQuery[] = "SELECT COUNT(EntityId) FROM SpatialEntities.Entities a
            INNER JOIN Properties.UserProperty b ON a.EntityId = b.LinkedEntity
            INNER JOIN Properties.UserPropertyMetadata c ON b.PropertyId = c.PropertyId
            WHERE c.FieldName = 'property_status' AND c.FieldValue = 1 AND a.EntityParent IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            // $resultum["Property"] = UserProperty::viewPropertyInfo((int) $resultum["PropertyId"]);

            $resultMsg = $resultum['MessageJson'];

            $resultum['MessageJsonX'] = str_replace("&#39;","'",unserialize($resultMsg));
           // $result["PropertyData"] = UserProperty::viewProperty((int)$result["PropertyId"]);
            array_push($resultArr,$resultum);

        }

        $propQueries = implode(";", $propQuery);
        $blockQueries = implode(";", $blockQuery);
        $totalQueries = implode(";", $totalQuery);
        $availQueries = implode(";", $availQuery);

        $propResultArr = [];
        $blockResultArr = [];
        $totalResultArr = [];
        $availResultArr = [];

        $stmtProp = DBConnectionFactory::getConnection()->query($propQueries);

        do {

            $propResult = $stmtProp->fetchAll(\PDO::FETCH_ASSOC);
            if($propResult) {
                // Add $rowset to array
                array_push($propResultArr,$propResult);
            }
        } while($stmtProp->nextRowset());

        $stmtBlock = DBConnectionFactory::getConnection()->query($blockQueries);

        do {

            $blockResult = $stmtBlock->fetchAll(\PDO::FETCH_ASSOC);
            if($blockResult) {
                // Add $rowset to array
                array_push($blockResultArr,$blockResult);
            }
        } while($stmtBlock->nextRowset());

        $stmtTotal = DBConnectionFactory::getConnection()->query($totalQueries);

        do {
            $totalResult = $stmtTotal->fetchAll(\PDO::FETCH_ASSOC);
            if($totalResult) {
                // Add $rowset to array
                array_push($totalResultArr,$totalResult);
            }
        } while($stmtTotal->nextRowset());

        $stmtAvail = DBConnectionFactory::getConnection()->query($availQueries);

        do {
            $availResult = $stmtAvail->fetchAll(\PDO::FETCH_ASSOC);
            if($availResult) {
                // Add $rowset to array
                array_push($availResultArr,$availResult);
            }
        } while($stmtAvail->nextRowset());


        $metadata = [];

        $metadata['PropertyUnit'] = array_combine($resultKey,$propResultArr);
        $metadata['PropertyUnitBlock'] = array_combine($resultKey,$blockResultArr);
        $metadata['PropertyTotal'] = array_combine($resultKey,$totalResultArr);
        $metadata['PropertySold'] = array_combine($resultKey,$availResultArr);

        foreach($result as $resultum){
            foreach($metadata['PropertyTotal'] as $key => $value){
                if($key == $resultum['PropertyId']){
                    $resultum['PropertyTotal'] = $value[0][''];
                }

            }
            foreach($metadata['PropertySold'] as $keyId => $valueId){
                if($keyId == $resultum['PropertyId']){
                    $resultum['PropertySold'] = $valueId[0][''];
                }

            }

           // $metadata['PropertyUnit'] = (array)json_decode($metadata['PropertyUnit'],true);

            foreach($metadata['PropertyUnit'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnit'] = $valueItem;
                }

            }

          //  $metadata['PropertyUnitBlock'] = (array)json_decode($metadata['PropertyUnitBlock'],true);

            foreach($metadata['PropertyUnitBlock'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnitBlock'] = $valueItem;
                }

            }
             array_push($resultArr,$resultum);

        }

        // die(var_dump($metadata));

       // array_push($resultArr,$metadata);

       // $result = $result[0] ?? [];
       // if (count($result) > 0){
       //    $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["PropertyId"]]);  // $result["LinkedEntity"]
          // $result["Metadata"] = self::viewEnquiryMetadata((int)$result["EnquiryId"]);
       // }

       // die(var_dump($resultArr));


        return $resultArr;
    }

    public static function viewEnquiryBySeven(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;
        $limit = $data['limit'] ?? 1000;

        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d');
        $toDate = date('Y-m-d', strtotime('-7 days', strtotime(date('Y-m-d'))));

        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateCreated >= $toDate AND DateCreated < $fromDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch $limit ROWS ONLY"; 
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        //  WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND
        // WHERE DateCreated  >= $fromDate AND DateCreated  < $toDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY"

        if(count($result) == 0){
            return "No enquiries found";
        }
        $resultArr = [];

        $resultKey = [];

        $propQuery = [];
        $blockQuery = [];

        $totalQuery = [];
        $availQuery = [];

        foreach($result as $resultum){

            //Fetching estate property data
            $resultPropertyId = $resultum['PropertyId'];
            $resultEstateId = $resultum['EstateId'];

            $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $resultPropertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;

            $propQuery[] = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultPropertyId";

            $blockQuery[] = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
            WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
                SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $resultPropertyId)) AND c.PropertyFloor = $resultFloorPoint";

            array_push($resultKey,$resultum['PropertyId']);

            $totalQuery[] = "SELECT count(a.PropertyId) FROM Properties.UserProperty a 
            INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId
            WHERE b.EntityType = 3 AND b.EntityParent 
            IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            $availQuery[] = "SELECT COUNT(EntityId) FROM SpatialEntities.Entities a
            INNER JOIN Properties.UserProperty b ON a.EntityId = b.LinkedEntity
            INNER JOIN Properties.UserPropertyMetadata c ON b.PropertyId = c.PropertyId
            WHERE c.FieldName = 'property_status' AND c.FieldValue = 1 AND a.EntityParent IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            // $resultum["Property"] = UserProperty::viewPropertyInfo((int) $resultum["PropertyId"]);

            $resultMsg = $resultum['MessageJson'];

            $resultum['MessageJsonX'] = str_replace("&#39;","'",unserialize($resultMsg));
           // $result["PropertyData"] = UserProperty::viewProperty((int)$result["PropertyId"]);
            array_push($resultArr,$resultum);

        }

        $propQueries = implode(";", $propQuery);
        $blockQueries = implode(";", $blockQuery);
        $totalQueries = implode(";", $totalQuery);
        $availQueries = implode(";", $availQuery);

        $propResultArr = [];
        $blockResultArr = [];
        $totalResultArr = [];
        $availResultArr = [];

        $stmtProp = DBConnectionFactory::getConnection()->query($propQueries);

        do {

            $propResult = $stmtProp->fetchAll(\PDO::FETCH_ASSOC);
            if($propResult) {
                // Add $rowset to array
                array_push($propResultArr,$propResult);
            }
        } while($stmtProp->nextRowset());

        $stmtBlock = DBConnectionFactory::getConnection()->query($blockQueries);

        do {

            $blockResult = $stmtBlock->fetchAll(\PDO::FETCH_ASSOC);
            if($blockResult) {
                // Add $rowset to array
                array_push($blockResultArr,$blockResult);
            }
        } while($stmtBlock->nextRowset());

        $stmtTotal = DBConnectionFactory::getConnection()->query($totalQueries);

        do {
            $totalResult = $stmtTotal->fetchAll(\PDO::FETCH_ASSOC);
            if($totalResult) {
                // Add $rowset to array
                array_push($totalResultArr,$totalResult);
            }
        } while($stmtTotal->nextRowset());

        $stmtAvail = DBConnectionFactory::getConnection()->query($availQueries);

        do {
            $availResult = $stmtAvail->fetchAll(\PDO::FETCH_ASSOC);
            if($availResult) {
                // Add $rowset to array
                array_push($availResultArr,$availResult);
            }
        } while($stmtAvail->nextRowset());


        $metadata = [];

        $metadata['PropertyUnit'] = array_combine($resultKey,$propResultArr);
        $metadata['PropertyUnitBlock'] = array_combine($resultKey,$blockResultArr);
        $metadata['PropertyTotal'] = array_combine($resultKey,$totalResultArr);
        $metadata['PropertySold'] = array_combine($resultKey,$availResultArr);

        foreach($result as $resultum){
            foreach($metadata['PropertyTotal'] as $key => $value){
                if($key == $resultum['PropertyId']){
                    $resultum['PropertyTotal'] = $value[0][''];
                }

            }
            foreach($metadata['PropertySold'] as $keyId => $valueId){
                if($keyId == $resultum['PropertyId']){
                    $resultum['PropertySold'] = $valueId[0][''];
                }

            }

           // $metadata['PropertyUnit'] = (array)json_decode($metadata['PropertyUnit'],true);

            foreach($metadata['PropertyUnit'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnit'] = $valueItem;
                }

            }

          //  $metadata['PropertyUnitBlock'] = (array)json_decode($metadata['PropertyUnitBlock'],true);

            foreach($metadata['PropertyUnitBlock'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnitBlock'] = $valueItem;
                }

            }
             array_push($resultArr,$resultum);

        }

        // die(var_dump($metadata));

       // array_push($resultArr,$metadata);

       // $result = $result[0] ?? [];
       // if (count($result) > 0){
       //    $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["PropertyId"]]);  // $result["LinkedEntity"]
          // $result["Metadata"] = self::viewEnquiryMetadata((int)$result["EnquiryId"]);
       // }

       // die(var_dump($resultArr));


        return $resultArr;
    }

    public static function viewEnquiryByThirty(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;
        $limit = $data['limit'] ?? 1000;

        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d');
        $toDate = date('Y-m-d', strtotime('-30 days', strtotime(date('Y-m-d'))));


        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateCreated  >= $toDate AND DateCreated  <  $fromDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch $limit ROWS ONLY";  
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if(count($result) == 0){
            return "No enquiries found";
        }

        $resultArr = [];

        $resultKey = [];

        $propQuery = [];
        $blockQuery = [];

        $totalQuery = [];
        $availQuery = [];

        foreach($result as $resultum){

            //Fetching estate property data
            $resultPropertyId = $resultum['PropertyId'];
            $resultEstateId = $resultum['EstateId'];

            $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $resultPropertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;

            $propQuery[] = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultPropertyId";

            $blockQuery[] = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
            WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
                SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $resultPropertyId)) AND c.PropertyFloor = $resultFloorPoint";

            array_push($resultKey,$resultum['PropertyId']);

            $totalQuery[] = "SELECT count(a.PropertyId) FROM Properties.UserProperty a 
            INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId
            WHERE b.EntityType = 3 AND b.EntityParent 
            IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            $availQuery[] = "SELECT COUNT(EntityId) FROM SpatialEntities.Entities a
            INNER JOIN Properties.UserProperty b ON a.EntityId = b.LinkedEntity
            INNER JOIN Properties.UserPropertyMetadata c ON b.PropertyId = c.PropertyId
            WHERE c.FieldName = 'property_status' AND c.FieldValue = 1 AND a.EntityParent IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            // $resultum["Property"] = UserProperty::viewPropertyInfo((int) $resultum["PropertyId"]);

            $resultMsg = $resultum['MessageJson'];

            $resultum['MessageJsonX'] = str_replace("&#39;","'",unserialize($resultMsg));
           // $result["PropertyData"] = UserProperty::viewProperty((int)$result["PropertyId"]);
            array_push($resultArr,$resultum);

        }

        $propQueries = implode(";", $propQuery);
        $blockQueries = implode(";", $blockQuery);
        $totalQueries = implode(";", $totalQuery);
        $availQueries = implode(";", $availQuery);

        $propResultArr = [];
        $blockResultArr = [];
        $totalResultArr = [];
        $availResultArr = [];

        $stmtProp = DBConnectionFactory::getConnection()->query($propQueries);

        do {

            $propResult = $stmtProp->fetchAll(\PDO::FETCH_ASSOC);
            if($propResult) {
                // Add $rowset to array
                array_push($propResultArr,$propResult);
            }
        } while($stmtProp->nextRowset());

        $stmtBlock = DBConnectionFactory::getConnection()->query($blockQueries);

        do {

            $blockResult = $stmtBlock->fetchAll(\PDO::FETCH_ASSOC);
            if($blockResult) {
                // Add $rowset to array
                array_push($blockResultArr,$blockResult);
            }
        } while($stmtBlock->nextRowset());

        $stmtTotal = DBConnectionFactory::getConnection()->query($totalQueries);

        do {
            $totalResult = $stmtTotal->fetchAll(\PDO::FETCH_ASSOC);
            if($totalResult) {
                // Add $rowset to array
                array_push($totalResultArr,$totalResult);
            }
        } while($stmtTotal->nextRowset());

        $stmtAvail = DBConnectionFactory::getConnection()->query($availQueries);

        do {
            $availResult = $stmtAvail->fetchAll(\PDO::FETCH_ASSOC);
            if($availResult) {
                // Add $rowset to array
                array_push($availResultArr,$availResult);
            }
        } while($stmtAvail->nextRowset());


        $metadata = [];

        $metadata['PropertyUnit'] = array_combine($resultKey,$propResultArr);
        $metadata['PropertyUnitBlock'] = array_combine($resultKey,$blockResultArr);
        $metadata['PropertyTotal'] = array_combine($resultKey,$totalResultArr);
        $metadata['PropertySold'] = array_combine($resultKey,$availResultArr);

        foreach($result as $resultum){
            foreach($metadata['PropertyTotal'] as $key => $value){
                if($key == $resultum['PropertyId']){
                    $resultum['PropertyTotal'] = $value[0][''];
                }

            }
            foreach($metadata['PropertySold'] as $keyId => $valueId){
                if($keyId == $resultum['PropertyId']){
                    $resultum['PropertySold'] = $valueId[0][''];
                }

            }

           // $metadata['PropertyUnit'] = (array)json_decode($metadata['PropertyUnit'],true);

            foreach($metadata['PropertyUnit'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnit'] = $valueItem;
                }

            }

          //  $metadata['PropertyUnitBlock'] = (array)json_decode($metadata['PropertyUnitBlock'],true);

            foreach($metadata['PropertyUnitBlock'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnitBlock'] = $valueItem;
                }

            }
             array_push($resultArr,$resultum);

        }

        // die(var_dump($metadata));

       // array_push($resultArr,$metadata);

       // $result = $result[0] ?? [];
       // if (count($result) > 0){
       //    $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["PropertyId"]]);  // $result["LinkedEntity"]
          // $result["Metadata"] = self::viewEnquiryMetadata((int)$result["EnquiryId"]);
       // }

       // die(var_dump($resultArr));


        return $resultArr;
    }


    public static function viewEnquiryByNinety(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;
        $limit = $data['limit'] ?? 1000;

        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d');
        $toDate = date('Y-m-d', strtotime('-90 days', strtotime(date('Y-m-d'))));


        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateCreated  >= $toDate AND DateCreated  < $fromDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch $limit ROWS ONLY";  // EnquiryId = $EnquiryId
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if(count($result) == 0){
            return "No enquiries found";
        }

        $resultArr = [];

        $resultKey = [];

        $propQuery = [];
        $blockQuery = [];

        $totalQuery = [];
        $availQuery = [];

        foreach($result as $resultum){

            //Fetching estate property data
            $resultPropertyId = $resultum['PropertyId'];
            $resultEstateId = $resultum['EstateId'];

            $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $resultPropertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;

            $propQuery[] = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultPropertyId";

            $blockQuery[] = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
            WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
                SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $resultPropertyId)) AND c.PropertyFloor = $resultFloorPoint";

            array_push($resultKey,$resultum['PropertyId']);

            $totalQuery[] = "SELECT count(a.PropertyId) FROM Properties.UserProperty a 
            INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId
            WHERE b.EntityType = 3 AND b.EntityParent 
            IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            $availQuery[] = "SELECT COUNT(EntityId) FROM SpatialEntities.Entities a
            INNER JOIN Properties.UserProperty b ON a.EntityId = b.LinkedEntity
            INNER JOIN Properties.UserPropertyMetadata c ON b.PropertyId = c.PropertyId
            WHERE c.FieldName = 'property_status' AND c.FieldValue = 1 AND a.EntityParent IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            // $resultum["Property"] = UserProperty::viewPropertyInfo((int) $resultum["PropertyId"]);

            $resultMsg = $resultum['MessageJson'];

            $resultum['MessageJsonX'] = str_replace("&#39;","'",unserialize($resultMsg));
           // $result["PropertyData"] = UserProperty::viewProperty((int)$result["PropertyId"]);
            array_push($resultArr,$resultum);

        }

        $propQueries = implode(";", $propQuery);
        $blockQueries = implode(";", $blockQuery);
        $totalQueries = implode(";", $totalQuery);
        $availQueries = implode(";", $availQuery);

        $propResultArr = [];
        $blockResultArr = [];
        $totalResultArr = [];
        $availResultArr = [];

        $stmtProp = DBConnectionFactory::getConnection()->query($propQueries);

        do {

            $propResult = $stmtProp->fetchAll(\PDO::FETCH_ASSOC);
            if($propResult) {
                // Add $rowset to array
                array_push($propResultArr,$propResult);
            }
        } while($stmtProp->nextRowset());

        $stmtBlock = DBConnectionFactory::getConnection()->query($blockQueries);

        do {

            $blockResult = $stmtBlock->fetchAll(\PDO::FETCH_ASSOC);
            if($blockResult) {
                // Add $rowset to array
                array_push($blockResultArr,$blockResult);
            }
        } while($stmtBlock->nextRowset());

        $stmtTotal = DBConnectionFactory::getConnection()->query($totalQueries);

        do {
            $totalResult = $stmtTotal->fetchAll(\PDO::FETCH_ASSOC);
            if($totalResult) {
                // Add $rowset to array
                array_push($totalResultArr,$totalResult);
            }
        } while($stmtTotal->nextRowset());

        $stmtAvail = DBConnectionFactory::getConnection()->query($availQueries);

        do {
            $availResult = $stmtAvail->fetchAll(\PDO::FETCH_ASSOC);
            if($availResult) {
                // Add $rowset to array
                array_push($availResultArr,$availResult);
            }
        } while($stmtAvail->nextRowset());


        $metadata = [];

        $metadata['PropertyUnit'] = array_combine($resultKey,$propResultArr);
        $metadata['PropertyUnitBlock'] = array_combine($resultKey,$blockResultArr);
        $metadata['PropertyTotal'] = array_combine($resultKey,$totalResultArr);
        $metadata['PropertySold'] = array_combine($resultKey,$availResultArr);

        foreach($result as $resultum){
            foreach($metadata['PropertyTotal'] as $key => $value){
                if($key == $resultum['PropertyId']){
                    $resultum['PropertyTotal'] = $value[0][''];
                }

            }
            foreach($metadata['PropertySold'] as $keyId => $valueId){
                if($keyId == $resultum['PropertyId']){
                    $resultum['PropertySold'] = $valueId[0][''];
                }

            }

           // $metadata['PropertyUnit'] = (array)json_decode($metadata['PropertyUnit'],true);

            foreach($metadata['PropertyUnit'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnit'] = $valueItem;
                }

            }

          //  $metadata['PropertyUnitBlock'] = (array)json_decode($metadata['PropertyUnitBlock'],true);

            foreach($metadata['PropertyUnitBlock'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnitBlock'] = $valueItem;
                }

            }
             array_push($resultArr,$resultum);

        }

        // die(var_dump($metadata));

       // array_push($resultArr,$metadata);

       // $result = $result[0] ?? [];
       // if (count($result) > 0){
       //    $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["PropertyId"]]);  // $result["LinkedEntity"]
          // $result["Metadata"] = self::viewEnquiryMetadata((int)$result["EnquiryId"]);
       // }

       // die(var_dump($resultArr));


        return $resultArr;
    }



    public static function searchEnquiry(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;
        $limit = $data['limit'] ?? 1000;

        $searchTerm = $data['searchTerm'];
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND EnquiryId LIKE '%$searchTerm%' OR DateCreated LIKE '%$searchTerm%' OR 'Name' LIKE '%$searchTerm%' OR EmailAddress LIKE '%$searchTerm%' OR PhoneNumber LIKE '%$searchTerm%' OR MessageJson LIKE '%$searchTerm%' ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch $limit ROWS ONLY";  
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        if(count($result) == 0){
            return "No results found";
        }
        $resultArr = [];

        $resultKey = [];

        $propQuery = [];
        $blockQuery = [];

        $totalQuery = [];
        $availQuery = [];

        foreach($result as $resultum){

            //Fetching estate property data
            $resultPropertyId = $resultum['PropertyId'];
            $resultEstateId = $resultum['EstateId'];

            $queryFloor = "SELECT PropertyFloor FROM Properties.UserProperty WHERE PropertyId = $resultPropertyId";
            $resultFloor = DBConnectionFactory::getConnection()->query($queryFloor)->fetchAll(\PDO::FETCH_ASSOC);

            $resultFloorPoint = $resultFloor['PropertyFloor'] ?? 0;

            $propQuery[] = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultPropertyId";

            $blockQuery[] = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
            WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
                SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $resultPropertyId)) AND c.PropertyFloor = $resultFloorPoint";

            array_push($resultKey,$resultum['PropertyId']);

            $totalQuery[] = "SELECT count(a.PropertyId) FROM Properties.UserProperty a 
            INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId
            WHERE b.EntityType = 3 AND b.EntityParent 
            IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            $availQuery[] = "SELECT COUNT(EntityId) FROM SpatialEntities.Entities a
            INNER JOIN Properties.UserProperty b ON a.EntityId = b.LinkedEntity
            INNER JOIN Properties.UserPropertyMetadata c ON b.PropertyId = c.PropertyId
            WHERE c.FieldName = 'property_status' AND c.FieldValue = 1 AND a.EntityParent IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultEstateId))";

            // $resultum["Property"] = UserProperty::viewPropertyInfo((int) $resultum["PropertyId"]);

            $resultMsg = $resultum['MessageJson'];

            $resultum['MessageJsonX'] = str_replace("&#39;","'",unserialize($resultMsg));
           // $result["PropertyData"] = UserProperty::viewProperty((int)$result["PropertyId"]);
            array_push($resultArr,$resultum);

        }

        $propQueries = implode(";", $propQuery);
        $blockQueries = implode(";", $blockQuery);
        $totalQueries = implode(";", $totalQuery);
        $availQueries = implode(";", $availQuery);

        $propResultArr = [];
        $blockResultArr = [];
        $totalResultArr = [];
        $availResultArr = [];

        $stmtProp = DBConnectionFactory::getConnection()->query($propQueries);

        do {

            $propResult = $stmtProp->fetchAll(\PDO::FETCH_ASSOC);
            if($propResult) {
                // Add $rowset to array
                array_push($propResultArr,$propResult);
            }
        } while($stmtProp->nextRowset());

        $stmtBlock = DBConnectionFactory::getConnection()->query($blockQueries);

        do {

            $blockResult = $stmtBlock->fetchAll(\PDO::FETCH_ASSOC);
            if($blockResult) {
                // Add $rowset to array
                array_push($blockResultArr,$blockResult);
            }
        } while($stmtBlock->nextRowset());

        $stmtTotal = DBConnectionFactory::getConnection()->query($totalQueries);

        do {
            $totalResult = $stmtTotal->fetchAll(\PDO::FETCH_ASSOC);
            if($totalResult) {
                // Add $rowset to array
                array_push($totalResultArr,$totalResult);
            }
        } while($stmtTotal->nextRowset());

        $stmtAvail = DBConnectionFactory::getConnection()->query($availQueries);

        do {
            $availResult = $stmtAvail->fetchAll(\PDO::FETCH_ASSOC);
            if($availResult) {
                // Add $rowset to array
                array_push($availResultArr,$availResult);
            }
        } while($stmtAvail->nextRowset());


        $metadata = [];

        $metadata['PropertyUnit'] = array_combine($resultKey,$propResultArr);
        $metadata['PropertyUnitBlock'] = array_combine($resultKey,$blockResultArr);
        $metadata['PropertyTotal'] = array_combine($resultKey,$totalResultArr);
        $metadata['PropertySold'] = array_combine($resultKey,$availResultArr);

        foreach($result as $resultum){
            foreach($metadata['PropertyTotal'] as $key => $value){
                if($key == $resultum['PropertyId']){
                    $resultum['PropertyTotal'] = $value[0][''];
                }

            }
            foreach($metadata['PropertySold'] as $keyId => $valueId){
                if($keyId == $resultum['PropertyId']){
                    $resultum['PropertySold'] = $valueId[0][''];
                }

            }

           // $metadata['PropertyUnit'] = (array)json_decode($metadata['PropertyUnit'],true);

            foreach($metadata['PropertyUnit'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnit'] = $valueItem;
                }

            }

          //  $metadata['PropertyUnitBlock'] = (array)json_decode($metadata['PropertyUnitBlock'],true);

            foreach($metadata['PropertyUnitBlock'] as $keyItem => $valueItem){
                if($keyItem == $resultum['PropertyId']){
                    $resultum['PropertyUnitBlock'] = $valueItem;
                }

            }
             array_push($resultArr,$resultum);

        }

        // die(var_dump($metadata));

        // array_push($resultArr,$metadata);

       // $result = $result[0] ?? [];
       // if (count($result) > 0){
       //    $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["PropertyId"]]);  // $result["LinkedEntity"]
          // $result["Metadata"] = self::viewEnquiryMetadata((int)$result["EnquiryId"]);
       // }

       // die(var_dump($resultArr));


        return $resultArr;
    }

    // @todo not been used for now 19/1/2022 

    public static function viewEnquiryChildren(int $EnquiryId){
        $query = "SELECT a.*, b.EntityParent FROM Properties.Enquiries a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE b.EntityParent = (SELECT LinkedEntity FROM Properties.Enquiries WHERE EnquiryId = $EnquiryId)";
        $results = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if (isset($results[0])){
            $EnquiryId = $results[0]["EntityParent"];
        }

        $EnquiryChildren = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntityChildren(["entityId"=>$EnquiryId]);

        $childrenMetadata = self::viewEnquiryChildrenMetadata((int)$EnquiryId);

        foreach ($results as $key=>$result){
            $results[$key]["Entity"] = $EnquiryChildren[$result["LinkedEntity"]] ?? [];
            $results[$key]["Metadata"] = $childrenMetadata[$result["EnquiryId"]] ?? [];
            $results[$key]["PropertyTotal"] = UserProperty::getEstatePropertyTotal((int) $result["PropertyId"]);
            $results[$key]["PropertyAvailable"] = UserProperty::getEstatePropertyAvailable((int) $result["PropertyId"]);

        }

        return $results;
    }

    public static function viewEnquiryMetadata(int $EnquiryId){
        if($EnquiryId == 0){
            return "Parameter not set";
        }
        $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.EnquiryMetadata WHERE EnquiryId = $EnquiryId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $metadata = [];
        foreach ($result as $key=>$value){
            $metadata[$value["FieldName"]] = ["FieldValue"=>$value["FieldValue"], "MetadataId"=>$value["MetadataId"]];
        }

        return $metadata;
    }

    public static function viewEnquiryChildrenMetadata(int $parentId){
        $query = "SELECT a.* FROM Properties.EnquiryMetadata a
                    INNER JOIN Properties.Enquiries b ON a.EnquiryId = b.EnquiryId
                    INNER JOIN SpatialEntities.Entities c ON b.LinkedEntity = c.EntityId
                    WHERE c.EntityParent = $parentId";

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $metadata = [];

        foreach ($result as $key=>$value){
            if (!isset($metadata[$value["EnquiryId"]])){
                $metadata[$value["EnquiryId"]] = [];
            }

            $metadata[$value["EnquiryId"]][$value["FieldName"]] = ["FieldValue"=>$value["FieldValue"], "MetadataId"=>$value["MetadataId"]];
        }

        return $metadata;
    }

    public static function editEnquiryMetadata(int $EnquiryId, array $metadata = []){
        if($EnquiryId == 0 OR empty($metadata)){
            return "Parameters not set";
        }
        $queries = [];
        foreach($metadata as $key=>$value){
            $queries[] = "BEGIN TRANSACTION;".
                         "UPDATE Properties.EnquiryMetadata SET FieldValue='$value' WHERE FieldName='$key' AND EnquiryId=$EnquiryId; ".
                         "IF @@ROWCOUNT = 0 BEGIN INSERT INTO Properties.EnquiryMetadata (EnquiryId, FieldName, FieldValue) VALUES ($EnquiryId, '$key', '$value') END;".
                         "COMMIT TRANSACTION;";


        }

        $query = implode(";", $queries);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;
    }
}