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
        $this->assign('page', $show);
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 机器人编辑
     */
    public function robot_edit(){
        if(IS_POST){
            $post = I('post.');
            $id = $post['id'];
            if($id){
                $post['id'] = $id;
                $post['uptime'] = time();
                $ok = M('robots')->save($post);
            }else{
                $post['addtime'] = time();
                $ok = M('robots')->add($post);
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

    /**
     * 机器人删除
     */
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
        $robot_list = M('robots')->field('id,name')->select();
        $count 		=  M('qun')->count();
        $robots = array();
        foreach($robot_list as $robot){
            $robots[$robot['id']] = $robot['name'];
        }

        foreach($lists as $i=>$qun){
            $lists[$i]['robot_name'] = $qun['robot_id']>0?$robots[$qun['robot_id']]:'未知';
        }
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
     * 群成员列表
     */
    public function user(){
        $lists = M('qun_user')->limit(20)->select();

        $count 		=  M('qun_user')->count();

        $Page       = new \Think\Page($count,20);			// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();

        $this->assign('lists',$lists);
        $this->assign('page', $show);
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 群成员列表
     */
    public function game_level(){
        $lists = M('qun_game_level')->limit(20)->select();
        $game_lists = M('qun_game')->field('id,name')->select();
        $games = array();
        foreach($game_lists as $game){
              $games[$game['id']] = $game['name'];
        }

        //print_r($games);
        foreach($lists as $i=>$level){
            $lists[$i]['game_name'] = $level['game_id']>0?$games[$level['game_id']]:'未知';
        }

        $count 		=  M('qun_game_level')->count();

        $Page       = new \Think\Page($count,20);			// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();

        $this->assign('lists',$lists);
        $this->assign('page', $show);
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 关卡编辑
     */
    public function game_level_edit(){
        if(IS_POST){
            $post = I('post.');
            $id = $post['id'];
            if($id){
                $post['id'] = $id;
                $post['uptime'] = time();
                $ok = M('qun_game_level')->save($post);
            }else{
                $post['addtime'] = time();
                $ok = M('qun_game_level')->add($post);
            }
            if($ok){
                $this->success('成功',$_SERVER['HTTP_REFERER']);
            }else{
                $this->error('失败');
            }
            return ;
        }

        $id = I('get.id');
        $game_list = M('qun_game')->field('id,name')->select();

        $this->assign('game_list', $game_list);
        /**
         * 游戏详情
         */
        $this->assign('level',array());
        if($id){
            $level = M('qun_game_level')->find($id);
            $this->assign('level', $level);
        }

        $this->display();
    }

    /**
     * 游戏删除
     */
    public function game_level_del(){
        $post=I('get.');
        $id = $post['id'];
        if($id){
            $ok = M('qun_game_level')->delete($id);
            if($ok){
                $this->success('成功');
            }else{
                $this->error('失败');
            }
        }
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
        $id =     $httpget['id'];
        if(IS_POST){
            $post = I('post.');
            $id = $post['id'];

            if( $id ){
                $post['id'] = $id;
                $post['uptime'] = time();
                $ok = M('qun_game')->save($post);
            }else{
                $post['addtime'] = time();
                $ok = M('qun_game')->add($post);
            }

            if($ok){
                $this->success('成功',$_SERVER['HTTP_REFERER']);
            }else{
                $this->error('失败');
            }
            return ;
        }

        /**
         * 游戏详情
         */
        $this->assign('game',array());
        if($id){
            $game = M('qun_game')->find($id);
            $this->assign('game', $game);
        }

        $this->display();
    }

    /**
     * 机器人删除
     */
    public function game_del(){
        $post=I('get.');
        $id = $post['id'];
        if($id){
            $ok = M('qun_game')->delete($id);
            if($ok){
                $this->success('成功');
            }else{
                $this->error('失败');
            }
        }
    }

    /**
     * 群消息管理
     */
    public function message(){
        $lists = M('qun_message')->limit(20)->select();

        $count 		=  M('qun_message')->count();

        $Page       = new \Think\Page($count,20);			// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();

        $this->assign('lists',$lists);
        $this->assign('page', $show);
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 消息编辑
     */
    public function message_edit(){
        if(IS_POST){
            $post=I('post.');
            $id = $post['id'];
            if($post['start_time']){
                $post['start_time'] = strtotime($post['start_time']);
            }
            if($id){
                $post['id'] = $id;
                $post['uptime'] = time();
                $ok = M('qun_message')->save($post);
            }else{
                $post['addtime'] = time();
                $ok = M('qun_message')->add($post);
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
            $message  = M('qun_message')->find($id);
            $this->assign('message', $message);
        }

        $qun_list = M('qun')->field('id,nickname')->select();
        $this->assign('qun_list', $qun_list);
        $this->display();
    }

    /**
     * 机器人删除
     */
    public function message_del(){
        $post=I('get.');
        $id = $post['id'];
        if($id){
            $ok = M('qun_message')->delete($id);
            if($ok){
                $this->success('成功');
            }else{
                $this->error('失败');
            }
        }
    }

    /**
     * 机器人命令列表
     */
    public function command(){
        $lists = M('qun_command')->limit(20)->select();
        $game_list = M('qun_game')->field('id,name')->select();
        $games = array();
        foreach($game_list as $game){
            $games[$game['id']] = $game['name'];
        }

        foreach($lists as $i=>$list){
            $lists[$i]['game_name'] = $list['game_id']>0?$games[$list['game_id']]:'不限';
        }

        $count 		=  M('qun_command')->count();

        $Page       = new \Think\Page($count,20);			// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();

        $this->assign('lists',$lists);
        $this->assign('page', $show);
        $this->assign('count', $count);

        $this->display();
    }

    /**
     * 机器人命令编辑
     */
    public function command_edit(){
        if(IS_POST){
            $post=I('post.');
            $id = $post['id'];
            if($id){
                $post['id'] = $id;
                $post['uptime'] = time();
                $ok = M('qun_command')->save($post);
            }else{
                $post['addtime'] = time();
                $ok = M('qun_command')->add($post);
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
        $game_list = M('qun_game')->field('id,name')->select();
        $this->assign('game_list', $game_list);
        if($id){
            $command = M('qun_command')->find($id);
            $this->assign('command', $command);
        }
        $this->display();
    }

    /**
     * 机器人删除
     */
    public function command_del(){
        $post=I('get.');
        $id = $post['id'];
        if($id){
            $ok = M('qun_command')->delete($id);
            if($ok){
                $this->success('成功');
            }else{
                $this->error('失败');
            }
        }
    }

}