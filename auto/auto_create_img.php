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

include($root_path . '/auto/config.php');

do{
    $bao = M('bao')->where(array('is_create'=>0,'from_bao_id'=>0, 'state'=>array('in', array(2,3))))->find();
    if(!$bao){
        break;
    }
    echo $bao['number_no']."\r\n";
    echo exec("/data/wkhtmltoimage --crop-h 750 --crop-w 400 --crop-x 280 --crop-y 0 'http://sh.kakaapp.com/index.php?s=/bao/hongbao/qrcode.html&id={$bao['number_no']}&show=yes' /data/website/zhaopian/shares/{$bao['number_no']}.png");
    M('bao')->where(array('id'=>$bao['id']))->save(array('is_create'=>1));
}while(true);