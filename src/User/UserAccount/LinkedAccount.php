<?php declare (strict_types=1);

Namespace KuboPlugin\User\UserAccount;

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;

class LinkedAccount {

	public static function addLinkToAccount(int $userId, int $accountId){
		$result = DBQueryFactory::insert("Users.LinkedAccounts", [
			"UserId"=>$userId,
			"LinkedAccount"=>$accountId
		]);

		return $result;
	}

	/**
     * Retrieve all users linked to an account
     *
     * @param int $accountId
     *
     * @return array
     */
	public static function viewLinkedUsers(int $accountId){
		$query = "SELECT a.LinkedAccountId, a.UserId, b.UserEmail, b.DateCreated FROM Users.LinkedAccounts a INNER JOIN Users.Account b ON a.UserId = b.UserId WHERE a.LinkedAccount = $accountId";
		$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

		return $result;
	}

	/**
     * Retrieve all accounts linked to a user
     *
     * @param int $userId
     *
     * @return array
     */
	public static function viewLinkedAccounts(int $userId){
		$query = "SELECT a.LinkedAccountId, a.LinkedAccount, b.UserEmail AS LinkedAccountEmail, b.DateCreated FROM Users.LinkedAccounts a INNER JOIN Users.Account b ON a.LinkedAccount = b.UserId WHERE a.UserId = $userId";
		$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

		return $result;
	}
}