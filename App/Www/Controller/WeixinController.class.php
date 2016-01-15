<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Www\Controller;
use Think\Controller;
use Wechat\Wx;
require_once ROOT_PATH .'/Inc/Library/Wxpay/weixin.php';
class WeixinController extends Controller {
    public function index(){
        $data = date("Y-m-d H:i:s==");
        if(!empty($_GET)){
            $data .= "==GET=".http_build_query($_GET);
        }

        if(!empty($GLOBALS["HTTP_RAW_POST_DATA"])){
            $data .= "==POST=";
        }

        f_log($data , ROOT_PATH.'/weixin_api.log');

        $weixin = F('weixin','',CONF_PATH);
        //define your token
        define("TOKEN", $weixin['weixin_token']);
        $wechatObj = new \Wechat\Wx();
        $wechatObj->valid();
        $wechatObj->responseMsg();
    }

    /**
     * 网页授权操作
     */
    function oauth(){
        $web_openid = session('openid');
        if(isset($_GET['code']) ){
            $data = \Wechat\Wxapi::get_openid($_GET['code']);
            session('openid', $data['openid']);
            session('access_token', $data['access_token']);
            cookie('openid',$data['openid'],array('expire'=>time()+2592000));

            header("location: ".U('/weixin/userinfo').'?url='.$_GET['url']. '&token='.$data['access_token']);
            exit();
        }else{
            header("location: ".U('/weixin/userinfo').'?url='.$_GET['url']);
            exit();
        }
    }

    function test(){
        echo 'ok';
        //$data = \Wechat\Wxapi::send_wxmsg('obb1AuBzVPvw8NE8UZ2gc0web854','测试消息','http://hb.kakaapp.com','你收到消息了吗哈哈','http://hb.kakaapp.com/images/hongbao_bg.png');
       // print_r($data);
    }

    function userinfo(){
        if(session('openid')){
            if($_GET['token'] && session('openid')){
                $user =  \Wechat\Wxapi::getUserInfo(session('openid'));
            }else{
                $user = \Wechat\Wxapi::getUserInfo(session('openid'),2);
            }

            if($user){
                $user_info = M('user')->where(array('openid'=>session('openid')))->find();
                if($user_info){
                    $data['name'] = $user['nickname'];
                    $data['openid'] = $user['openid'];
                    $data['create_time'] = time();
                    $data['header'] = $user['headimgurl'];
                    $data['sex'] = $user['sex'];
                    $data['unionid'] = $user['unionid'];
                    $data['wx_province'] = $user['province'];
                    $data['wx_city'] = $user['wx_city'];
                    $data['wx_country'] = $user['country'];
                    if($user['subscribe_time']){
                        $data['subscribe_time'] = $user['subscribe_time'];
                    }

                    if(isset($user['subscribe'])){
                        $data['subscribe'] = $user['subscribe'];
                    }

                    if(isset($user['groupid'])){
                        $data['groupid'] = $user['groupid'];
                    }

                    if(isset($user['remark'])){
                        $data['remark'] = $user['remark'];
                    }

                    $data['wx_last_time'] = time();

                    M('user')->where("uin='".$user_info['uin']."'")->save($data);
                    session('user_id', $user_info['uin']);
                }else{
                    //
                    $data['name'] = $user['nickname'];
                    $data['openid'] = $user['openid'];
                    $data['create_time'] = time();
                    $data['header'] = $user['headimgurl'];
                    $data['sex'] = $user['sex'];
                    $data['unionid'] = $user['unionid'];
                    $data['wx_province'] = $user['province'];
                    $data['wx_city'] = $user['wx_city'];
                    $data['wx_country'] = $user['country'];
                    if($user['subscribe_time']){
                        $data['subscribe_time'] = $user['subscribe_time'];
                    }

                    if(isset($user['subscribe'])){
                        $data['subscribe'] = $user['subscribe'];
                    }

                    if(isset($user['groupid'])){
                        $data['groupid'] = $user['groupid'];
                    }

                    if(isset($user['remark'])){
                        $data['remark'] = $user['remark'];
                    }
                    $data['wx_last_time'] = time();
                    $uin = M('user')->add($data);
                    session('user_id', $uin);
                }
            }else{
                echo 'ERROR 11';
                exit();
            }
        }else{
            // print_r($_SERVER);
            echo 'ERROR 22';
            exit();
        }

        $url = urldecode($_GET['url']);
        if(empty($url)){
            $url = '/';
        }

        header("location:".$url."");
        exit();
    }

    /**
     * 支付接口
     */
    public function pay(){
        $id = I('id','', 'strval');
        if($id){
            $order = M('hongbao_order')->where(array('order_sn'=>$id))->find();

            if($order){
                $hongbao = M('hongbao')->where(array('number_no'=>$order['number_no']))->find();

                if($hongbao['state'] != 1){
                    $this->error("红包不能支付", U('/hongbao/detail', array('id'=>$hongbao['number_no'])));
                    exit();
                }

                if($hongbao['total_part'] <= $hongbao['total_num']){
                    $this->error("红包已经凑齐", U('/hongbao/detail', array('id'=>$hongbao['number_no'])));
                    exit();
                }

                if(($hongbao['addtime'] + 86400) < time() ){
                    $this->error("红包已经过期", U('/hongbao/detail', array('id'=>$hongbao['number_no'])));
                    exit();
                }

                if($order['state'] == 1){
                    $amount = ceil($order['total_amount'] *100);
                    if($amount < 1){
                        $this->error("红包金额不对能支付", U('/hongbao/detail', array('id'=>$order['number_no'])));
                        exit();
                    }
                    $data['body'] = "凑红包";
                    $data['attach'] = "凑红包";
                    $data['order_sn'] = $order['order_sn'] ;
                    $data['total_fee'] = $amount;
                    $data['time_start'] = date('YmdHis');
                    $data['time_expire'] =  date("YmdHis", time() + 600);
                    $data['goods_tag'] = "WXG";
                    // $openid = ;//session('openid')?session('openid'):cookie('openid');
                    $data['openid'] = $order['openid'];

                    $this->user = M('user')->find($hongbao['user_id']);

                    $this->title = "{$this->user['name']}凑红包";
                    $this->hongbao = $hongbao;
                    $this->order = $order;

                    // $this->jsApiParameters = jsapipay($data, false);
                    $this->display();
                    exit();
                }else{
                    $this->error("红包状态不能支付", U('/hongbao/detail', array('id'=>$order['number_no'])));
                    exit();
                }
            }

        }
        $this->error("红包状态不能支付", U('/notes'));
        exit();
    }

    /**
     * 异步回调
     */
    public function notify(){
        notify();
    }

    /**
     * 订单退款
     */
    function refund(){
        refund(array('transaction_id'=> '1007370008201601132684551338', 'total_fee'=>1, 'refund_fee'=>1));
    }

    function sendhonebao(){
        sendHongBao(array(
            'mch_billno' => get_order_sn(),
            'send_name' => '凑红包',
            're_openid' => 'obb1AuBzVPvw8NE8UZ2gc0web854',
            'total_amount' => '100',
            'wishing' => '恭喜您获得测试红包一个',
            'act_name' => '测试红包',
            'remark' =>'凑红包'
        ));
    }

    function test1(){
        $list = M('hongbao_order')->where("hongbao_id='1' AND state=2")->select();
        $ids = array();
        foreach($list as $r){
            $ids[] = $r['id'];
        }
        $k = array_rand($ids);
        $id = $ids[$k];
        echo $k."<br/>";
        print_r($ids);
        echo "<br/>";
        print_r($id);
         M('hongbao_order')->where("id=$id")->save(array('is_star'=>1));
    }
}