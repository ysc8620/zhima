<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-2-19
 * Time: 下午2:18
 * To change this template use File | Settings | File Templates.
 */
set_time_limit(0);
header("Content-type:text/html;charset=utf-8");
require_once (__DIR__ . '/config.php');
require_once (__DIR__ . '/command.php');
require_once (__DIR__ . '/sphinxapi.php');

define('APPID', '14569962162794');
define('APPSECRET','a9d93c77993f03bec6af9d4a35f327cb');

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
    $cl = new SphinxClient();
    //$cl->
    $cl->SetServer ( '127.0.0.1', 9312);
    $cl->SetConnectTimeout ( 3 );
    $cl->SetArrayResult ( true );
    $cl->SetMatchMode ( SPH_MATCH_EXTENDED2);

    $word = str_replace('@','',$word);
    $res = $cl->Query ( "@command $word", "*" );
    if(empty($res)){
       flogs(__DIR__.'/search.log', $word);
       $json['msg_code'] = 10005;
       $json['msg_content']  = $word;
       break;
    }

    try{
        if($res['matches']){
            $res = $res['matches'][0];
            $json['command'] = $res['attrs']['command_str'];
            $json['ext'] = $res;
            if($res['attrs']['msgtype'] == 'msg'){
                $msg = M('qun_command')->find($res['id']);
                $json['data']['message'] = $msg['message'];
                break;
            }else{
                $fun =  $res['attrs']['action'];
                if(function_exists($fun)){
                    $json = $fun($data);
                    break;
                }else{
                    $json['msg_code'] = 10006;
                    $json['data']['message'] = "执行接口“{$fun}”不存在";
                    break;
                }
            }
        }

    }catch (\Exception $e){}
//    $is_command = false;
//    $json['command_text'] = $word;
//    foreach($command_list as $command){
//        $preg = preg_match("/^".$command['command']."$/i", $word);
//        if($preg){
//            $is_command = true;
//            $action = $command['action'];
//            $json['action'] = $action;
//            if( $action ){
//                if(method_exists ($obj, $action)){
//                    $json = $obj->$action($data);
//                }
//            }
//
//            break;
//        }
//    }

    if(!$is_command){
        $json['msg_code'] = 10003;
        $json['msg_content'] = 'COMMAND ERROR';
        break;
    }

}while(false);

echo json_encode($json);