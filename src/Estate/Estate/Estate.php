<?php declare (strict_types = 1);
/**
 * Estate Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\Estate\Estate;

use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;
use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Factory\MailerFactory as Mailer;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;



/**
 * class KuboPlugin\Estate\Estate
 *
 * Estate
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 18/10/2021 06:20
 */
class Estate
{
    public static function updateEstateUser(int $userId, array $data)
    {
        $company_name = $data["company_name"] ?? null;
        $fullname = $data["fullname"] ?? null;
        $email = $data["email"] ?? null;
        $about = $data["about"] ?? null;
        $phone = $data["phone"] ?? null;
        $address = $data["address"] ?? null;
        $tel = $data["tel"] ?? null;
        
        $first_name = "";
        $last_name = "";
        if (isset($fullname)) {
            $name = explode(" ", trim($fullname));
            $first_name = $name[0];
            $last_name = $name[1];
        }

        $inputData = [
            "company_name" => QB::wrapString($company_name, "'"),
            "first_name" => QB::wrapString($first_name, "'"),
            "last_name" => QB::wrapString($last_name, "'"),
            "email" => QB::wrapString($email, "'"),
            "phone" => QB::wrapString($phone, "'"),
            "about" => QB::wrapString($about, "'"),
            "address" => QB::wrapString($address, "'"),
            "tel" => QB::wrapString($tel, "'"),
            "user_id" => $userId,
        ];

        $result = [];

        $companyName = $inputData['company_name'];

        $query = "SELECT * FROM Estate.users WHERE company_name = $companyName";
        $resultOne = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        $counter = count($resultOne);
        if (isset($counter) and $counter > 0) {
            $updateQuery = "UPDATE Estate.users SET company_name = " . $inputData['company_name'] . ",first_name = " . $inputData['first_name'] . ",last_name = " . $inputData['last_name'] . " WHERE company_name = $companyName";
            $result = DBConnectionFactory::getConnection()->query($query);
        } else {
            $result = DBQueryFactory::insert("[Estate].[users]", $inputData, false);
        }

        return $result;
    }

    public static function viewEstateUser(int $userId, array $data)
    {
        $company_name = $data["company_name"] ?? null;
        $email = $data["email"] ?? null;

        $inputData = [
            "company_name" => QB::wrapString($company_name, "'"),
            "email" => QB::wrapString($email, "'"),

        ];

        $companyName = $inputData['company_name'];

        $query = "SELECT * FROM Estate.users WHERE company_name = $companyName";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public static function uploadEstateUserAvatar(int $userId, array $data)
    {
        $company_name = $data["company_name"] ?? null;
        $email = $data["email"] ?? null;

        $inputData = [
            "company_name" => QB::wrapString($company_name, "'"),
            "email" => QB::wrapString($email, "'"),

        ];

        $companyName = $inputData['company_name'];


        $uploadedFiles = $request->getUploadedFiles();

        // handle file upload
        $uploadedFile = $uploadedFiles['avatar'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $avatar = base64_encode(file_get_contents($uploadedFile)); // convert to base64

            $updateQuery = "UPDATE Estate.users SET profile_photo = $avatar WHERE company_name = $companyName";
            $resultImg = DBConnectionFactory::getConnection()->query($query);

            $resultArr = [];

            if (isset($resultImg)) {
                $query = "SELECT * FROM Estate.users WHERE company_name = $companyName";
                $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
                $resultArr[] = $result;
            } else {
                $resultArr[] = "No Data";
            }

            return $resultArr;
        } else {
            $result = 'Upload Failed';

            return $result;
        }

    }

    public static function sendSupport(int $userId, array $data)
    {
        $headers = "From: " . $data['email'] . "" . "\r\n" .
            "Name:  " . $data['name'] . "";

        $receiver = "support@houseafrica.io";
        $sender = $data['email'];

        $subject = $data['subject'];

        $msg = $data['message'];

        return mail($receiver, $subject, $msg, $headers);
    }

    public static function sendMail(array $data)
    {

        $mail = new Mailer($data['sender'], $data['recipients'], $data['message']);

        if ($mail->send()) {

            return true;
        } else {
            return false;
        }

    }

    public static function getDashBoardTotal(int $userId)
    {
        $resultArr = [];

        $query = "SELECT EntityType FROM SpatialEntities.Entities WHERE EntityId IN (SELECT LinkedEntity FROM Properties.UserProperty WHERE UserId = $userId) AND EntityType = 1";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $estateCount = count($result);
        $resultArr['estate'] = $estateCount;

        $query1 = "SELECT EntityType FROM SpatialEntities.Entities WHERE EntityId IN (SELECT LinkedEntity FROM Properties.UserProperty WHERE UserId = $userId) AND EntityType = 3";
        $result1 = DBConnectionFactory::getConnection()->query($query1)->fetchAll(\PDO::FETCH_ASSOC);

        $propCount = count($result1);
        $resultArr['property'] = $propCount;

        $query2 = "SELECT property_id FROM Estate.Mortgages  WHERE property_id IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId)";
        $result2 = DBConnectionFactory::getConnection()->query($query2)->fetchAll(\PDO::FETCH_ASSOC);

        $mortCount = count($result2);
        $resultArr['mortgages'] = $mortCount;

        $query3 = "SELECT EnquiryId FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId)";
        $result3 = DBConnectionFactory::getConnection()->query($query3)->fetchAll(\PDO::FETCH_ASSOC);

        $reserveCount = count($result3);
        $resultArr['reservations'] = $reserveCount;

        return $resultArr;

    }

    public static function searchEstateClient(int $userId, array $data)
    {
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
            $query3 = "SELECT user_params,property_name,mortgagee_name FROM Estate.Mortgages WHERE  property_id = $result AND Name LIKE '%$searchTerm%' OR EmailAddress LIKE '%$searchTerm%' OR PhoneNumber LIKE '%$searchTerm%' ORDER BY mortgage_id DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";
            $result3 = DBConnectionFactory::getConnection()->query($query3)->fetchAll(\PDO::FETCH_ASSOC);
            // Returning all data
            $resultArr[$result1["FieldName"]] = ["FieldValue" => $result1["FieldValue"]];
            $resultArr["Client Enquirer"] = $result2;
            $resultArr["Client Mortgagee"] = $result3;
        }

        return $resultArr;

    }

    public static function viewEstateData(int $propertyId, int $floorLevel = 0)
    {
        // Getting Estate Data
        $query = "SELECT MetadataId, FieldName, FieldValue FROM Properties.UserPropertyMetadata WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $metadata = [];
        foreach ($result as $key => $value) {
            $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"]];
        }

        return $metadata;
    }

    public static function editEstateData(int $propertyId, array $metadata = [])
    {
        $queries = [];


        $uploadedFiles = $request->getUploadedFiles();

        // handle file upload
        $uploadedFile1 = $uploadedFiles['estate_logo'];
        $uploadedFile2 = $uploadedFiles['title_img'];
        if ($uploadedFile1->getError() === UPLOAD_ERR_OK) {
            $estate_logo = base64_encode(file_get_contents($uploadedFile1)); // convert to base64

            $metadata["estate_logo"] = $estate_logo;

        }
        if ($uploadedFile2->getError() === UPLOAD_ERR_OK) {
            $title_img = base64_encode(file_get_contents($uploadedFile2)); // convert to base64

            $metadata["title_img"] = $title_img;

        }

        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $queries[] = "BEGIN TRANSACTION;" .
                "UPDATE Properties.UserPropertyMetadata SET FieldValue='$value' WHERE FieldName='$key' AND PropertyId=$propertyId; " .
                "IF @@ROWCOUNT = 0 BEGIN INSERT INTO Properties.UserPropertyMetadata (PropertyId, FieldName, FieldValue) VALUES ($propertyId, '$key', '$value') END;" .
                "COMMIT TRANSACTION;";

        }

        $query = implode(";", $queries);

        $result = DBConnectionFactory::getConnection()->exec($query);

        return $result;
    }

    public static function allocateProperty(int $userId, array $data)
    {
        $queries = [];

        $client_name = $data["Client_Fullname"] ?? null;
        $phone = $data["Phone_number"] ?? null;
        $email = $data["Email"] ?? null;
        $property_id = $data["property_id"] ?? null;

        $inputData = [
            "recipient" => QB::wrapString($client_name, "'"),
            "property_id" => QB::wrapString($property_id, "'"),
            "email" => QB::wrapString($email, "'"),
            "phone" => QB::wrapString($phone, "'"),
        ];

        $metadata = [];

        $uploadedFiles = $request->getUploadedFiles();

        // handle file upload
        $uploadedFile1 = $uploadedFiles['Upload_Photo'];
        $uploadedFile2 = $uploadedFiles['Upload_Id'];
        $uploadedFile3 = $uploadedFiles['Upload_Signed_Deed'];
        $uploadedFile4 = $uploadedFiles['Upload_Contract_of_Sale'];
        $uploadedFile5 = $uploadedFiles['Upload_HOA'];

        if ($uploadedFile1->getError() === UPLOAD_ERR_OK) {
            $Upload_Photo = base64_encode(file_get_contents($uploadedFile1)); // convert to base64

            $metadata["Upload_Photo"] = $Upload_Photo;

        }
        if ($uploadedFile2->getError() === UPLOAD_ERR_OK) {
            $Upload_Id = base64_encode(file_get_contents($uploadedFile2)); // convert to base64

            $metadata["Upload_Id"] = $Upload_Id;

        }
        if ($uploadedFile3->getError() === UPLOAD_ERR_OK) {
            $Upload_Photo = base64_encode(file_get_contents($uploadedFile3)); // convert to base64

            $metadata["Upload_Signed_Deed"] = $Upload_Signed_Deed;

        }
        if ($uploadedFile4->getError() === UPLOAD_ERR_OK) {
            $Upload_Id = base64_encode(file_get_contents($uploadedFile4)); // convert to base64

            $metadata["Upload_Contract_of_Sale"] = $Upload_Contract_of_Sale;

        }
        if ($uploadedFile5->getError() === UPLOAD_ERR_OK) {
            $Upload_Photo = base64_encode(file_get_contents($uploadedFile5)); // convert to base64

            $metadata["Upload_HOA"] = $Upload_HOA;

        }

        $result = DBQueryFactory::insert("[Estate].[allocations]", $inputData, false);
        if ($result) {
            foreach ($metadata as $key => $value) {
                // Inserting Allocations MetaData
                $queries[] = "BEGIN TRANSACTION;" .
                    "UPDATE Estate.allocationsMetadata SET FieldValue='$value' WHERE FieldName='$key' AND PropertyId=$property_id; " .
                    "IF @@ROWCOUNT = 0
                              BEGIN INSERT INTO Estate.allocationsMetadata (PropertyId, FieldName, FieldValue) VALUES ($property_id, '$key', '$value')
                              END;" .
                    "COMMIT TRANSACTION;";

            }

            $query = implode(";", $queries);

            $resultData = DBConnectionFactory::getConnection()->exec($query);

            return $resultData;

        } else {
            return false;
        }

    }

    public static function viewEstateAllocationsData(int $propertyId, array $data)
    {
        $metadata = [];

        $query0 = "SELECT LinkedEntity FROM Properties.UserProperty WHERE PropertyId = $propertyId";
        $result0 = DBConnectionFactory::getConnection()->query($query1)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result0 as $key => $value) {

            // Getting Allocations Data
            $query = "SELECT * FROM Estate.allocations WHERE property_id IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT EntityId FROM SpatialEntities.Entities WHERE EntityParent = $value))";
            $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            if ($result) {
                $query1 = "SELECT FieldName, FieldValue FROM Estate.allocationsMetadata WHERE PropertyId = $propertyId";
                $result1 = DBConnectionFactory::getConnection()->query($query1)->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($result1 as $key => $value) {
                    $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"]];
                }

            }

        }

        return $metadata;

    }

    public static function viewBlockAllocationsData(int $propertyId, array $data)
    {
        $metadata = [];

        $query0 = "SELECT LinkedEntity FROM Properties.UserProperty WHERE PropertyId = $propertyId";
        $result0 = DBConnectionFactory::getConnection()->query($query1)->fetchAll(\PDO::FETCH_ASSOC);

        $resultLinkedEntity = $result0['LinkedEntity'];

        // Getting Allocations Data
        $query = "SELECT * FROM Estate.allocations WHERE property_id IN (SELECT PropertyId FROM Properties.UserProperty WHERE LinkedEntity IN (SELECT EntityId FROM SpatialEntities.Entities WHERE EntityParent = $resultLinkedEntity))";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if ($result) {
            foreach ($result1 as $key => $value) {
                $query1 = "SELECT FieldName, FieldValue FROM Estate.allocationsMetadata WHERE PropertyId = $propertyId";
                $result1 = DBConnectionFactory::getConnection()->query($query1)->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($result1 as $key => $value) {
                    $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"]];
                }

                return $metadata;
            }
        } else {
            return false;
        }
    }

    public static function viewUnitAllocationsData(int $propertyId, array $data)
    {
        $metadata = [];
        // Getting Allocations Data
        $query = "SELECT * FROM Estate.allocations WHERE PropertyId = $propertyId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if ($result) {
            $query1 = "SELECT FieldName, FieldValue FROM Estate.allocationsMetadata WHERE PropertyId = $propertyId";
            $result1 = DBConnectionFactory::getConnection()->query($query1)->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($result1 as $key => $value) {
                $metadata[$value["FieldName"]] = ["FieldValue" => $value["FieldValue"]];
            }

            return $metadata;
        } else {
            return false;
        }

    }

}
