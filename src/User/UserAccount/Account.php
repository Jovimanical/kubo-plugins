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
	public static function newAccount(string $email, string $password){
		$passwordHash = password_hash($password, PASSWORD_DEFAULT);

		$result = DBQueryFactory::insert("Users.Account", [
			"UserEmail"=>QB::wrapString($email, "'"),
			"PasswordHash"=>QB::wrapString($passwordHash, "'")
		]);

		if (!$result['lastInsertId']){
			//throw an exception, insert was unsuccessful
		}	

		return $result;
	}

	public static function viewAccounts(){
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
}