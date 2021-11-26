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
 * class KuboPlugin\Estate
 *
 * Estate
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 18/10/2021 06:20
 */
class Estate {
	public static function updateEstateUser(int $userId,array $data){
		return Estate\Estate::updateEstateUser($userId,$data);
	}

    public static function viewEstateUser(int $userId,array $data){
        return Estate\Estate::viewEstateUser($userId,$data);
    }


    public static function uploadEstateUserAvatar(int $userId,array $data){
        return Estate\Estate::uploadEstateUserAvatar($userId,$data);
    }

    public static function sendSupport(int $userId,array $data){
        return Estate\Estate::sendSupport($userId,$data);
    }

    public static function sendMail(array $data){
        return Estate\Estate::sendMail($data);
    }

    public static function getDashBoardTotal(int $userId){
        return Estate\Estate::getDashBoardTotal($userId);
    }

    public static function searchEstateClient(int $userId,array $data){
        return Estate\Estate::searchEstateClient($userId,$data);
    }

    public static function viewEstateData(int $propertyId, int $floorLevel = 0){
        return Estate\Estate::viewEstateData($propertyId,$floorLevel);
    }

    public static function editEstateData(int $propertyId, array $metadata = []){
        return Estate\Estate::editEstateData($propertyId,$metadata);
    }

    public static function allocateProperty(int $userId, array $data){
        return Estate\Estate::allocateProperty($userId,$data);
    }

    public static function viewBlockAllocationsData(int $propertyId, array $data){
        return Estate\Estate::viewBlockAllocationsData($propertyId, $data);
    }

    public static function viewUnitAllocationsData(int $propertyId, array $data){
        return Estate\Estate::viewUnitAllocationsData($propertyId, $data);
    }

    public static function viewEstateName(int $userId, array $data) {
        return Estate\Estate::viewEstateName($userId, $data);
    }

}