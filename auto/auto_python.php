<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-2-19
 * Time: 下午2:18
 * To change this template use File | Settings | File Templates.
 */
set_time_limit(0);
$root_path = realpath(dirname(dirname(__FILE__)));
require_once ($root_path . '/auto/config.php');
require_once 'auto_match.php';

define('APPID', '14569962162794');
define('APPSECRET','a9d93c77993f03bec6af9d4a35f327cb');

$base_file = $root_path . "/auto/command.json";
///////////////////////////////////////////////////
//
//$word = "加100";
//preg_match_all("/^(加|加注)\\s*(\\d+)$/i", $word, $re);
//print_r($re);
//die();

//$command_list = json_decode(file_get_contents($base_file));
//$word = '开始';
//foreach($command_list as $command){
//    $json['data']['test'] = "/^".$command->command."$/i".$word;
//    $preg = preg_match("/^".$command->command."$/i", $word);
//    if($preg){
//        $json['data']['message'] =  $command->remark."=ok";
//        break;
//    }else{
//        print_r($command);
//    }
//}
//die();
///////////////////////////////////////////////////
$json = array(
    'msg_code' => 10001,
    'msg_content' => '',
    'command'=>'-',
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
    $json['command_text'] = $word;
    foreach($command_list as $command){
        $preg = preg_match("/^".$command['command']."$/i", $word);
        if($preg){
            $is_command = true;
            $action = $command['action'];
            $json['action'] = $action;
            if( $action ){
                if(method_exists ($obj, $action)){
                    $json = $obj->$action($data);
                    $json['command'] = $command['action'];
                    $json['command_text'] = $word;
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