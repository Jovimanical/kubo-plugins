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
 * class KuboPlugin\User\UserAccount\AccountType.
 *
 * User Account Type Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 18/09/2020 02:52
 */
class AccountType {
	
	/**
     * Assigns a new user account to an account type.
     *
     * @param int $userId
     * @param int $accountType
     *
     * @return array
     */
	public static function addAccountType(int $userId, int $accountType){
		$result = DBQueryFactory::insert("Users.UserAccountType", [
			"UserId"=>$userId,
			"AccountType"=>$accountType
		]);

		return $result;
	}
}