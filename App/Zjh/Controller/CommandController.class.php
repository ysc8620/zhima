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
class CommandController extends BaseController {
    public function index(){
        $user = M('user')->find($this->user_id);
        if($user['name'] != '乐圣昌'){
            return header('Location: '.U('/zjh'));
        }
        $this->title = "命令行管理";

        $this->user = $user;
        $this->list = M('qun_command')->select();

    	//首页幻灯片获取
    	$this->display();
    }

    public function edit(){
        $user = M('user')->find($this->user_id);
        if($user['name'] != '乐圣昌'){
            return header('Location: '.U('/zjh'));
        }
        $this->user = $user;
        $this->title = "命令行管理";

        $id = I('request.id',0,'intval');
        if($id){
            $this->command = M('qun_command')->find($id);
        }

        $this->display();
    }

    /**
     * 编辑命令行
     */
    public function post(){
        $json = array(
            'error'=>0,
            'message'=>'',
            'data'=>''
        );
        $user = M('user')->find($this->user_id);
        if($user['name'] != '乐圣昌'){
            return header('Location: '.U('/zjh'));
        }

        $this->title = "命令行管理";

        $id = I('request.id',0,'intval');
        $data['command'] = I('post.command','','strval');
        $data['action'] = I('post.action','','strval');
        $data['params'] = I('post.params','','strval');
        $data['remark'] = I('post.remark','','strval');
        $data['status'] = I('post.status','','strval');
        if(empty($data['command']) || empty($data['action'])){
            $json['error'] = 1;
            $json['message'] = '请输入指令代码或执行接口信息';
            echo json_encode($json);
            die();
        }
        $command = M('qun_command')->where(array('command'=>$data['command']))->select();
        if($id){
            if($command){
                foreach($command as $item){
                    if($item['id'] != $id){
                        $json['error'] = 1;
                        $json['message'] = '指令代码已存在';
                        echo json_encode($json);
                        die();
                    }
                }
            }
        }else{
            if($command){
                $json['error'] = 1;
                $json['message'] = '指令代码已存在';
                echo json_encode($json);
                die();
            }
        }

        $action = M('qun_command')->where(array('action'=>$data['action']))->select();
        if($id){
            if($action){
                foreach($action as $item){
                    if($item['id'] != $id){
                        $json['error'] = 1;
                        $json['message'] = '执行接口已存在';
                        echo json_encode($json);
                        die();
                    }
                }
            }
        }else{
            if($action){
                $json['error'] = 1;
                $json['message'] = '执行接口已存在';
                echo json_encode($json);
                die();
            }
        }

        if($id){
            M('qun_command')->where(array('id'=>$id))->save($data);
        }else{
            M('qun_command')->add($data);
        }

        echo json_encode($json);
        die();
    }
}