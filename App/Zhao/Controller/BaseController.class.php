<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Zhao\Controller;
use Think\Controller;
class BaseController extends Controller {

    /**
     * 系统初始化
     */
    public function _initialize(){

        $this->user_id = session('user_id');
        $this->user_id = 10001;
//
        return true;
        $openid =  session('openid');
        if(!$openid){
            $openid = cookie('openid');
            if($openid){
                session('openid', $openid);
            }
        }


        // 系统获取当前用户
        if( ! $this->user_id ){
            if($openid){
                // 用户登录
                $user = M('user')->where(array('openid'=>$openid))->find();

                if( $user ){
                    session('subscribe', $user['subscribe']);
                    session('user_id', $user['uin']);
                    $this->user_id = $user['uin'];

                    if($user['wx_last_time'] < time() - 86400){
                        if($user['subscribe']){
                            // 自动获取
                            $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                            header("Location: ".U("/zhao/weixin/userinfo").'?url='.urlencode($url));
                        }else{
                            // 网页授权
                            \Wechat\Wxapi::authorize();
                            exit();
                        }
                    }
                }else{
                    \Wechat\Wxapi::authorize();
                    exit();
                }
            }else{
                // 网页授权
                \Wechat\Wxapi::authorize();
                exit();
            }
        }
        else{
            $user = M('user')->find($this->user_id);

            if( $user ){
                session('user_id', $user['uin']);
                // echo $user['subscribe'].'-';
                session('subscribe', $user['subscribe']);
            }
        }

        $signPackage = \Wechat\Wxapi::getSignPackage();
        $this->signPackage = $signPackage;
        $this->share_title = "凑红包, 有福利, 你懂得";
        $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/logo.jpg";
        $this->share_desc = "凑红包, 有福利, 你懂得.";
        $this->subscribe = session('subscribe');

        // if(!$this->user_id)
    }

    protected function error($message,$jumpUrl){
        session('error_message', $message);
        redirect($jumpUrl);
    }

    protected function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
        $this->assign('time', time());
        $this->view->display($templateFile,$charset,$contentType,$content,$prefix);
    }

}

class JSSDK {
    private $appId;
    private $appSecret;
    public function __construct($appId, $appSecret) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }
    public function getSignPackage() {
        $jsapiTicket = $this->getJsApiTicket();
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    private function getJsApiTicket() {
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = json_decode(file_get_contents("jsapi_ticket.json"));
        if ($data->expire_time < time()) {
            $accessToken = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url));
            $ticket = $res->ticket;
            if ($ticket) {
                $data->expire_time = time() + 7000;
                $data->jsapi_ticket = $ticket;
                $fp = fopen("jsapi_ticket.json", "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        } else {
            $ticket = $data->jsapi_ticket;
        }
        return $ticket;
    }
    private function getAccessToken() {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = json_decode(file_get_contents("access_token.json"));
        if ($data->expire_time < time()) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->httpGet($url));
            $access_token = $res->access_token;
            if ($access_token) {
                $data->expire_time = time() + 7000;
                $data->access_token = $access_token;
                $fp = fopen("access_token.json", "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }
    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
}

