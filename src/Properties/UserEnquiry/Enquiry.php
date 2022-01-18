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

        $messageJson = serialize($messagePayload);

        $inputData = [
            "PropertyId"=>$propertyId,
            "Name"=>QB::wrapString($name, "'"),
            "EmailAddress"=>QB::wrapString($email, "'"),
            "PhoneNumber"=>QB::wrapString($phone, "'"),
            "MessageJson"=>QB::wrapString($messageJson, "'")
        ];

        $result = DBQueryFactory::insert("[Properties].[Enquiries]", $inputData, false);

        return $result;
    }


    public static function viewEnquiry(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;

        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY"; 
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

            $propQuery[] = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $resultPropertyId";

            $blockQuery[] = "SELECT d.MetadataId, d.FieldName, d.FieldValue, c.PropertyId FROM Properties.UserPropertyMetadata d INNER JOIN Properties.UserProperty c ON d.PropertyId = c.PropertyId
            WHERE d.PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT b.EntityParent FROM Properties.UserProperty a INNER JOIN
                SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.PropertyId = $resultPropertyId))";

            array_push($resultKey,$resultum['PropertyId']);

            $totalQuery[] = "SELECT COUNT(EntityId) FROM SpatialEntities.Entities 
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities 
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultPropertyId))";

            $availQuery[] = "SELECT COUNT(EntityId) FROM SpatialEntities.Entities a
            INNER JOIN Properties.UserProperty b ON a.EntityId = b.LinkedEntity
            INNER JOIN Properties.UserPropertyMetadata c ON b.PropertyId = c.PropertyId
            WHERE c.FieldName = 'property_status' AND c.FieldValue != 1 AND a.EntityParent IN(SELECT SpatialEntities.Entities.EntityId FROM SpatialEntities.Entities
            WHERE SpatialEntities.Entities.EntityParent
            IN(SELECT Properties.UserProperty.LinkedEntity FROM Properties.UserProperty
            WHERE PropertyId = $resultPropertyId))";

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


        $stmtProp = DBConnectionFactory::getConnection()->query($propQueries);
        //$stmtProp->nextRowset();
        //$propResult = $stmtProp->fetchAll(\PDO::FETCH_ASSOC);

        $propResultArr = [];

        do {
            
            $propResult = $stmtProp->fetchAll(\PDO::FETCH_ASSOC);
            if($propResult) {
                // Add $rowset to array
                array_push($propResultArr,$propResult);
            }
        } while($stmtProp->nextRowset());

        $blockResult = DBConnectionFactory::getConnection()->query($blockQueries)->fetchAll(\PDO::FETCH_ASSOC);
        $totalResult = DBConnectionFactory::getConnection()->query($totalQueries)->fetchAll(\PDO::FETCH_ASSOC);
        $availResult = DBConnectionFactory::getConnection()->query($availQueries)->fetchAll(\PDO::FETCH_ASSOC);

         die(var_dump($propResultArr));
         $r = [];

         foreach($propResultArr as $v){
             $r[] = $v;

         }
         die(var_dump($r));

        $metadata = [];

        $metadata['PropertyUnit'] = array_combine($resultKey,$propResult);
        $metadata['PropertyUnitBlock'] = array_combine($resultKey,$blockResult);
        $metadata['PropertyTotal'] = array_combine($resultKey,$totalResult);
        $metadata['PropertyAvailable'] = array_combine($resultKey,$availResult);

        // die(var_dump($metadata));

        array_push($resultArr,$metadata);

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
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d');
        $toDate = date('Y-m-d', strtotime('-7 days', strtotime(date('Y-m-d'))));

        $query = "SELECT * FROM Properties.Enquiries WHERE DateCreated  >= $fromDate AND DateCreated  < $toDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY"; 
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        //  WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND
        // WHERE DateCreated  >= $fromDate AND DateCreated  < $toDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY"
        $resultArr = [];
        foreach($result as $resultum){
            $resultum["PropertyTotal"] = UserProperty::getEstatePropertyTotal((int) $resultum["PropertyId"]);
            $resultum["PropertyAvailable"] = UserProperty::getEstatePropertyAvailable((int) $resultum["PropertyId"]);

            $resultMsg = $resultum['MessageJson'];

            $resultum['MessageJsonX'] = str_replace("&#39;","'",htmlspecialchars_decode(unserialize($resultMsg)));
            // $result["PropertyData"] = UserProperty::viewProperty((int)$result["PropertyId"]);
            array_push($resultArr,$resultum);

        }


       // $result = $result[0] ?? [];
       // if (count($result) > 0){
        //    $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["PropertyId"]]);  // $result["LinkedEntity"]
           // $result["Metadata"] = self::viewEnquiryMetadata((int)$result["EnquiryId"]);
       // }



        return $resultArr;
    }

    public static function viewEnquiryByThirty(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d');
        $toDate = date('Y-m-d', strtotime('-30 days', strtotime(date('Y-m-d'))));


        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateCreated  >= $fromDate AND DateCreated  <  $toDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $resultArr = [];
        foreach($result as $resultum){
            $resultum["PropertyTotal"] = UserProperty::getEstatePropertyTotal((int) $resultum["PropertyId"]);
            $resultum["PropertyAvailable"] = UserProperty::getEstatePropertyAvailable((int) $resultum["PropertyId"]);

            $resultMsg = $resultum['MessageJson'];

            $resultum['MessageJsonX'] = str_replace("&#39;","'",htmlspecialchars_decode(unserialize($resultMsg)));
            // $result["PropertyData"] = UserProperty::viewProperty((int)$result["PropertyId"]);
            array_push($resultArr,$resultum);

        }


       // $result = $result[0] ?? [];
       // if (count($result) > 0){
        //    $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["PropertyId"]]);  // $result["LinkedEntity"]
           // $result["Metadata"] = self::viewEnquiryMetadata((int)$result["EnquiryId"]);
       // }

        return $resultArr;
    }


    public static function viewEnquiryByNinety(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d');
        $toDate = date('Y-m-d', strtotime('-90 days', strtotime(date('Y-m-d'))));


        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateCreated  >= $fromDate AND DateCreated  < $toDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  // EnquiryId = $EnquiryId
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $resultArr = [];
        foreach($result as $resultum){
            $resultum["PropertyTotal"] = UserProperty::getEstatePropertyTotal((int) $resultum["PropertyId"]);
            $resultum["PropertyAvailable"] = UserProperty::getEstatePropertyAvailable((int) $resultum["PropertyId"]);

            $resultMsg = $resultum['MessageJson'];

            $resultum['MessageJsonX'] = str_replace("&#39;","'",htmlspecialchars_decode(unserialize($resultMsg)));
            // $result["PropertyData"] = UserProperty::viewProperty((int)$result["PropertyId"]);
            array_push($resultArr,$resultum);

        }


       // $result = $result[0] ?? [];
       // if (count($result) > 0){
        //    $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["PropertyId"]]);  // $result["LinkedEntity"]
           // $result["Metadata"] = self::viewEnquiryMetadata((int)$result["EnquiryId"]);
       // }



        return $resultArr;
    }



    public static function searchEnquiry(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;
        $searchTerm = $data['searchTerm'];
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND EnquiryId LIKE '%$searchTerm%' OR DateCreated LIKE '%$searchTerm%' OR 'Name' LIKE '%$searchTerm%' OR EmailAddress LIKE '%$searchTerm%' OR PhoneNumber LIKE '%$searchTerm%' OR MessageJson LIKE '%$searchTerm%' ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $resultArr = [];
        foreach($result as $resultum){
            $resultum["PropertyTotal"] = UserProperty::getEstatePropertyTotal((int) $resultum["PropertyId"]);
            $resultum["PropertyAvailable"] = UserProperty::getEstatePropertyAvailable((int) $resultum["PropertyId"]);

            $resultMsg = $resultum['MessageJson'];

            $resultum['MessageJsonX'] = str_replace("&#39;","'",htmlspecialchars_decode(unserialize($resultMsg)));
            // $result["PropertyData"] = UserProperty::viewProperty((int)$result["PropertyId"]);
            array_push($resultArr,$resultum);

        }


       // $result = $result[0] ?? [];
       // if (count($result) > 0){
        //    $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId" => $result["PropertyId"]]);  // $result["LinkedEntity"]
           // $result["Metadata"] = self::viewEnquiryMetadata((int)$result["EnquiryId"]);
       // }



        return $resultArr;
    }

    public static function viewEnquiryByName(array $data){
        $name = $data["name"] ?? 0;
        $query = "SELECT * FROM Properties.Enquiries WHERE EnquiryTitle = '$name'";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $result = $result[0] ?? [];
        if (count($result) > 0){
            $result["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId"=>$result["LinkedEntity"]]);
            $result["Metadata"] = self::viewEnquiryMetadata((int)$result["EnquiryId"]);
            $results[$key]["PropertyTotal"] = UserProperty::getEstatePropertyTotal((int) $result["PropertyId"]);
            $results[$key]["PropertyAvailable"] = UserProperty::getEstatePropertyAvailable((int) $result["PropertyId"]);

        }

        return $result;
    }

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