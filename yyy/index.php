<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2016/9/20
 * Time: 22:22
 */
error_reporting(E_ALL); //E_ALL

function cache_shutdown_error() {

    $_error = error_get_last();

    if ($_error && in_array($_error['type'], array(1, 4, 16, 64, 256, 4096, E_ALL))) {

        echo '<font color=red>你的代码出错了：</font></br>';
        echo '致命错误:' . $_error['message'] . '</br>';
        echo '文件:' . $_error['file'] . '</br>';
        echo '在第' . $_error['line'] . '行</br>';
    }
}

register_shutdown_function("cache_shutdown_error");



header("Content-type:text/html;charset=utf-8");
session_start();
$base_path = dirname(__FILE__);

require_once ( $base_path."/Weixin/MyWechat.class.php");

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

function authorize($type)
{

    global $wechat;

    if (isset($_GET['code'])) {

        $info = $wechat->getOauthAccessToken();

        if ($info) {
            $oauthname = "oat" . $type . $info["openid"];
            $_SESSION[$oauthname]= $info['access_token'];
            return $info;
        } else {
            echo "error=";
            // File::write_file(APP_PATH.'log/error.log', "getOauthAccessToken error,errCode:" . $wechat->errCode . "  errMsg: " . $wechat->errMsg,'a+');
            print_r($wechat->errCode);
            print_r($wechat->errMsg);
            die();
        }
        return false;
    } else {
        $url = $wechat->getOauthRedirect('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], uniqid());
        if ($url) {
            header("Location:$url");
            exit;
        }
        return false;
    }
}

$options = array(
    'token' =>"mlmk8899", //填写你设定的key
    'encodingaeskey' => "ok5axEaHCOs5FNz38t7mvLyafUtlxdHpFTMUgKyff4p", //填写加密用的EncodingAESKey
    'appid' => "wxe7dec0577556bf7c", //填写高级调用功能的app id
    'appsecret' => "61d0d68711659113a8dc8bb3befb5391", //填写高级调用功能的密钥
    'mchid' => "1254761001",// 支付商户号
    'zhifu'=>"xunyuantongda6666millionmake9999"//支付密钥
);

$base_url = "";
$city_id = 23;

$from = "{$_GET['from']}";
$from = $from?$from:1;

$ac = "{$_GET['ac']}";
$wechat = initWechat($city_id);
$FUserId = $_SESSION['openid'.$city_id];

$money = 1;//元


// 判断是否领取奖励
$openid_path =  $base_path."/receive/$FUserId.json";
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

        if($receive_info){
            $json['state'] = 101;
            $json['msg'] = "您已领取红包~";
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
//        $json['data'] = $data;
//        break;
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $returnData = $wechat->pay($url, $data, $options['zhifu'], $city_id);
        $json['ret'] = $returnData;
        if ($returnData) {
            if ($returnData['result_code'] != 'SUCCESS') {
                File::write_file($openid_path, json_encode([]),"w+");
                $json['state'] = 101;
                $json['msg'] = '红包稍后到达你的账户~';
                File::write_file("error.log",date("Y-m-d H:i:s=").json_encode($returnData)."\r\n","a+");
                break;
            }else{
                // 更改提现状态
                $d = [
                    'openid'=>$FUserId,
                    'status'=>1,
                    "amount"=>$money
                ];
                File::write_file($openid_path, json_encode($d),"w+");
                $json['msg'] = "恭喜您~ 获得1元现金红包";
                break;
            }
        }else{
            $json['state'] = 101;
            $json['msg'] = '红包稍后到达你的账户';
            break;
        }



    }while(false);
    echo json_encode($json);
    die();
}
// 进行授权
if(! $FUserId){
    //
    if($from == 2){
        $info = authorize($city_id);
        if ($info) {
            $FUserId = $info['openid'];
            $_SESSION['openid'.$city_id] = $FUserId;
            $access_token = $info['access_token'];
            $info = $wechat->getOauthUserinfo($access_token, $FUserId);
            $_SESSION['info'] = $info;
        }
    }else{
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
<div class="container" style="background: #fffbf3">
    <div class="detail_header">
        <div class="detail_head_block"><img src="<?php echo empty($_SESSION['info']['headimgurl'])?$base_url.'images/userimg.jpg':$_SESSION['info']['headimgurl'];?>" alt="" class="detail_head_img" /></div>
    </div>
    <div class="cl"></div>
    <div class="from mt40 cl">
        <ul>
            <div class="tit mt20">
                <div class="title"><?php echo $_SESSION['info']['nickname'];?>摇中红包</div>
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

<?php
if(!$userSubscribe):
?>
    <script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
    <script src="http://zb.weixin.qq.com/nearbycgi/addcontact/BeaconAddContactJsBridge.js"></script>
    <script type="text/javascript">
        var _from = parseInt('<?php echo $from;?>');
        if ( _from == 1) {
            alert("您尚未关注我们的公众号请先关注我们再执行此操作");

            BeaconAddContactJsBridge.invoke('jumpAddContact');

        }

        if ( _from == 2) {
            alert("您尚未关注我们的公众号请先关注我们再执行此操作");
        }
    </script>
    <script type="text/javascript">
            //立即分享到微信朋友圈点击事件
        $("#machine1").on("click", function() {
            layer.msg('请先关注我们', function(){});
        });

</script>
    <?php
        elseif($receive_info ):
    ?>
<script type="text/javascript">
    //立即分享到微信朋友圈点击事件
    $("#machine1").on("click", function() {
        layer.msg('您已经领取红包', function(){});
    });

</script>
<?php else:?>
        <script type="text/javascript">
            //立即分享到微信朋友圈点击事件
            $("#machine1").on("click", function() {
                $.ajax({
                    type:'post',
                    url:"?ac=receive",
                    async:false,
                    cache:false,
                    data:{id:'1'},
                    dataType:'json',
                    success:function(result){
                        if(result.state == 100){
                            $('#remark_block').show();
                        }else{
                            layer.msg(result.msg, function(){});
                        }

                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        alert(XMLHttpRequest.status);
                        alert(XMLHttpRequest.readyState);
                        alert(textStatus);
                    }
                });

            });

        </script>
<?php endif;?>
</body>
</html>