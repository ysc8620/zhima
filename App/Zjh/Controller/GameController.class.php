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
class GameController extends BaseController {
    public function index(){
        $this->title = "充值中心";

        $this->user = M('user')->find($this->user_id);
    	//首页幻灯片获取
    	$this->display();
    }

    public function detail(){
        $id = I('request.id','','strval');
        $this->game = M('zhajinhua')->where(array('number_no'=>$id))->find();
        $this->user = M('user')->find($this->user_id);
        $this->title = "游戏详情";
        $this->display();
    }
}