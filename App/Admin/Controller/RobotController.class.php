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

        $Page       = new \Think\Page($count,20);			// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();

        $this->assign('lists',$lists);
        $this->assign('show', $show);
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 机器人编辑
     */
    public function robot_edit(){
        if(IS_POST){
            $post=I('post.');
            $id = $post['id'];
            if($id){
                $post['id']=$id;
                $ok=M('robots')->save($post);
            }else{
                $post['addtime'] = time();
                $ok=M('robots')->add($post);
            }
            if($ok){
                $this->success('成功',$_SERVER['HTTP_REFERER']);
            }else{
                $this->error('失败');
            }
            return ;
        }

        $httpget = I('get.');
        $id = $httpget['id'];

        if($id){
            $robot = M('robots')->find($id);
            $this->assign('robot', $robot);
        }

        $this->display();
    }

    public function robot_del(){
        $post=I('get.');
        $id = $post['id'];
        if($id){
            $ok = M('robots')->delete($id);
            if($ok){
                $this->success('成功');
            }else{
                $this->error('失败');
            }
        }
    }

    /**
     * 群管理
     */
    public function qun(){
        $lists = M('qun')->limit(20)->select();

        $count 		=  M('qun')->count();

        $Page       = new \Think\Page($count,20);			// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();

        $this->assign('lists',$lists);
        $this->assign('page', $show);
        $this->assign('count', $count);
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
        $lists = M('qun_game')->limit(20)->select();

        $count 		=  M('qun_game')->count();

        $Page       = new \Think\Page($count,20);			// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();

        $this->assign('lists',$lists);
        $this->assign('page', $show);
        $this->assign('count', $count);
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
        $lists = M('robots')->limit(20)->select();

        $count 		=  M('robots')->count();

        $Page       = new \Think\Page($count,20);			// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();

        $this->assign('lists',$lists);
        $this->assign('show', $show);
        $this->assign('count', $count);

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