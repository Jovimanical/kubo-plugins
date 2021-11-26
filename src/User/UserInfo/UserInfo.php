<?php declare (strict_types = 1);
/**
 * UserInfo Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\User\UserInfo;

use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;
use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;

/**
 * class KuboPlugin\User\UserInfo.
 *
 * User Info Controller
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 20/11/2021 15:22
 */
class UserInfo
{

    /**
     * Creates new user account
     *
     * @param string $email
     * @param string $password
     *
     * @return array
     */
    public static function updateUserInfo(int $userId, array $data)
    {
        $queries = [];
        $companyName = $data["companyName"] ?? '';
        $fullName = $data["fullName"] ?? '';
        $email = $data["email"] ?? '';
        $about = $data["about"] ?? '';
        $phone = $data["phone"] ?? '';
        $address = $data["address"] ?? '';
        $tel = $data["tel"] ?? '';

        $firstName = "";
        $lastName = "";
        if ($fullName != null) {
            $name = explode(" ", trim($fullName));
            $firstName = $name[0];
            $lastName = $name[1];
        }

        $inputDataUser = [
            "first_name" => QB::wrapString($firstName, "'"),
            "last_name" => QB::wrapString($lastName, "'"),
            "phone" => QB::wrapString($phone, "'"),
        ];

        $inputDataCompany = [
            "company_name" => QB::wrapString($companyName, "'"),
            "about" => QB::wrapString($about, "'"),
            "address" => QB::wrapString($address, "'"),
        ];

        foreach ($inputDataCompany as $key => $value) {

            $queries[] = "BEGIN TRANSACTION;" .
                "UPDATE Users.UserInfoFieldValues SET FieldValue='$value' WHERE FieldId='$key' AND UserId=".$inputDataCompany['user_id'].";" .
                "IF @@ROWCOUNT = 0 BEGIN INSERT INTO Users.UserInfoFieldValues (UserId, FieldId, FieldValue) VALUES (" . $inputDataCompany['user_id'] . ", '$key', '$value') END;" .
                "COMMIT TRANSACTION;";

        }

        $queries[] = "BEGIN TRANSACTION;" .
                "UPDATE Users.UserInfo SET FirstName=".$inputDataUser['first_name'].", LastName=".$inputDataUser['last_name'].", PhoneNumber=".$inputDataUser['phone']." WHERE UserId='$user_id'" .
                "IF @@ROWCOUNT = 0 BEGIN INSERT INTO Users.UserInfo (UserId, FirstName, LastName, PhoneNumber) VALUES ('$user_id', " . $inputDataUser['first_name'] . ", " . $inputDataUser['last_name'] . ", " . $inputDataUser['phone'] . ") END;" .
                "COMMIT TRANSACTION;";

        $query = implode(";", $queries);

        $result = DBConnectionFactory::getConnection()->exec($query);

        if ($result) {
            return "Update User Info successful!";
        } else {
            return "Update User Info Unsuccessful, please retry!";
        }

    }

    public static function viewUserInfo(int $userId)
    {

        $query = "SELECT * FROM Users.UserInfo WHERE UserId=$userId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $query = "SELECT * FROM Users.UserInfoFieldValues WHERE UserId=$userId";
        $result['meta'] = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if ($result) {
            return $result;
        } else {
            return "No User Info Found";
        }

    }


    public static function uploadUserInfoAvatar(int $userId, array $data)
    {
        $avatar = $data["avatar"] ?? '';

        $inputData = [
            "profilePhoto" => QB::wrapString($avatar, "'"),
        ];

       // $image = base64ToImg( $avatar, 'profilePhoto'.$userId.'.jpg' );

        $avatar = $inputData['profilePhoto'];

        $updateQuery = "UPDATE Users.UserInfo SET profilePhotoUrl = $avatar WHERE UserId = '$userId'";
        $result = DBConnectionFactory::getConnection()->query($updateQuery);


        if ($result) {
            $result = "Successful!";
        } else {
            $result = "Unsuccessful!";
        }

        return $result;

    }

    function base64ToImg($base64String, $outputFile) {
        // open file for writing
        $imgStringFile = fopen( $outputFile, 'wb' );

        // split the string on commas
        $dataImg = explode( ',', $base64String );

        fwrite( $imgStringFile, base64_decode( $dataImg[1] ) );

        // clean up the file resource
        fclose($imgStringFile);

        return $outputFile;
    }

}
