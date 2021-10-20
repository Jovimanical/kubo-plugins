<?php declare (strict_types=1);
/**
 * Estate Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\Estate;

/**
 * class KuboPlugin\Mortgages
 *
 * Estate
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 18/10/2021 06:20
 */
class Estate {
	public static function updateEstateUser(array $data){
		return Estates\Estate::updateEstateUser($data);
	}

    public static function viewEstateUser(int $userId,array $data){
        return Estates\Estate::viewEstateUser($userId,$data);
    }


    public static function uploadEstateUserAvatar(int $userId,array $data){
        return Estates\Estate::uploadEstateUserAvatar($userId,$data);
    }

    public static function sendSupport(int $userId,array $data){
        return Estates\Estate::sendSupport($userId,$data);
    }

}