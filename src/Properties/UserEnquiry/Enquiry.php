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

namespace KuboPlugin\Properties\UserEnquiry;

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;

/**
 * class KuboPlugin\Properties\UserEnquiry
 *
 * Enquiry Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 24/08/2021 08:10
 */
class Enquiry {
    public static function newEnquiry(array $data){
        $propertyId = $data["propertyId"];
        $messagePayload = $data["message_payload"] ?? [];
        $name =  $data["name"];
        $email = $data["email"] ?? null;
        $phone = $data["phone"] ?? null;
        $email = $data["budget"] ?? null;
        $phone = $data["msg"] ?? null;

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

    public static function viewProperties(int $userId){
        $query = "SELECT a.* FROM Properties.Enquiries a INNER JOIN SpatialEntities.Entities b ON a.LinkedEntity = b.EntityId WHERE a.UserId = $userId AND b.EntityParent IS NULL";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $key=>$Enquiry){
            $result[$key]["Entity"] = \KuboPlugin\SpatialEntity\Entity\Entity::viewEntity(["entityId"=>$Enquiry["LinkedEntity"]]);
            $result[$key]["Metadata"] = self::viewEnquiryMetadata((int)$Enquiry["EnquiryId"]);
        }

        return $result;
    }

    public static function viewEnquiry(int $userId,array $data){
        $fetch = "FIRST";
        $offset = 0;

        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY"; 
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $resultArr = [];
        foreach($result as $resultum){
            $resultMsg = $resultum['MessageJson'];

            $resultum['MessageJsonX'] = str_replace("&#39;","'",unserialize($resultMsg));
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

    public static function viewEnquiryBySeven(int $userId,array $data){
        $fetch = "FIRST";
        $offset = 0;
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d H:i:s');
        $toDate = date('Y-m-d H:i:s', strtotime('-7 days', strtotime(date('Y-m-d H:i:s'))));

        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateCreated  >= $fromDate AND DateCreated  < $toDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY"; 
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $resultArr = [];
        foreach($result as $resultum){
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
        $fetch = "FIRST";
        $offset = 0;
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d H:i:s');
        $toDate = date('Y-m-d H:i:s', strtotime('-30 days', strtotime(date('Y-m-d H:i:s'))));


        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateCreated  >= $fromDate AND DateCreated  <  $toDate ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $resultArr = [];
        foreach($result as $resultum){
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
        $fetch = "FIRST";
        $offset = 0;
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d H:i:s');
        $toDate = date('Y-m-d H:i:s', strtotime('-90 days', strtotime(date('Y-m-d H:i:s'))));


        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateCreated  >= date('Y-m-d H:i:s') AND DateCreated  <  date('Y-m-d H:i:s', strtotime('-90 days', strtotime(date('Y-m-d H:i:s'))) ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  // EnquiryId = $EnquiryId
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $resultArr = [];
        foreach($result as $resultum){
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
        $fetch = "FIRST";
        $offset = 0;
        $searchTerm = $data['searchTerm'];
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $query = "SELECT * FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND EnquiryId LIKE '%$searchTerm%' AND DateCreated LIKE '%$searchTerm%' AND 'Name' LIKE '%$searchTerm%' AND EmailAddress LIKE '%$searchTerm%' AND PhoneNumber LIKE '%$searchTerm%' AND MessageJson LIKE '%$searchTerm%' ORDER BY EnquiryId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $resultArr = [];
        foreach($result as $resultum){
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
        }

        return $results;
    }

    public static function viewEnquiryMetadata(int $EnquiryId){
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