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
		$query = "SELECT * FROM Users.UserSession WHERE UserId=$userId AND SessionId=$sessionId;";
		$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

		if (count($result) == 1){
			$result[0]["Session"] = unserialize(base64_decode($result[0]["Session"]));
		}

		return $result;
	}

	public static function retrieveDecodedSession(int $userId, int $sessionId){
		$query = "SELECT Session FROM Users.UserSession WHERE UserId=$userId AND SessionId=$sessionId;";
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

	public static function activate(int $resourceId, int $sessionId){
		$query = "UPDATE Users.UserSession SET Status=1, LastModified=CURRENT_TIMESTAMP WHERE UserId=$resourceId AND SessionId=$sessionId;";

		return DBConnectionFactory::getConnection()->exec($query);
	}

	public static function deactivate(int $resourceId, int $sessionId){
		$query = "UPDATE Users.UserSession SET Status=0, LastModified=CURRENT_TIMESTAMP WHERE UserId=$resourceId AND SessionId=$sessionId;";

		return DBConnectionFactory::getConnection()->exec($query);
	}	

	public static function deactivateAll(int $resourceId){
		$query = "UPDATE Users.UserSession SET Status=0, LastModified=CURRENT_TIMESTAMP WHERE UserId=$resourceId;";

		return DBConnectionFactory::getConnection()->exec($query);
	}
}