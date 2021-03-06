<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Zjh\Controller;
use Think\Controller;
use Wechat\Wx;
require_once ROOT_PATH .'/Inc/Library/Wxpay/weixin.php';
class WeixinController extends Controller {
    public function index(){
        $data = date("Y-m-d H:i:s==");
        if(!empty($_GET)){
            $data .= "==GET=".http_build_query($_GET);
        }

        if(!empty($GLOBALS["HTTP_RAW_POST_DATA"])){
            $data .= "==POST=";
        }

        f_log($data , ROOT_PATH.'/weizjh_api.log');

        $weixin = F('weixin','',CONF_PATH);
        //define your token
        define("TOKEN", $weixin['weixin_token']);
        $wechatObj = new \Wechat\Wx();
        $wechatObj->valid();
        $wechatObj->responseMsg();
    }

    /**
     * 网页授权操作
     */
    function oauth(){
        $web_openid = session('openid');
        if(isset($_GET['code']) ){
            $data = \Wechat\Wxapi::get_openid($_GET['code']);
            session('openid', $data['openid']);
            session('access_token', $data['access_token']);
            cookie('openid',$data['openid'],array('expire'=>time()+2592000));

            header("location: ".U('/zjh/weixin/userinfo').'&url='.$_GET['url']. '&token='.$data['access_token']);
            exit();
        }else{
            header("location: ".U('/zjh/weixin/userinfo').'&url='.$_GET['url']);
            exit();
        }
    }

    function userinfo(){
        if(session('openid')){
            if($_GET['token'] && session('openid')){
                $user =  \Wechat\Wxapi::getUserInfo(session('openid'));
            }else{
                $user = \Wechat\Wxapi::getUserInfo(session('openid'),2);
            }

            if($user){
                $user_info = M('user')->where(array('openid'=>session('openid')))->find();
                if($user_info){
                    if($user['openid']){
                        $data['name'] = $user['nickname'];
                        // $data['openid'] = $user['openid'];
                        $data['create_time'] = time();
                        $data['header'] = $user['headimgurl'];
                        $data['sex'] = $user['sex'];
                        $data['unionid'] = $user['unionid'];
                        $data['wx_province'] = $user['province'];
                        $data['wx_city'] = $user['wx_city'];
                        $data['wx_country'] = $user['country'];
                        if($user['subscribe_time']){
                            $data['subscribe_time'] = $user['subscribe_time'];
                        }

                        if(isset($user['subscribe'])){
                            $data['subscribe'] = $user['subscribe'];
                        }

                        if(isset($user['groupid'])){
                            $data['groupid'] = $user['groupid'];
                        }

                        if(isset($user['remark'])){
                            $data['remark'] = $user['remark'];
                        }

                        $data['wx_last_time'] = time();

                        M('user')->where("uin='".$user_info['uin']."'")->save($data);
                    }
                    session('user_id', $user_info['uin']);
                }else{

                    if($user['openid']){
                        //
                        $data['name'] = $user['nickname'];
                        $data['openid'] = $user['openid'];
                        $data['create_time'] = time();
                        $data['header'] = $user['headimgurl'];
                        $data['sex'] = $user['sex'];
                        $data['unionid'] = $user['unionid'];
                        $data['wx_province'] = $user['province'];
                        $data['wx_city'] = $user['wx_city'];
                        $data['wx_country'] = $user['country'];
                        if($user['subscribe_time']){
                            $data['subscribe_time'] = $user['subscribe_time'];
                        }

                        if(isset($user['subscribe'])){
                            $data['subscribe'] = $user['subscribe'];
                        }

                        if(isset($user['groupid'])){
                            $data['groupid'] = $user['groupid'];
                        }

                        if(isset($user['remark'])){
                            $data['remark'] = $user['remark'];
                        }
                        $data['wx_last_time'] = time();
                        $uin = M('user')->add($data);
                        session('user_id', $uin);
                    }

                }
            }else{
                echo 'ERROR 11';
                exit();
            }
        }else{
            // print_r($_SERVER);
            echo 'ERROR 22';
            exit();
        }

        $url = urldecode($_GET['url']);
        if(empty($url)){
            $url = U('/zjh/');
        }

        header("location:".$url."");
        exit();
    }

    /**
     * 异步回调
     */
    public function notify(){
        notify();
    }
}