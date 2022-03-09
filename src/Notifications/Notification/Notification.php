<?php declare (strict_types = 1);
/**
 * Notification Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\Notifications\Notification;

use EmmetBlue\Core\Factory\MailerFactory as Mailer;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;
use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;

/**
 * class KuboPlugin\Notifications\Notification
 *
 * Notification
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 6/3/2022 15:28
 */
class Notification
{

    public static $apiKey = "AAAATmd7XM4:APA91bEUW6x7acYPN8gzAJqFVOHjaEzHTt_T6-T-RhvgKeTyNcx7xq-iNAtrGlGrCnDepPTG5JCt8SQjxqBbQiwr55PTuAadb1ktnKw8_1ekmKkFniyfubQPIRWEAOQZMC1bcfkE5esw";

    public static function sendMail(array $data)
    {

        $mail = new Mailer($data['sender'], $data['recipients'], $data['message']);

        if ($mail->send()) {

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

    public static function sendNotifications(array $data)
    {
        if(empty($data)){
            return false;
        }

        $notificationKey = \uniqid();
        $sender = $data["sender"] ?? "";
        $receiver =  $data["receiver"] ?? "";
        $title =  $data["title"] ?? "";
        $notifications = $data["notifications"] ?? "";
        $readStatus = "unread";

        // return progress data
        $query = "SELECT Token FROM Utils.NotificationTokens WHERE UserEmail = '$receiver'";
        $token = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $inputData = [
            "NotificationKey"=>QB::wrapString($notificationKey, "'"),
            "Sender"=>QB::wrapString($sender, "'"),
            "Receiver"=>QB::wrapString($receiver, "'"),
            "Title"=>QB::wrapString($title, "'"),
            "Notifications"=>QB::wrapString($notifications, "'"),
            "ReadStatus"=>QB::wrapString($readStatus, "'"),
        ];

        $result = DBQueryFactory::insert("[Utils].[Notifications]", $inputData, false);

        if($result){
            $mail = new Mailer($sender, $receiver, $notifications);
            if(isset($token) and !is_array($token)){
                $push = \KuboPlugin\Utils\Util::sendNota($token,self::apikey,$title,$notifications);

            } else if (isset($token) and is_array($token)) {
                foreach ($token as $key => $value) {
                    $push = \KuboPlugin\Utils\Util::sendNota($value,self::apikey,$title,$notifications);
                }

            }

            if ($mail->send()) {
    
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }


        

    }

    public static function readNotifications(array $data)
    {

        if(empty($data)){
            return false;
        }

        $notificationKey = $data["notificationKey"] ?? "";
        $readStatus = "read";
        $readDate = \date("Y-m-d H:i:s");

        $inputData = [
            "NotificationKey"=>QB::wrapString($notificationKey, "'"),
            "ReadStatus"=>QB::wrapString($readStatus, "'"),
            "ReadDate"=>QB::wrapString($readDate, "'")
        ];

        // update status
        $updateQuery = "UPDATE Utils.Notifications SET ReadStatus = '$readStatus',ReadDate = '$readDate' WHERE NotificationKey = '$notificationKey'";
        $resultExec = DBConnectionFactory::getConnection()->exec($updateQuery);


        if($resultExec){
            return true;
        } else {
            return false;
        }

    }

    public static function saveNotificationTokens(array $data)
    {

        if(empty($data)){
            return false;
        }

        $tokenData = $data["token"] ?? "";
        $userId =  $data["userId"] ?? 0;
        $userEmail =  $data["userEmail"] ?? "";
        $deviceId = $data["deviceId"] ?? "";

        $inputData = [
            "Token"=>QB::wrapString($tokenData, "'"),
            "UserId"=>$userId,
            "UserEmail"=>QB::wrapString($userEmail, "'"),
            "DeviceId"=>QB::wrapString($deviceId, "'")
        ];

        $result = DBQueryFactory::insert("[Utils].[NotificationTokens]", $inputData, false);

        if($result){
            return true;
        } else {
            return false;
        }

    }

}
