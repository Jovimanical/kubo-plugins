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
        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }
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
            "address" => QB::wrapString($address, "'"),
            "about" => QB::wrapString($about, "'"),
        ];

        $inputDataId = [
            "company_name" => 2,
            "address" => 7,
            "about" => 5,
        ];

        $query = "SELECT * FROM Users.UserInfoFields";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($inputDataCompany as $key => $value) {
            $keyValue = 0;
            // $key = \KuboPlugin\Utils\Util::camelToSnakeCase($key);
            foreach ($inputDataId as $keyItem => $valueItem) {

                if ($keyItem == $key) {
                    $keyValue = $valueItem;

                    $queries[] = "BEGIN TRANSACTION;" .
                        "UPDATE Users.UserInfoFieldValues SET FieldValue=$value WHERE FieldId=$keyValue AND UserId=$userId;" .
                        "IF @@ROWCOUNT = 0 BEGIN INSERT INTO Users.UserInfoFieldValues (UserId, FieldId, FieldValue) VALUES ($userId, $keyValue, $value) END;" .
                        "COMMIT TRANSACTION;";

                }
            }

        }

        $queries[] = "BEGIN TRANSACTION;" .
            "UPDATE Users.UserInfo SET FirstName=" . $inputDataUser['first_name'] . ", LastName=" . $inputDataUser['last_name'] . ", PhoneNumber=" . $inputDataUser['phone'] . " WHERE UserId=$userId;" .
            "IF @@ROWCOUNT = 0 BEGIN INSERT INTO Users.UserInfo (UserId, FirstName, LastName, PhoneNumber) VALUES ($userId, " . $inputDataUser['first_name'] . ", " . $inputDataUser['last_name'] . ", " . $inputDataUser['phone'] . ") END;" .
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
        if ($userId == 0) {
            return "Parameter not set";
        }
        $resultData = [];
        $query = "SELECT UserId,FirstName,LastName,ProfilePhotoUrl,PhoneNumber FROM Users.UserInfo WHERE UserId=$userId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($result as $keyItem => $valueItem) {
           // $valueItem = json_decode($valueItem, true);
            foreach ($valueItem as $keyId => $valueId) {
                $resultData['profile'][$keyId] = $valueId;
            }
        }

        $queryMeta = "SELECT FieldValue,FieldName FROM [Users].[UserInfoFieldValues] LEFT JOIN [Users].[UserInfoFields] ON [Users].[UserInfoFieldValues].FieldId = [Users].[UserInfoFields].FieldId WHERE UserId = $userId;";
        $resultMeta = DBConnectionFactory::getConnection()->query($queryMeta)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($resultMeta as $key => $value) {
            foreach ($value as $keyItem => $valueItem) {
                $resultData['profile'][$value["FieldName"]] = $value['FieldValue'];

            }
        }

        // $resultMetaData = array_map("self::combineArrays",$result,$resultData);

        if (!empty($resultData)) {
            return $resultData;
        } else {
            return "No User Info Found";
        }

    }

    protected static function combineArrays($keyId, $keyItem)
    {
        $arrData = [];
        array_push($arrData, $keyId, $keyItem);
        return $arrData;
    }

    public static function uploadUserInfoAvatar(int $userId, array $data)
    {
        if ($userId == 0 or empty($data)) {
            return "Parameters not set";
        }
        $avatar = $data["avatar"] ?? '';

        $inputData = [
            "profilePhoto" => QB::wrapString($avatar, "'"),
        ];

        // insert image into db

        $avatar = $inputData['profilePhoto'];

        $updateQuery = "UPDATE Users.UserInfo SET profilePhotoUrl = $avatar WHERE UserId = $userId";
        $result = DBConnectionFactory::getConnection()->query($updateQuery);

        if ($result) {
            $result = "Successful!";
        } else {
            $result = "Unsuccessful!";
        }

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

}
