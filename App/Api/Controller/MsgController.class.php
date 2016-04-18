<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Api\Controller;
use Think\Controller;


class MsgController extends Controller {

    public function index(){
        require_once ROOT_PATH .'/auto/auto_match.php';
        $base_file ="/data/website/zhaopian/auto/command.json";
        $json = array(
            'msg_code' => 10001,
            'msg_content' => '',
            'data' => array(
                'type' => 1,
                'uid' =>'',
                'message' => "",
                'expand' =>''
            ),
            //'post' => $_POST
        );

        do{
            // 验证API
            if($_POST['api'] != APPID){
                $json['msg_code'] = 10002;
                $json['msg_content'] = 'ERROR API';
                break;
            }

            // 验证时间
            if($_POST['time'] < (time() - 100)){
                $json['msg_code'] = 10002;
                $json['msg_content'] = 'ERROR TIME';
                break;
            }

            // 解码
            $data = json_decode($_POST['msg'], true);
            $json['data']['uid'] = $data['user']['id'];
            if(! $json['data']['uid']){
                $json['msg_code'] = 10004;
                $json['msg_content'] = 'ERROR MSG';
                break;
            }

            $word = $data['content']['data'];
            $command_list = json_decode(file_get_contents($base_file), true);
            $obj = new \Automatch($data);
            $is_command = false;
            foreach($command_list as $command){
                $preg = preg_match("/^".$command['command']."$/i", $word);
                if($preg){
                    $is_command = true;
                    $action = $command['action'];
                    if( $action ){
                        if(method_exists ($obj, $action)){
                            $json = $obj->$action($data);
                        }
                    }

                    break;
                }
            }

            if(!$is_command){
                $json['msg_code'] = 10003;
                $json['msg_content'] = 'COMMAND ERROR';
                break;
            }

        }while(false);

        echo json_encode($json);
    }
}
