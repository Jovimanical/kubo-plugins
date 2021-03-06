<?php declare (strict_types = 1);
/**
 * Controller Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\Utils\Utils;

/**
 * class KuboPlugin\Utils\Utils
 *
 * Util Controller
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 09/12/2021 11:44
 */
class Util
{
    public static function camelToSnakeCase(String $string, String $sc = "_")
    {
        return strtolower(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', $sc, $string));
    }

    public static function isJSON(String $stringData)
    {
        if (is_string($stringData)) {
            $stringData = str_replace('&#39;', '"', $stringData);
            $stringData = str_replace('&#34;', '"', $stringData);
            $stringData = html_entity_decode($stringData);
            return is_string($stringData) && is_array(json_decode($stringData, true)) ? true : false;
        } else {
            return false;
        }
    }

    public static function clientRequest(String $url, String $method = 'GET', array $data = [], String $header = "")
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        if ($header != "") {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
        }

        if ($method == 'POST') {

            curl_setopt($ch, CURLOPT_POST, 1);
            if(isset($data['property_metadata'])){
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }

        }

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;

    }

    public static function sendNota($token, $apikey, $title, $message, $channelId = 100, $data = [] )
    {
        // API access key from Google API's Console
        // replace API
       // define('API_ACCESS_KEY', $apikey);
        $tokenData = $token;
        $msg = array
            (
            'body' => $message,
            'title' => $title,
            'vibrate' => 1,
            'sound' => 'default',

            // you can also add images, additionalData
        );

        $fields = array
            (
            'to' => $tokenData,
            'notification' => $msg,
            'channel_id' => $channelId,

        );
        $headers = array
            (
            'Authorization: key=' . $apikey,
            'Content-Type: application/json',
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);
        //$resultData = json_encode($result);
        $resultData = (string) $result;

        if ($resultData == null) {
            return false;
        } else {
            return true;
        }

    }

    public static function recurseRmdir($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::recurseRmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public static function checkAuthorization()
    {

       // Get Authorization-Token header info
        $authHeader = $_SERVER['HTTP_AUTHORIZATION_TOKEN'];
        $authHeaderValues = explode(",",$authHeader);
        $tokenHead = $authHeaderValues[0];
        $sessionIdHead = (int)$authHeaderValues[1];

        $userIdHead = (int)$authHeaderValues[2];

        if(isset($_SESSION['userId']) && $_SESSION['userId'] == $userIdHead){

        } else {
            die("Unauthorized access or ensure your login/token has not expired !");
        }

        $authCheck = \KuboPlugin\User\UserSession\Session::retrieveDecodedSession($userIdHead,$sessionIdHead);

        if($tokenHead == $authCheck['token']){

        } else {
             die("Unauthorized access or ensure your login/token has not expired !");
        }


    }

    public static function serializeObject($object){
        return serialize($object);
    }

    public static function unserializeObject($str){
        $data = html_entity_decode(unserialize($str));
        $reps = ["\n"=>"",'\\'=>"", "&#39;"=>"\""];
        foreach($reps as $dirt=>$val){
            $data = str_replace($dirt, $val, $data);
        }

        return $data;
    }

    public static function getCoordinateDistance(float $latitudeFrom, float $longitudeFrom, float $latitudeTo, float $longitudeTo, int $earthRadius = 6371000)
    {

        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
        pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        return (int) (($angle * $earthRadius) / 1000);
    }

}
