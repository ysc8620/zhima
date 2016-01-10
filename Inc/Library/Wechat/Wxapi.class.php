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
        if($wexintoken['access_token'] && time() < ($wexintoken['access_token_time'] + 7100) ){
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

    // 获取网页用户授权
    static function authorize(){
        self::init();

        $state = urlencode($_SERVER['REQUEST_URI']);

        $redirect_uri = "http://" . $_SERVER['HTTP_HOST'] . "/weixin/oauth.html";
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
