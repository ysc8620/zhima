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
class IndexController extends Controller {

    /**
     * 系统初始化
     */
    public function index(){
       if($_SERVER['HTTP_HOST'] != 'sh.kakaapp.com' && $_SERVER['HTTP_HOST'] != 'w.zhima.cn'){
           return header("Location: http://www.baidu.com");
       }

      $this->display();
    }

    public function test(){
        $data = \Wechat\Wxapi::dwz(U('/zjh/game/detail',array('id'=>234),true,true));
        print_r($data);
        die();
    }

    protected function error($message,$jumpUrl){
        session('error_message', $message);
        redirect($jumpUrl);
    }

}