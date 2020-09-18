<?php declare (strict_types=1);
/**
 * Visitor Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 *
 */
namespace KuboPlugin\User;

/**
 * class KuboPlugin\User\UserAccount
 *
 * UserAccount Visitor
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 18/09/2020 02:44
 */
class UserAccount {

	/**
     * Creates new user account and sets user account type
     * by registering new database entries
     *
     * @param array $data
     *
     * @return bool | Exception
     */
	public function newAccount(array $data){
		if (isset($data["email"]) && isset($data["password"]) && isset($data["accountType"])){
			$result = UserAccount\Account::newAccount($data["email"], $data["password"]);
			if ($result["lastInsertId"]){
				$accountId = $result["lastInsertId"];
				$setType = UserAccount\AccountType::addAccountType((int)$accountId, (int)$data["accountType"]);

				return true;
			}
		}

		/**
		* @todo throw a proper exception, an unexpected error occurred
		*
		*/
		return false;
	}

	public function viewAccounts(){
	}

	public function addUserAccountType(int $resourceId, array $data){
	}

	public function viewUserAccountTypes(int $resourceId){
	}

	public function addAccountType(int $resourceId, array $data){
	}

	public function removeAccountType(int $resourceId, array $data){
	}

	public function addLinkedAccount(int $resourceId, array $data){
	}

	public function viewLinkedAccounts(int $resourceId){
	}

	public function removeLinkedAccount(int $resourceId, array $data){
	}
}