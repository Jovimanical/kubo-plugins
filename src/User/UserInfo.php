<?php declare (strict_types = 1);
/**
 * UserInfo Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\User;

use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;
use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;

/**
 * class KuboPlugin\User
 *
 * User Info Controller
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 20/11/2021 15:22
 */
class UserInfo
{

    /**
     * Creates new user account
     *
     * @param string $email
     * @param string $password
     *
     * @return array
     */
    public static function updateUserInfo(int $userId, array $data){
        return UserInfo\UserInfo::updateUserInfo($userId, $data);
    }

    public static function viewUserInfo(int $userId){
        return UserInfo\UserInfo::viewUserInfo($userId);
    }


    public static function uploadUserInfoAvatar(int $userId, array $data){
        return UserInfo\UserInfo::uploadUserInfoAvatar($userId, $data);
    }

}
