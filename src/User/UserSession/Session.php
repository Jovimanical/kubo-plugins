<?php declare(strict_types=1);
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

namespace KuboPlugin\User\UserSession;

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;

/**
 * class Session.
 *
 * Session Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 22/10/2020 19:35
 */
class Session
{
	public static function load(int $userId, int $sessionId)
	{
		$query = "SELECT a.*, b.TypeName as AccountTypeName FROM Users.UserSession a INNER JOIN Users.AccountTypes b ON a.AccountTypeId = b.TypeId WHERE a.UserId=$userId AND a.SessionId=$sessionId;";
		$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

		if (count($result) == 1){
			$result[0]["Session"] = unserialize(base64_decode($result[0]["Session"]));
		}

		return $result;
	}

	public static function retrieveDecodedSession(int $userId, int $sessionId, bool $onlyActiveSession=false){
		$query = "SELECT Session FROM Users.UserSession WHERE UserId=$userId AND SessionId=$sessionId";
		$query = $onlyActiveSession ? $query." AND Status=1;" : $query;

		$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

		$session = isset($result[0]) ? unserialize(base64_decode($result[0]["Session"])) : [];

		return $session;
	}

	public static function getActiveSessions(int $userId){
		$query = "SELECT * FROM Users.UserSession WHERE UserId=$userId AND Status=1 ORDER BY DateCreated DESC;";
		$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

		return $result;
	}

	public static function save(int $userId, array $sessionData)
	{
		$serializedSession = serialize($sessionData);
		$encodedSessionString = base64_encode($serializedSession);

		$result = DBQueryFactory::insert("Users.UserSession", [
			"UserId"=>$userId,
			"Session"=>QB::wrapString($encodedSessionString, "'")
		]);

		if (!$result['lastInsertId']){
			//throw an exception, insert was unsuccessful
		}	

		return $result;
	}

	public static function activate(int $resourceId, array $data){
		$sessionId = (int) $data["sessionId"];
		$accountType = (int) $data["accountType"] ?? 0;

		if ($accountType == 0){
			$query = "SELECT TOP 1 AccountType FROM Users.UserAccountType WHERE UserId=$resourceId ORDER BY DateCreated ASC;";
			$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

			$accountType = $result[0]["AccountType"] ?? 0;
		}

		$query = "UPDATE Users.UserSession SET Status=1, AccountTypeId = $accountType, LastModified=CURRENT_TIMESTAMP WHERE UserId=$resourceId AND SessionId=$sessionId;";

		return ["status"=>DBConnectionFactory::getConnection()->exec($query)];
	}

	public static function deactivate(int $resourceId, int $sessionId){
		$query = "UPDATE Users.UserSession SET Status=0, LastModified=CURRENT_TIMESTAMP WHERE UserId=$resourceId AND SessionId=$sessionId;";

		return ["status"=>DBConnectionFactory::getConnection()->exec($query)];
	}	

	public static function deactivateAll(int $resourceId){
		$query = "UPDATE Users.UserSession SET Status=0, LastModified=CURRENT_TIMESTAMP WHERE UserId=$resourceId;";

		return ["status"=>DBConnectionFactory::getConnection()->exec($query)];
	}

    public static function isTokenValid(int $userId, array $data)
    {
    	$isValid = ["status"=>false];

        $sessionId = (int) $data["sessionId"];
        $token = $data["token"];

        $session = self::retrieveDecodedSession($userId, $sessionId, true);

        if (isset($session["token"])){
        	$isValid["status"] = $session["token"] == $token;
        }

        return $isValid;
    }
}