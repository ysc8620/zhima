<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-3-13
 * Time: 上午10:54
 * To change this template use File | Settings | File Templates.
 */
header("Content-type:text/html;charset=utf-8");
echo "<pre>";
print_r($_SERVER);
print_r($_COOKIE);
print_r($_SESSION);
echo "</pre>";
#exec("./http://sh.kakaapp.com/index.php?s=/bao/hongbao/qrcode.html&id=20160321103653575449&show=yes");