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

namespace KuboPlugin\Estates\Estate;

/**
 * class KuboPlugin\Estates\Estate
 *
 * Estate
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 18/10/2021 06:20
 */
class Estate {
	public static function updateEstateUser(array $data){
		$company_name = $data["company_name"] ?? null;
        $fullname = $data["fullname"] ?? null;
        $email =  $data["email"] ?? null;
        $about = $data["about"] ?? null;
        $phone = $data["phone"] ?? null;
        $address = $data["address"] ?? null;
        $tel = $data["tel"] ?? null;

        $inputData = [
            "company_name"=>QB::wrapString($company_name, "'"),
            "full_name"=>QB::wrapString($fullname, "'"),
            "email"=>QB::wrapString($email, "'"),
            "phone"=>QB::wrapString($phone, "'"),
            "about"=>QB::wrapString($about, "'"),
            "address"=>QB::wrapString($address, "'"),
            "tel"=>QB::wrapString($tel, "'")
        ];

        $result = DBQueryFactory::insert("[Estate].[User]", $inputData, false);

        return $result;
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