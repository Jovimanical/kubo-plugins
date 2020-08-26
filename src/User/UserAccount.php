<?php declare (strict_types=1);

Namespace KuboPlugin\Users;

class UserAccount {
	
	public function newAccount(array $data){
		if (isset($data["email"]) && isset($data["password"]) && isset($data["accountType"])){
			$accountId = UserAccount\Account::newAccount($data["email"], $data["password"]);
			$setType = UserAccount\AccountType::addAccountType((int)$accountId, (int)$data["accountType"]);

			if ($setType){
				return false;
			}
		}
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