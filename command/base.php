<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-2-19
 * Time: 下午2:18
 * To change this template use File | Settings | File Templates.
 */

set_time_limit(0);
require_once (__DIR__ . '/config.php');

class base{

    static $json = array(
        'msg_code' => 10001,
        'msg_content' => '',
        'data' => array(
            'type' => 1,
            'uid' =>'',
            'message' => "",
            'expand' =>''
        )
        //'post' => $_POST
    );
    static function dwz($url){
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,"http://dwz.cn/create.php");
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $data=array('url'=>$url);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $strRes=curl_exec($ch);
        curl_close($ch);
        $arrResponse=json_decode($strRes,true);
        if($arrResponse['status'] == 0)
        {
            /**错误处理*/
            // echo iconv('UTF-8','GBK',$arrResponse['err_msg'])."\n";
            return $url;
        }
        /** tinyurl */
        return $arrResponse['tinyurl'];
    }
    /**
     * @param $app
     * @param $param
     */
    static function U($app, $param=array()){
        $url = "http://sh.kakaapp.com/index.php?s=".str_replace('__APP__','',U($app, $param));

        $data = \Wechat\Wxapi::dwz($url);
        if($data['errcode'] == '0' && $data['short_url']){
            return $data['short_url'];
        }else{
            return $url;
        }
    }


    static function initJson($data){
        $json = self::$json;
        $json['data']['uid']   = $data['user']['id'];
        return $json;
    }
}