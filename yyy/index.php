<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2016/9/20
 * Time: 22:22
 */
header("Content-type:text/html;charset=utf-8");
session_start();
require_once $base_path . "/Weixin/MyWechat.class.php";

/**
 * 调用微信类返回 access_token
 * @param  int $type 城市ID
 * @return object 微信公共类的对象
 */
function initWechat($type)
{

    global $options;
    if(empty($options)){
        die('No Found Weixin Option.');
    }

    return new MyWechat($options);
}
$options = array(
    'token' =>"", //填写你设定的key
    'encodingaeskey' => "", //填写加密用的EncodingAESKey
    'appid' => "", //填写高级调用功能的app id
    'appsecret' => "", //填写高级调用功能的密钥
    'mchid' => "",// 支付商户号
    'zhifu'=>""//支付密钥
);


$base_url = "";
$city_id = 23;
$base_path = dir(__FILE__);
$ac = $_GET['ac'];
$wechat = initWechat($city_id);
$FUserId = $_SESSION['openid'.$city_id];

$money = 1;//元


// 判断是否领取奖励
$openid_path = $base_path . "/receive/$FUserId.json";
$receive_info = [];
if(file_exists($openid_path)){
    $data = file_get_contents($openid_path);
    $receive_info = (array)json_decode($data, true);
}


if($ac == 'access'){
    $wechat->valid();
    echo 'success';
    die();
}elseif($ac == 'receive'){
    $json = [
        'state' => 100,
        'msg' => '',
        'data' => []
    ];
    do{
        if(empty($FUserId)){
            $json['state'] = 101;
            $json['msg'] = "没有获取到用户信息";
            break;
        }

        // https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack
        $partner_trade_no = "YY".$FUserId;
        $data = [
            'mch_appid' => $options['appid'],
            'mchid' => $options['mchid'],
            'partner_trade_no' => $partner_trade_no,
            'openid' => $FUserId,
            'check_name' => 'NO_CHECK',
            'amount' => $money * 100,
            'desc' => "摇一摇红包",
        ];
        $json['data'] = $data;
        break;
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $returnData = $wechat->pay($url, $data, $options['zhifu'], $this->type);



    }while(false);
    echo json_encode($json);
    die();
}
// 进行授权
if(! $FUserId){
    //
    $ticket = $_GET['ticket'];
    if (empty($ticket)) {  //ticket 参数为空
        $error = "摇一摇设备无法获取票据";
        die($error);
    }

    //从微信摇一摇接口获取设备id及用户id
    $user = $wechat->getShakeInfoShakeAroundUser($ticket);
    if (empty($user)) {
        $error = "票据用户获取失败,请重新摇一摇";
        die($error);
    }
    //记录用户openid
    $FUserId = $user['data']['openid'];
    $_SESSION['openid'.$city_id] = $FUserId;
}

// 判断是否关注
$userInfo = $wechat->getUserInfo($FUserId);
if ($userInfo) {
    $userSubscribe = $userInfo['subscribe'];
} else {
    $userSubscribe = 0;
}

?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta name="Generator" content="hongbaoos v1.0" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>摇一摇红包</title>
    <meta name="Keywords" content="" />
    <meta name="Description" content="" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link href="<?php echo $base_url;?>css/base.css?t=1474410253" rel="stylesheet" />
    <script type="text/javascript" src="<?php echo $base_url;?>js/jquery.js?t=1474410253"></script>
    <script type="text/javascript" src="<?php echo $base_url;?>js/layer.js?t=1474410253"></script>
</head>

<body>
<style>
    .slotMachine { width: 80px; height: 80px;  border-radius: 50%; overflow: hidden; display: inline-block; text-align: center;}
    .noBorder { border: none !important; background: transparent !important; font: 14px arial;}
    .slotMachine .slot { width: 80px; height: 80px; border-radius: 50%;}
    .default { background:url("<?php echo $base_url;?>images/giftbox-icon.png")  no-repeat; filter:"progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod='scale')";  -moz-background-size:100% 100%; background-size:100% 100%;;}
    .default>div{ display:none;}
    svg{ height: 1px; }
</style>
<script src="<?php echo $base_url;?>js/jquery.slotmachine.js?t=1474410253"></script>
<div class="container" style="background: #fffbf3">
    <div class="detail_header">
        <div class="detail_head_block"><img src="http://wx.qlogo.cn/mmopen/ajNVdqHZLLDhVlGAJZUGTvKCvZEUxZPd8VEWjFmhg6JdENeVPmZ9ovBqw4V0XBsSdPd6hCcDGAWwRe4FgX7nng/0" alt="" class="detail_head_img" /></div>
    </div>
    <div class="cl"></div>
    <div class="from mt40 cl">
        <ul>
            <div class="tit mt20">
                <div class="title">摇中红包</div>
                <div class="remark" style="margin: 16px 0; font-size: 18px">恭喜发财,大吉大利!</div>
                <ul >
                    <div class="tit mt20">
                        <div class="title"><font>您已领取：</font>￥1元</div>                    </div>
                </ul>
            </div>
        </ul>

        <div class=" detail_block success_block show">
            <ul>
                <div class="tit">
                    <div class="remark">
                        <div class="content" style="text-align: center">
                            <div class="clear">
                                <div id="machine1" class="slotMachine default">
                                </div>
                            </div>
                        </div>
                        <div class="remark star_name" style="font-size: 12px;" data-id="0" data-txt="">点击领奖</div>
                    </div>
            </ul>
        </div>
    </div>
    <div class="from "><div class="foot_reamrk">如果24小时内该红包没有凑齐, 系统会自动退款</div></div>
    <div class="cl" ></div>
</div>

<div id="remark_block" style="display: none">
    <div id="shareit">
    </div>
    <span style="text-align: center; background: #fff; padding: 12px; -webkit-border-radius:5px;border-radius:5px; line-height: 18px; border: 1px solid #000; width: 90%; left: 5%;" id="follow">
        <p style="font-size: 1.5em">

             <br/>
            恭喜您~ 获得1元现金红包<br/><br/>
            </p>

        <input type="button" class="btn mc" onclick="$('#remark_block').hide();" style="width: 80%; height: 36px; margin-top: 12px; line-height: 36px;" value="知 道 了" />
       </span>
</div>

<script type="text/javascript">
    //立即分享到微信朋友圈点击事件
    $("#machine1").on("click", function() {
        $.ajax({
            type:'post',
            url:"?ac=",
            async:false,
            cache:false,
            data:{id:id, page:page},
            dataType:'html',
            success:function(result){
                $('#remark_block').show();
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                alert(XMLHttpRequest.status);
                alert(XMLHttpRequest.readyState);
                alert(textStatus);
            }
        });

    });

</script>
</body>
</html>