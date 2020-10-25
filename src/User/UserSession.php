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
 * class KuboPlugin\User\UserSession
 *
 * UserSession class
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 22/10/2020 19:31
 */
class UserSession {

	/**
     * Logs a User In
     *
     * @param string $username
     * @param string $password
     */
    public static function login(array $data){
        $username = $data["username"];
        $password = $data["password"];
        $sessionInfo = $data["sessionInfo"] ?? [];

        if (UserSession\Login::isLoginDataValid($username, $password))
        {
            $id = UserAccount\Account::getUserId($username);
            $token = substr(str_shuffle(MD5(microtime())), 0, 50);

            $sessionData = [
            	"token"=>$token,
            	"timestamp"=>microtime(),
            	"sessionInfo"=>$sessionInfo
            ];

            $result = UserSession\Session::save((int)$id, $sessionData);

            return ["status"=>true, "userId"=>$id, "sessionId"=>$result["lastInsertId"], "sessionData"=>$sessionData];
        }

        return ["status"=>false];
    }

    public static function logout(int $userId, array $data){
    	$sessionId = $data["session"];

    	$result = UserSession\Session::deactivate($userId, (int)$sessionId);

    	return $result;
    }

    public static function isTokenValid(int $userId, array $data){
    	return UserSession\Session::isTokenValid($userId, $data);
    }
}