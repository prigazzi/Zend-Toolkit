<?php
/**
 * Bitly API Class.
 * Allows us to make requests sending an url and getting the valid bitl.ly short-url.
 * It is now possible to shorten or expand urls.
 * @todo: We could extend Toolkit_Service_Abstract for "options" funcionality 
 */
class Toolkit_Service_Bitly {
    public static function getUrl($url, $login, $appkey, $shorten = true, $format = 'txt')
    {
        $params = array(
            "login" => "{user}",//$login,
            "apiKey" => "{api_key}",//$appkey,
            "uri" => $url,
            "format" => $format,
        );

        $type = $shorten ? "shorten" : "expand";
        $ch = Toolkit_Http_CurlRequests::createCurlHandler("http://api.bit.ly/v3/".$type."?".http_build_query($params));
        $ans = Toolkit_Http_CurlRequests::makeCurlRequest($ch);
        if($ans['code'] == 200) {
            return $ans['body'];
        } else {
            throw new Exception("there is an error creating the Bitly link");
        }
    }
}