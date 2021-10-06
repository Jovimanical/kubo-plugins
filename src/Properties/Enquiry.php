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

namespace KuboPlugin\Properties;

/**
 * class KuboPlugin\Properties\Enquiry
 *
 * Enquiry Visitor
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 24/08/2021 08:07
 */
class Enquiry {
	public static function newEnquiry(array $data){
		return UserEnquiry\Enquiry::newEnquiry($data);
	}

    public static function viewEnquiries(int $userId){
        return UserEnquiry\Enquiry::viewEnquiry($userId);
    }
}