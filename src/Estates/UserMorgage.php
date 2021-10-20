<?php declare (strict_types=1);
/**
 * Mortgages Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\Estates;

/**
 * class KuboPlugin\Estates
 *
 * Mortgages Visitor
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 18/10/2021 06:20
 */
class UserMortgage {
	public static function newMortgage(array $data){
		return UserMortgage\UserMortgage::newMortgage($data);
	}

    public static function viewMortgages(int $userId,array $data){
        return UserMortgage\UserMortgage::viewMortgages($userId,$data);
    }


    public static function viewMortgagesBySeven(int $userId,array $data){
        return UserMortgage\UserMortgage::viewMortgagesBySeven($userId,$data);
    }

    public static function viewMortgagesByThirty(int $userId,array $data){
        return UserMortgage\UserMortgage::viewMortgagesByThirty($userId,$data);
    }

    public static function viewMortgagesByNinety(int $userId,array $data){
        return UserMortgage\UserMortgage::viewMortgagesByNinety($userId,$data);
    }

    public static function searchMortgages(int $userId,array $data){
        return UserMortgage\UserMortgage::searchMortgages($userId,$data);
    }
}