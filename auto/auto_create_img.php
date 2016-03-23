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
    exec("/data/wkhtmltoimage 'http://sh.kakaapp.com/index.php?s=/bao/hongbao/qrcode.html&id={$bao['number_no']}&show=yes' /data/website/zhaopian/shares/{$bao['number_no']}.png");
}while(true);