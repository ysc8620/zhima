<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  醉忆花颜 <aminsire@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
class RobotController extends CommonController {

    //机器人管理
    public function index(){
        $httpget = I('get.');
        $lists = M('robots')->limit(20)->select();

        $count 		=  M('robots')->count();

        $Page       = new \Think\Page($count,10);			// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();

        $this->assign('lists',$lists);
        $this->assign('show', $show);
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 机器人编辑
     */
    public function rebot_edit(){
        $httpget = I('get.');

        //print_r( $lists);
        $this->assign('lists',array());
        $this->display();
    }

    /**
     * 群管理
     */
    public function qun(){
        $httpget = I('get.');

        //print_r( $lists);
        $this->assign('lists',array());
        $this->display();
    }

    /**
     * 群编辑
     */
    public function qun_edit(){
        $httpget = I('get.');

        //print_r( $lists);
        $this->assign('lists',array());
        $this->display();
    }

    /**
     * 游戏管理
     */
    public function game(){
        $httpget = I('get.');

        //print_r( $lists);
        $this->assign('lists',array());
        $this->display();
    }

    /**
     * 游戏编辑
     */
    public function game_edit(){
        $httpget = I('get.');

        //print_r( $lists);
        $this->assign('lists',array());
        $this->display();
    }

    /**
     * 群消息管理
     */
    public function message(){
        $httpget = I('get.');

        //print_r( $lists);
        $this->assign('lists',array());
        $this->display();
    }

    /**
     * 消息编辑
     */
    public function message_edit(){
        $httpget = I('get.');

        //print_r( $lists);
        $this->assign('lists',array());
        $this->display();
    }

    /**
     * 机器人命令列表
     */
    public function command(){
        $httpget = I('get.');

        //print_r( $lists);
        $this->assign('lists',array());
        $this->display();
    }

    /**
     * 机器人命令编辑
     */
    public function command_edit(){
        $httpget = I('get.');

        //print_r( $lists);
        $this->assign('lists',array());
        $this->display();
    }

}