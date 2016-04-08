<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-2-19
 * Time: 下午2:18
 * To change this template use File | Settings | File Templates.
 */
set_time_limit(0);

define('APPID', '14569962162794');
define('APPSECRET','a9d93c77993f03bec6af9d4a35f327cb');

$root_path = realpath(dirname(dirname(__FILE__)));
include($root_path . '/auto/config.php');
$json = array(
    'msg_code' => 10001,
    'msg_content' => '',
    'data' => array(
        'type' => 1,
        'uid' => '@@ca1f367d666019cd23ef97b1bd1244ba06749363e96312b7337ea44a8a628c94',
        'message' => "@机器猫 test"
    ),
    'post' => $_POST
);

do{
    # 判斷是否在進行中
    $zz = array(
        '@德州机器人 开始',
        '跟牌',
        '跟'
    );
    $word = '@德州机器人 开始';
    foreach($zz as $z){
        $preg = preg_match("/$z/", $word);
        if($preg){
            echo $z . "=ok";
        }else{
            echo $z . "=full";
        }
    }
}while(false);

echo json_encode($json);