<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-2-19
 * Time: 下午2:18
 * To change this template use File | Settings | File Templates.
 */
require_once __DIR__ .'/base.php';

class Msgs extends base{

    static function send($data){
        $json = self::initJson($data);
        $json['data']['message'] = 'ok';
        return $json;
    }

    static function test($data){
        $json = self::initJson($data);
        $json['data']['message'] = "您这是执行test接口获取的数据，当前时间：".date("Y-m-d H:i:s");
        return $json;
    }
}