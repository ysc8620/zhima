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

class match{
    public function __construct(){

    }
}

do{
    # 判斷是否在進行中


}while(false);

echo json_encode($json);