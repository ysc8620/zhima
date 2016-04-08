<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-4-8
 * Time: 上午9:36
 * To change this template use File | Settings | File Templates.
 */

/*
这是一个测试程序!!!
*/
require("smtp.php");
##########################################
$smtpserver = "email-smtp.us-west-2.amazonaws.com";//SMTP服务器
$smtpserverport = 25;//SMTP服务器端口 25, 465 or 587
$smtpusermail = "info@ilovedeals.sg";//SMTP服务器的用户邮箱
$smtpemailto = "ysc8620@163.com";//发送给谁
$smtpuser = "AKIAJJQWCEU3EAIHG57A";//SMTP服务器的用户帐号
$smtppass = "AvVgMyjw7Xf9dBWegRAYzOE1UewplBIvnpTMxYcOpxqo";//SMTP服务器的用户密码
$mailsubject = "中文";//邮件主题
$mailbody = "<h1>中文</h1>测试下能淤泥新年感";//邮件内容
$mailtype = "HTML";//邮件格式（HTML/TXT）,TXT为文本邮件
##########################################
$smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
$smtp->debug = TRUE;//是否显示发送的调试信息
$rs = $smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);
print_r($rs);

?>