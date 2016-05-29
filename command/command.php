<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-2-19
 * Time: 下午2:18
 * To change this template use File | Settings | File Templates.
 */
require_once __DIR__ . '/msg.php';

function msg($data){

    return Msgs::send($data);
}

function test($data){
    return Msgs::test($data);
}