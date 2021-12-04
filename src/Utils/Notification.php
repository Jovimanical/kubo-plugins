<?php declare (strict_types=1);
/**
 * Visitor Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\Utils;

/**
 * class KuboPlugin\Utils\Notification
 *
 * Notification Visitor
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 04/12/2021 07:23
 */
class Notification {
    public static function sendMail(array $data){
        return Notification\SendMail::sendMail($data);
    }
    public static function sendSupport(int $userId, array $data){
        return Notification\SendMail::sendSupport($userId,$data);
    }
}