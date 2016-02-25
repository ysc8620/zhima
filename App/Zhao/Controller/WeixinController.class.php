<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Zhao\Controller;
use Think\Controller;
use Wechat\Wx;
require_once ROOT_PATH .'/Inc/Library/Wxpay/weizhao.php';
class WeixinController extends Controller {
    public function index(){
        $data = date("Y-m-d H:i:s==");
        if(!empty($_GET)){
            $data .= "==GET=".http_build_query($_GET);
        }

        if(!empty($GLOBALS["HTTP_RAW_POST_DATA"])){
            $data .= "==POST=";
        }

        f_log($data , ROOT_PATH.'/weizhao_api.log');

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

            header("location: ".U('/zhao/weixin/userinfo').'?url='.$_GET['url']. '&token='.$data['access_token']);
            exit();
        }else{
            header("location: ".U('/zhao/weixin/userinfo').'?url='.$_GET['url']);
            exit();
        }
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
                    if($user['openid']){
                        $data['name'] = $user['nickname'];
                        // $data['openid'] = $user['openid'];
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
                    }
                    session('user_id', $user_info['uin']);
                }else{

                    if($user['openid']){
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
            $url = '/zhao/';
        }

        header("location:".$url."");
        exit();
    }

    public function zhaopian(){
        $json = array(
            'error'=>0,
            'message'=>'',
            'data'=>''
        );

        do{
            $id = I('id','', 'strval');
            if($id){
                $order = M('zhaopian_order')->where(array('id'=>$id))->find();

                if($order){
                    $zhaopian = M('zhaopian')->where(array('number_no'=>$order['number_no']))->find();

                    if($zhaopian['state'] != 1){
                        #$this->error("红包不能支付", U('/hongbao/detail', array('id'=>$hongbao['number_no'])));
                        $json['error'] = 1;
                        $json['message'] = "照片不能支付";
                        break;
                    }

                    if($zhaopian['state'] != 1){
                        #$this->error("红包已经凑齐", U('/hongbao/detail', array('id'=>$hongbao['number_no'])));
                        $json['error'] = 1;
                        $json['message'] = "照片已关闭";
                        break;
                    }

                    if($order['state'] == 1){
                        $amount = ceil($order['amount'] *100);
                        if($amount < 1 || $amount > 20000){
                            #$this->error("红包金额不对能支付", U('/hongbao/detail', array('id'=>$order['number_no'])));
                            $json['error'] = 1;
                            $json['message'] = "支付金额超过限制.{$amount}";
                            break;
                        }
                        $data = array();
                        $data['body'] = "红包照片";
                        $data['attach'] = "红包照片";
                        $data['order_sn'] = $order['order_sn'];
                        $data['total_fee'] = $amount;
                        $data['time_start'] = date('YmdHis');
                        $data['time_expire'] =  date("YmdHis", time() + 600);
                        $data['goods_tag'] = "WXG";
                        // $openid = ;//session('openid')?session('openid'):cookie('openid');
                        $data['openid'] = $order['openid'];
                        $str = '';
                        foreach($data as $k=>$v){
                            $str .="$k=$v,";
                        }
//
//                        $this->user = M('user')->find($zhaopian['user_id']);
//
//                        $this->title = "{$this->user['name']}凑红包";
//                        $this->zhaopian = $zhaopian;
//                        $this->order = $order;
//                        $this->id = $id;



                        try{
                            $jsApiParameters = jsapipay($data, false);
                        }catch (\Exception $e){
                            $json['error'] = 1;
                            $json['message'] = "签名失败请再点击一次".$e->getMessage().$str;
                            break;
                        }
                        $json['data'] = json_decode($jsApiParameters);
                        break;
                    }else{
                        //$this->error("红包状态不能支付", U('/hongbao/detail', array('id'=>$order['number_no'])));
                        $json['error'] = 1;
                        $json['message'] = "照片状态不能支付";
                        break;
                    }
                }

            }
            #$this->error("红包状态不能支付", U('/notes'));
                $json['error'] = 1;
                $json['message'] = "照片状态不能支付";
                break;
        }while(false);
        echo json_encode($json);
        die();
    }


    /**
     * 异步回调
     */
    public function notify(){
        notify();
    }
}