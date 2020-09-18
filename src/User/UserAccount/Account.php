<?php declare (strict_types=1)
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
}