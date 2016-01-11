<?php
/**
 * 微信服务器回调处理
 */
namespace Wechat;
class Wxapi
{
    static $appid = '';
    static $appsecret = '';

    static function init(){
        $weixin = F('weixin','',CONF_PATH);
        self::$appsecret = $weixin['weixin_appsecret'];
        self::$appid = $weixin['weixin_appid'];

        if(empty(self::$appid) || empty(self::$appsecret)){
            echo 'ERROR DEFINED WEIXIN APPID';
            exit();
        }
    }
    // 获取微信
    static public function getAccessToken(){
        self::init();

        $wexintoken = F('weixintoken','',CONF_PATH);
        if($wexintoken['access_token'] && time() < (intval($wexintoken['access_token_time']) + 7000) ){
            return $wexintoken['access_token'];
        }

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".self::$appid."&secret=".self::$appsecret;

        $tmpInfo = self::httpGet($url);
        $info = json_decode($tmpInfo, true);

        $data['access_token'] = $info['access_token'];
        $data['access_token_time'] = time();

        F('weixintoken', $data,CONF_PATH);
        return $info['access_token'];
    }

    /**
     * 获取js ticket
     */
    static public function getJsApiTicket() {
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        self::init();

        $wexintoken = F('weixinjstoken','',CONF_PATH);
        if($wexintoken['jsapi_ticket'] && time() < (intval($wexintoken['jsapi_ticket_time']) + 7000) ){
            return $wexintoken['jsapi_ticket'];
        }

        $accessToken = self::getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
        $res = json_decode(self::httpGet($url));
        $jsapi_ticket = $res->ticket;

        if ($jsapi_ticket) {
            $data['jsapi_ticket'] = $jsapi_ticket;
            $data['jsapi_ticket_time'] = time();
            F('weixinjstoken', $data,CONF_PATH);
        }

        return $jsapi_ticket;
    }

    /**
     * 获取js 签名信息
     * @return array
     */
    static public function getSignPackage() {
        self::init();
        $jsapiTicket = self::getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = self::createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
        "appId"     => self::$appid,
        "nonceStr"  => $nonceStr,
        "timestamp" => $timestamp,
        "url"       => $url,
        "signature" => $signature,
        "rawString" => $string
        );
        return $signPackage;


     
    }

    /**
     * 获取随机字符串
     * @param int $length
     * @return string
     */
    static private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    // 获取网页用户授权
    static public function authorize(){
        self::init();

        $state = urlencode($_SERVER['REQUEST_URI']);
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $redirect_uri = "http://" . $_SERVER['HTTP_HOST'] . "/weixin/oauth.html?url=".urlencode($url);
        $redirect_uri = urlencode($redirect_uri);
        $url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".self::$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=".$state."#wechat_redirect";
        header("location:".$url."");
        exit;
    }

    /**
     * 获取用户授权信息
     * @param $code
     * @return mixed
     */
    static  function get_openid($code)
    {
        self::init();
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".self::$appid."&secret=".self::$appsecret."&code=".$code."&grant_type=authorization_code";
        return json_decode(self::httpGet($url),true);
    }

    static function getUserInfo($openid,$is_subscribe=1)
    {
        // 授权Access Token https://api.weixin.qq.com/sns/userinfo?access_token=&openid=
        //全局ACCESS_TOKEN获取OpenID的详细信息 https://api.weixin.qq.com/cgi-bin/user/info?access_token=ACCESS_TOKEN&openid=OPENID
        if($is_subscribe == 1){
            $token = session('access_token');
            $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$token."&openid=".$openid;
        }else{
            $token = self::getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$token."&openid=".$openid;
        }
        $tmpInfo = self::httpGet($url);
        return json_decode($tmpInfo,true);
    }

    /**
     * @param $openid
     * @param $w_title
     * @param $w_url
     * @param $w_description
     * @param string $picurl
     * @return mixed
     */
    static  public function send_wxmsg($openid,$w_title,$w_url,$w_description,$picurl='' )
    {

        $accessToken = self::getAccessToken();

        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$accessToken;

        $w_url = $w_url;
        $post_msg = '{
           "touser":"'.$openid.'",
           "msgtype":"news",
           "news":{
               "articles": [
                {
                    "title":"'.$w_title.'",
                    "description":"'.$w_description.'",
                    "url":"'.$w_url.'",
                    "picurl":"'.$picurl.'"
                }
                ]
           }
       }';

        $ret_json = self::httpPost($url, $post_msg);
        $ret = json_decode($ret_json);

        return $ret->errmsg ;

    }

    /**
     *
     * @param $url
     * @param null $data
     * @return mixed|string
     */
    static public  function httpGet($url, $data = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if(curl_errno($ch))
        {
            return curl_error($ch);
        }
        curl_close($ch);
        return $tmpInfo;
    }

    static public function httpPost($url, $data = null)
    {
        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $temp=curl_exec ($ch);
        curl_close ($ch);
        return $temp;
    }
}
