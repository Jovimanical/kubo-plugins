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
        $sender = $data["sender"] ?? [];
        $receiver =  $data["receiver"] ?? [];
        $title =  $data["title"] ?? "";
        $notifications = $data["notifications"] ?? [];
        $readStatus = "unread";

        if (\KuboPlugin\Utils\Util::isJSON($sender)) { // check for json and array conversion
            $sender = str_replace('&#39;', '"', $sender);
            $sender = str_replace('&#34;', '"', $sender);
            $sender = html_entity_decode($sender);
            $sender = json_decode($sender, true);

        }

        if (\KuboPlugin\Utils\Util::isJSON($receiver)) { // check for json and array conversion
            $receiver = str_replace('&#39;', '"', $receiver);
            $receiver = str_replace('&#34;', '"', $receiver);
            $receiver = html_entity_decode($receiver);
            $receiver = json_decode($receiver, true);

        }

        if (\KuboPlugin\Utils\Util::isJSON($notifications)) { // check for json and array conversion
            $notifications = str_replace('&#39;', '"', $notifications);
            $notifications = str_replace('&#34;', '"', $notifications);
            $notifications = html_entity_decode($notifications);
            $notifications = json_decode($notifications, true);

        }

        $queries = [];
        foreach($receiver as $receiverOne){
            $receiverOneAddress = $receiverOne["address"];
            $queries[] = "SELECT Token FROM Utils.NotificationTokens WHERE UserEmail = '$receiverOneAddress'";
        }

        // return progress data
        $query = implode(";", $queries);

        $resultSetArr = [];
        $token = [];

        // looping and building result set through complex chain returned results
        $stmtResult = DBConnectionFactory::getConnection()->query($query);

        do {

            $queryResultArr = $stmtResult->fetchAll(\PDO::FETCH_ASSOC);
            if (count($queryResultArr) > 0) {
                // Add $rowset to array
                array_push($resultSetArr, $queryResultArr);
                $token[] = $queryResultArr[0]["Token"];

            }

        } while ($stmtResult->nextRowset());

       // return $resultSetArr;
       // return $token;
       // $token = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $inputData = [
            "NotificationKey"=>QB::wrapString($notificationKey, "'"),
            "Sender"=>QB::wrapString(json_encode($sender), "'"),
            "Receiver"=>QB::wrapString(json_encode($receiver), "'"),
            "Title"=>QB::wrapString($title, "'"),
            "Notifications"=>QB::wrapString(json_encode($notifications), "'"),
            "ReadStatus"=>QB::wrapString($readStatus, "'"),
        ];

        $result = DBQueryFactory::insert("[Utils].[Notifications]", $inputData, false);

        if($result){
            $mail = new Mailer($sender, $receiver, $notifications);
            if(isset($token) and !is_array($token)){
                $push = \KuboPlugin\Utils\Util::sendNota($token,self::$apiKey,$title,$notifications['body']);

            } else if (isset($token) and is_array($token)) {
                foreach ($token as $key => $value) {
                    $push = \KuboPlugin\Utils\Util::sendNota($value,self::$apiKey,$title,$notifications['body']);
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

        return $data;

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
