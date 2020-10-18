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
}