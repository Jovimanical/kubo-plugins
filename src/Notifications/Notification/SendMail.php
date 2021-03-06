<?php declare (strict_types=1);
/**
 * SendMail Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\Notifications;

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;
use EmmetBlue\Core\Factory\MailerFactory as Mailer;



/**
 * class KuboPlugin\Notifications
 *
 * SendMail
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 25/10/2021 12:12
 */
class SendMail {


    public static function sendMail(array $data){

        $mail = new Mailer($data['sender'],$data['recipients'],$data['message']);
        
        if($mail->send()){

            return true;
        } else {
            return false;
        }

    }

    public static function sendSupport(int $userId, array $data)
    {
        $headers = "From: " . $data['email'] . "" . "\r\n" .
            "Name:  " . $data['name'] . "";

        $receiver = $data['receiver'];
        $sender = $data['email'];

        $subject = $data['subject'];

        $msg = $data['message'];

        return mail($receiver, $subject, $msg, $headers);
    }

    public static function sendNotifications(array $data){

        

        $mail = new Mailer($data['sender'],$data['recipients'],$data['message']);
        
        if($mail->send()){

            return true;
        } else {
            return false;
        }

    }

    public static function readNotifications(array $data){

        

        $mail = new Mailer($data['sender'],$data['recipients'],$data['message']);
        
        if($mail->send()){

            return true;
        } else {
            return false;
        }

    }


}