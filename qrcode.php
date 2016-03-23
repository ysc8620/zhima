<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 15-8-31
 * Time: 上午7:38
 * To change this template use File | Settings | File Templates.
 */
error_reporting(0);
require_once "auto/config.php";
$id = $_REQUEST['id'];
$t = $_REQUEST['t'];
$file = "qrcode/$id.png";

if(is_file($file)){
    header("content-type: image/png");
    echo file_get_contents($file);
    die();
}
mkdir(dirname($file), 0755,true);
include 'phpqrcode/phpqrcode.php';

$value = "http://sh.kakaapp.com/index.php?s=/bao/hongbao/detail/id/{$id}.html"; //二维码内容

$errorCorrectionLevel = 'L';//容错级别
$matrixPointSize = 10;//生成图片大小
//生成二维码图片
QRcode::png($value,$file, $errorCorrectionLevel, $matrixPointSize, 2);
//$logo = FALSE;//准备好的logo图片
//$QR = 'qrcode.png';//已经生成的原始二维码图
header("content-type: image/png");
echo file_get_contents($file);
//if ($logo !== FALSE) {
//    $QR = imagecreatefromstring(file_get_contents($QR));
//    $logo = imagecreatefromstring(file_get_contents($logo));
//    $QR_width = imagesx($QR);//二维码图片宽度
//    $QR_height = imagesy($QR);//二维码图片高度
//    $logo_width = imagesx($logo);//logo图片宽度
//    $logo_height = imagesy($logo);//logo图片高度
//    $logo_qr_width = $QR_width / 5;
//    $scale = $logo_width/$logo_qr_width;
//    $logo_qr_height = $logo_height/$scale;
//    $from_width = ($QR_width - $logo_qr_width) / 2;
//    //重新组合图片并调整大小
//    imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
//        $logo_qr_height, $logo_width, $logo_height);
//}
//输出图片
// imagepng($QR, 'helloweixin.png');
#echo '<img src="helloweixin.png">';

/*
include 'phpqrcode.php';
$value = 'http://www.cnblogs.com/txw1958/'; //二维码内容
$errorCorrectionLevel = 'L';//容错级别
$matrixPointSize = 6;//生成图片大小
//生成二维码图片
QRcode::png($value, 'qrcode.png', $errorCorrectionLevel, $matrixPointSize, 2);
$logo = 'logo.png';//准备好的logo图片
$QR = 'qrcode.png';//已经生成的原始二维码图

if ($logo !== FALSE) {
    $QR = imagecreatefromstring(file_get_contents($QR));
    $logo = imagecreatefromstring(file_get_contents($logo));
    $QR_width = imagesx($QR);//二维码图片宽度
    $QR_height = imagesy($QR);//二维码图片高度
    $logo_width = imagesx($logo);//logo图片宽度
    $logo_height = imagesy($logo);//logo图片高度
    $logo_qr_width = $QR_width / 5;
    $scale = $logo_width/$logo_qr_width;
    $logo_qr_height = $logo_height/$scale;
    $from_width = ($QR_width - $logo_qr_width) / 2;
    //重新组合图片并调整大小
    imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
        $logo_qr_height, $logo_width, $logo_height);
}
//输出图片
imagepng($QR, 'helloweixin.png');
echo '<img src="helloweixin.png">';   */