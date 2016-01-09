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
    public function index(){

        redirect(U("/hongbao"));
    	//首页幻灯片获取
    	$this->display();
		//session('user',null);
    }

    public function test(){
        echo 'ok';

    }
}