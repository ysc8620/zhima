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
class IndexController extends BaseController {
    public function index(){

        redirect(U("/zhaopian"));
    	//首页幻灯片获取
    	$this->display();
		//session('user',null);
    }

    public function test(){
        echo 'ok';

    }
}