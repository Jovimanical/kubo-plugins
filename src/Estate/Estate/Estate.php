<?php declare (strict_types=1);
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

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;
use EmmetBlue\Core\Factory\MailerFactory as Mailer;



/**
 * class KuboPlugin\Estate\Estate
 *
 * Estate
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 18/10/2021 06:20
 */
class Estate {
	public static function updateEstateUser(array $data){
		$company_name = $data["company_name"] ?? null;
        $fullname = $data["fullname"] ?? null;
        $email =  $data["email"] ?? null;
        $about = $data["about"] ?? null;
        $phone = $data["phone"] ?? null;
        $address = $data["address"] ?? null;
        $tel = $data["tel"] ?? null;

        $first_name = "";
        $last_name = "";
        if(isset($fullname)){
            $name = explode(trim(" ",$fullname));
            $first_name = $name[0];
            $last_name = $name[1];
        }

        $inputData = [
            "company_name"=>QB::wrapString($company_name, "'"),
            "first_name"=>QB::wrapString($first_name, "'"),
            "last_name"=>QB::wrapString($last_name, "'"),
            "email"=>QB::wrapString($email, "'"),
            "phone"=>QB::wrapString($phone, "'"),
            "about"=>QB::wrapString($about, "'"),
            "address"=>QB::wrapString($address, "'"),
            "tel"=>QB::wrapString($tel, "'")
        ];

        $result = [];

        $companyName = $inputData['company_name'];

        $query = "SELECT * FROM Estate.users WHERE company_name = $companyName";
        $resultOne = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        $counter = count($resultOne);
        if(isset($counter) and $counter > 0){
            $updateQuery = "UPDATE Estate.users SET company_name = ".$inputData['company_name'].",first_name = ".$inputData['first_name'].",last_name = ".$inputData['last_name']." WHERE company_name = $companyName";
            $result = DBConnectionFactory::getConnection()->query($query);
        } else {
            $result = DBQueryFactory::insert("[Estate].[users]", $inputData, false);
        }

        return $result;
	}

    public static function viewEstateUser(int $userId,array $data){
        $company_name = $data["company_name"] ?? null;
        $email =  $data["email"] ?? null;

        $inputData = [
            "company_name"=>QB::wrapString($company_name, "'"),
            "email"=>QB::wrapString($email, "'")
           
        ];

        $companyName = $inputData['company_name'];

        $query = "SELECT * FROM Estate.users WHERE company_name = $companyName";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);


        return $result;
    }


    public static function uploadEstateUserAvatar(int $userId,array $data){
        $company_name = $data["company_name"] ?? null;
        $email =  $data["email"] ?? null;

        $inputData = [
            "company_name"=>QB::wrapString($company_name, "'"),
            "email"=>QB::wrapString($email, "'")
           
        ];

        $companyName = $inputData['company_name'];
        
        $uploadedFiles = $request->getUploadedFiles();
    
        // handle file upload
        $uploadedFile = $uploadedFiles['avatar'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $avatar = base64_encode(file_get_contents($uploadedFile));  // convert to base64

            $updateQuery = "UPDATE Estate.users SET profile_photo = $avatar WHERE company_name = $companyName";
            $resultImg = DBConnectionFactory::getConnection()->query($query);

            $resultArr = [];

            if(isset($resultImg)){
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

    public static function sendSupport(int $userId,array $data){
        $headers = "From: ".$data['email']."" . "\r\n" .
            "Name:  ".$data['name']."";


        $receiver = "support@houseafrica.io";
        $sender = $data['email'];
        
        $subject = $data['subject'];

        $msg = $data['message'];

        return mail($receiver, $subject, $msg, $headers);
    }

    public static function sendMail(array $data){

        $mail = new Mailer($data['sender'],$data['recipients'],$data['message']);
        
        if($mail->send()){

            return true;
        } else {
            return false;
        }

    }


    public static function getDashBoardTotal(int $userId){
        $resultArr = [];

        $query = "SELECT EntityType FROM SpatialEntities.Entities WHERE EntityId IN (SELECT LinkedEntity FROM Properties.UserProperty WHERE UserId = $userId) AND EntityType = 1";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $estateCount =  count($result);
        $resultArr['estate'] = $estateCount;

        $query1 = "SELECT EntityType FROM SpatialEntities.Entities WHERE EntityId IN (SELECT LinkedEntity FROM Properties.UserProperty WHERE UserId = $userId) AND EntityType = 3";
        $result1 = DBConnectionFactory::getConnection()->query($query1)->fetchAll(\PDO::FETCH_ASSOC);

        $propCount =  count($result1);
        $resultArr['property'] = $propCount;

        $query2 = "SELECT property_id FROM Estate.Mortgages  WHERE property_id IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId)";
        $result2 = DBConnectionFactory::getConnection()->query($query2)->fetchAll(\PDO::FETCH_ASSOC);

        $mortCount =  count($result2);
        $resultArr['mortgages'] = $mortCount;

        $query3 = "SELECT EnquiryId FROM Properties.Enquiries WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId)";
        $result3 = DBConnectionFactory::getConnection()->query($query3)->fetchAll(\PDO::FETCH_ASSOC);

        $reserveCount =  count($result3);
        $resultArr['reservations'] = $reserveCount;

        return $resultArr;

    }

    public static function searchEstateUser(int $userId,array $data){
        $fetch = "FIRST";
        $offset = 0;
        $searchTerm = $data['searchTerm'];
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $query = "SELECT * FROM Properties.UserProperty WHERE UserId = $userId AND LinkedEntity IN (SELECT EntityType FROM SpatialEntities.Entities WHERE EntityType = 1) AND PropertyId LIKE '%$searchTerm%' OR PropertyTitle LIKE '%$searchTerm%' ORDER BY PropertyId DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY"; 
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;

    }

}