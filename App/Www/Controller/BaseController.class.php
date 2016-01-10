<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Www\Controller;
use Think\Controller;
class BaseController extends Controller {

    /**
     * 系统初始化
     */
    public function __construct(){
        parent::__construct();
        $this->user_id = session('user_id');

        $openid =  session('openid');
        if(!$openid){
            $openid = cookie('openid');
        }


        // 系统获取当前用户
        if( ! $this->user_id ){

            if($openid){
                // 用户登录
                $user = M('user')->where(array('openid'=>$openid))->find();

                if( $user ){;
                    session('user_id', $user['user_id']);
                    $this->user_id = $user['user_id'];

                    if($user['wx_last_time'] < time() - 86400){
                        if($user['subscribe']){
                            // 自动获取
                            $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                            header("Location: ".U("/weixin/userinfo").'?url='.urlencode($url));
                        }else{
                            // 网页授权
                            \Wechat\Wxapi::authorize();
                            exit();
                        }
                    }
                }
            }else{
                // 网页授权
                \Wechat\Wxapi::authorize();
                exit();
            }
        }
    }

    protected function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
        $this->assign('time', time());
        $this->view->display($templateFile,$charset,$contentType,$content,$prefix);
    }

}