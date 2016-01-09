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
use Wechat\Wx;
class WeixinController extends Controller {
    public function index(){
        //define your token
        define("TOKEN", "weixin");
        $wechatObj = new Wx();
        $wechatObj->valid();


    }


}