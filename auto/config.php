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
define('APP_DEBUG',True);

function flogs($file, $content){
    $fopen = fopen($file,'a+');
    if($fopen){
        fwrite($fopen, date("Y-m-d H:i:s")."=".$content."\n");
    }
}

// 定义应用目录
define('APP_PATH',$root_path .'/App/');

define('ROOT_PATH', dirname(dirname(__FILE__)).'/');

// 定义模版目录
define('TMPL_PATH',$root_path .'/Template/');
define('RUNTIME_PATH',$root_path .'/Runtime/');
include($root_path  .'/Inc/ThinkPHP.php');