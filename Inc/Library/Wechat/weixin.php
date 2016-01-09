<?php
define('IN_HHS', true);

//error_reporting(0);


require('callback.php');

$wechatObj = new wechatCallbackapi();
$wechatObj -> valid();

$base_url = 'http://' . $_SERVER['SERVER_NAME'] . '/';


#$wechatObj -> responseMsg();
