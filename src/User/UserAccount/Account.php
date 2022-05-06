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

namespace KuboPlugin\User\UserAccount;

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;

/**
 * class KuboPlugin\User\UserAccount\Account.
 *
 * User Account Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 18/09/2020 02:37
 */
class Account {

	/**
     * Creates new user account
     *
     * @param string $email
     * @param string $password
     *
     * @return array
     */
	public static function newAccount(string $email, string $password, string $names){
		$passwordHash = password_hash($password, PASSWORD_DEFAULT);

		$result = DBQueryFactory::insert("Users.Account", [
			"UserEmail"=>QB::wrapString($email, "'"),
			"PasswordHash"=>QB::wrapString($passwordHash, "'")
		]);

		if (!$result['lastInsertId']){
			//throw an exception, insert was unsuccessful
		}

		$companyName = $names ?? '';

        $inputData = [
            "company_name" => QB::wrapString($companyName, "'"),
        ];

        $query = "INSERT INTO Users.UserInfoFieldValues (UserId, FieldId, FieldValue) VALUES (".$result['lastInsertId']." , 2, ".$inputData['company_name'].")";


        $resultData = DBConnectionFactory::getConnection()->exec($query);

		if($resultData){
			return $result;
		} else {
			$result['error']  = "Company name add failed!";
			return $result;
		}


	}

	public static function viewAccounts(int $accountType){
		
	}

	/**
     * Check if an email has already been registered
     *
     * @param string $email
     *
     * @return bool
     */
	public static function checkAccountExistsByEmail(string $email){
		$query = "SELECT UserId FROM Users.Account WHERE UserEmail = '$email'";
		$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

		return count($result) == 1;
	}

	public static function checkAccountExistsById(int $accountId){
		$query = "SELECT UserId FROM Users.Account WHERE UserId = $accountId";
		$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

		return count($result) == 1;
	}

	public static function getUserId(string $email){
		$query = "SELECT UserId FROM Users.Account WHERE UserEmail = '$email';";
		$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

		return $result[0]["UserId"] ?? -1;
	}

	public static function updateKycGroup(int $userId){
		$currGroup = UserKyc::determineKycGroup($userId)["GroupId"];

		$result = -1;

		if ($currGroup != 0){
			$query = "UPDATE Users.Account SET KycGroupId = $currGroup, LastModified = CURRENT_TIMESTAMP WHERE UserId = $userId";
			$result = DBConnectionFactory::getConnection()->exec($query);
		}

		return ["status"=>$result];
	}

    public static function changePassword(int $resourceId, array $data){
        $newPassword = $data["newPassword"] ?? null;
        $oldPassword = $data["currentPassword"] ?? '';

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "SELECT PasswordHash FROM Users.Account WHERE UserId = $resourceId";
		$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if(!is_null($newPassword) && password_verify($oldPassword,$result[0]['PasswordHash'])){
            $query = "UPDATE Users.Account SET PasswordHash = '$passwordHash' WHERE UserId = $resourceId";
			$result = DBConnectionFactory::getConnection()->exec($query);

            return $result;

        }

        return ["errorStatus" => true, "errorMessage" => "Invalid password supplied"]; //@todo: throw an exception here
	}
}



