<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Bao\Controller;
use Wechat\Wx;
require_once ROOT_PATH .'/Inc/Library/Wxpay/weixin.php';
class HongbaoController extends BaseController {
    public function index(){
        $this->sign = md5(microtime(true));
        session('sign', $this->sign);
        $this->title = '新建红包';
        $this->display();
    }

    /**
     * 添加红包
     */
    public function add(){
        $json = array(
            'msg_code' => 10001,
            'msg_content' => '',
        );
        do{
            $id = I('post.id','','strval');
            $amount = I('post.amount',0,'floatval');
            $total = I('post.total',0,'intval');
            $remark = I('post.remark','','htmlspecialchars');
            if($amount <= 1 || $total < 1){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '请输入红包金额或红包个数';
                break;
            }

            if($amount/$total < 1.05 || $amount/$total > 200){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '平均每个红包范围在1.05-200之间.';
                break;
            }
            $re = '';
            if($id){
                $re = M('bao')->where(array('number_no'=>$id))->find();

            }
            $user = M('user')->find($this->user_id);


            if(!$re || ($re && $re['total_amount'] != $amount)){
                // `id`, `number_no`, `user_id`, `part_amount`, `total_amount`, `total_part`, `remark`, `addtime`, `update_time`, `state`
                $order_sn = get_order_sn('HB');

                $data['number_no'] = get_order_sn();
                $number_no = $data['number_no'];
                $data['order_sn'] = $order_sn;
                $data['user_id'] = $this->user_id;
                $data['total_amount'] = $amount;
                $data['total_num'] = $total;
                $data['remark'] = $remark;
                $data['addtime'] = time();
                $data['state'] = 1;
                $data['openid'] = $user['openid'];
                $data['from_user_id'] = $this->user_id;
                $data['from_openid'] = $user['openid'];
                $data['is_rand'] = 1;
                $re = M('bao')->add($data);
            }else{
                $order_sn = $re['order_sn'];
                $number_no = $re['number_no'];
            }

            if($re){
                $json['number_no'] = $number_no;
                $new = array();
                // redirect(U('/hongbao/detail', array('id'=>$data['number_no'])));
                $new['body'] = "红包";
                $new['attach'] = "红包";
                $new['order_sn'] = $order_sn;
                $new['total_fee'] = $amount * 100;
                $new['time_start'] = date('YmdHis');
                $new['time_expire'] =  date("YmdHis", time() + 600);
                $new['goods_tag'] = "BAO";
                // $openid = ;//session('openid')?session('openid'):cookie('openid');
                $new['openid'] = $user['openid'];
                $json['jsApiParameters'] = json_decode(jsapipay($new, false));
                break;
            }else{
                $json['msg_code'] = 10002;
                $json['msg_content'] = '红包创建失败.';
                break;
            }
        }while(false);
        echo json_encode($json);die();
    }

    function time2Units ($time)
    {
        $year   = floor($time / 60 / 60 / 24 / 365);
        $time  -= $year * 60 * 60 * 24 * 365;
        $month  = floor($time / 60 / 60 / 24 / 30);
        $time  -= $month * 60 * 60 * 24 * 30;
        $week   = floor($time / 60 / 60 / 24 / 7);
        $time  -= $week * 60 * 60 * 24 * 7;
        $day    = floor($time / 60 / 60 / 24);
        $time  -= $day * 60 * 60 * 24;
        $hour   = floor($time / 60 / 60);
        $time  -= $hour * 60 * 60;
        $minute = floor($time / 60);
        $time  -= $minute * 60;
        $second = $time;
        $elapse = '';

        $unitArr = array('年'  =>'year', '个月'=>'month',  '周'=>'week', '天'=>'day',
            '小时'=>'hour', '分钟'=>'minute', '秒'=>'second'
        );

        foreach ( $unitArr as $cn => $u )
        {
            if ( $$u > 0 )
            {
                $elapse = $$u . $cn;
                break;
            }
        }

        return $elapse;
    }


    /**
     * 红包详情
     */
    public function detail(){
        $this->title ="红包详情";

        $id = I('get.id',0, 'strval');

        $this->show_share = I('get.show_share', 0,'strval');
        if($id < 1){
            $this->error('请选择查看的红包', U('/bao/notes'));
        }
        $this->hongbao = M('bao')->where(array('number_no'=>$id))->find();

        if(!$this->hongbao){
            $this->error('没找到红包详情', U('/bao/notes'));
        }

//        if($this->hongbao['state'] == 1){
//            $this->error('红包还没支付', U('/bao/notes'));
//        }
        $this->hongbao_amount = $this->hongbao['total_amount'] * 0.98;
        $this->hongbao_user = M('user')->find($this->hongbao['user_id']);
        $this->user = M('user')->find($this->user_id);

        // 是否显示分享
        $this->is_show_share = false;
        $this->receive_order = false;
        if($this->hongbao['user_id'] == $this->user_id){
            if($this->hongbao['is_read'] < 1){
                $this->is_show_share = true;
                // 已经分享
                M('bao')->where(array('id'=>$this->hongbao['id']))->save(array('is_read'=>1));
            }
        }else{
            $this->receive_order = M('bao_order')->where(array('bao_id'=>$this->hongbao['id'], 'user_id'=>$this->user_id))->find();
        }

        $this->title = "{$this->hongbao_user['name']}发起的红包";

        $limit_part = $this->hongbao[total_num] - $this->hongbao[receive_num];
        $limit_part = $limit_part<0?0:$limit_part;

        if($this->hongbao['user_id'] == $this->user_id){
            $this->share_title = "我发起的红包-￥{$this->hongbao['total_amount']}";
            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/logo.jpg";
            $this->share_desc = "“{$this->hongbao['remark']}” 共{$this->hongbao['total_num']}个，还剩{$limit_part}个";
        }else{

            if(! $this->receive_order ){
                $this->share_title = "{$this->hongbao_user['name']}发起的红包-￥{$this->hongbao['total_amount']}";
            }else{
                $this->share_title = "我领了{$this->receive_order['amount']}元到{$this->hongbao_user['name']}的红包";
            }
            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/logo.jpg";
            $this->share_desc = "“{$this->hongbao['remark']}” 共{$this->hongbao['total_num']}个，还剩{$limit_part}个";
        }
        $this->default_index = 0;
        $order_list = M('bao_order')->where(array(array('bao_id'=>$this->hongbao['id'], 'state'=>array('in', array(1,2)))))->order("addtime desc")->select();

        if($order_list){
            foreach($order_list as $k=>$order){
                $order_list[$k]['user'] = M('user')->find($order['user_id']);
            }
        }
        $this->share_link = U('/bao/hongbao/detail', array('id'=>$id), true,true);
        $this->order_list = $order_list;
        $this->id = $id;
        $this->display();
    }

    /**
     * 领取红包
     */
    public function order(){
        $id = I('post.id','', 'strval');
        $json = array(
            'msg_code' => 10001,
            'msg_content' => '',
        );
        do{

            $hongbao = M('bao')->where(array('number_no'=>$id))->find();
            if(!$hongbao){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没找到红包';
                break;
            }

            if($hongbao['state'] != 2){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '红包已经领取完毕.';
                break;
            }
            $total_order = M('bao_order')->where( array('bao_id'=>$hongbao['id']))->count();
            $total_amount = M('bao_order')->where( array('bao_id'=>$hongbao['id']))->sum('amount');
            $total_amount = floatval($total_amount);
            $total_order = intval($total_order);
            if($total_order >= $hongbao['total_num']){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '红包已经领取完毕.';
                break;
            }
            $order = M('bao_order')->where(array('bao_id'=>$hongbao['id'], 'user_id'=>$this->user_id))->find();
            if(! $order){
                // 'id', 'bao_id', 'bao_user_id', 'bao_openid', 'from_bao_id', 'from_user_id', 'from_openid',
                // 'number_no', 'order_sn', 'amount', 'addtime', 'state', 'user_id', 'openid', 'transaction_id'

                $order_total_amount = $hongbao['total_amount'] - $hongbao['total_amount'] * 0.02 - $total_amount - ($hongbao['total_num'] - $total_order);

                $data = array(
                    'bao_id' => $hongbao['id'],
                    'bao_user_id' => $hongbao['user_id'],
                    'bao_openid' => $hongbao['openid'],
                    'from_bao_id' => $hongbao['from_bao_id'],
                    'from_openid' => $hongbao['from_openid'],
                    'number_no' => $hongbao['number_no'],
                    'order_sn' => get_order_sn('HB'),
                    'amount' => $this->get_order_amount($order_total_amount,$hongbao['total_num'] - $total_order),
                    'addtime' => time(),
                    'state' => 1,
                    'user_id' => $this->user_id,

                );
                //$order_id =
                $order_id = M('bao_order')->add($data);

                $order = M('bao_order')->find($order_id);
            }

            if($order){
                if($order['state'] == 1){
                    $user = M('user')->find($this->user_id);
                    $honbao_user = M('user')->find($order['bao_user_id']);
                    $data = array(
                        'partner_trade_no' => $order['order_sn'],
                        're_user_name' => $user['name'],
                        'openid' => $user['openid'],
                        'amount' => $order['amount'] * 100,
                        'desc' => "您成功领取”{$honbao_user['name']}“发的红包,￥ {$order['amount']}元"
                    );
                    $result = sendPay($data);
                    if($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS'){
                        M('bao_order')->where(array('id'=>$order['id']))->save(array('pay_time'=>time(), 'state'=>2, 'transaction_id'=>$result['transaction_id']));
                    }
                }

                $json['msg_content'] = '成功领取红包.';
                break;
            }

            $json['msg_code'] = 10002;
            $json['msg_content'] = '红包领取失败.';
            break;
        }while(false);
        echo json_encode($json);
    }

    /**
     * @param $bao_id
     */
    private function get_order_amount($total_amount, $total_num, $min=1){

        if($total_num == 1){
            return $total_amount;
        }
        $order_total_amount = ($total_amount - ($total_num ))* 0.8;

        $a = 1 + mt_rand(0, $order_total_amount * 100)/100;
        return $a;

    }

    /**
     * 设置赞赏
     */
    public function sponsor(){
        $id = I('get.id',0, 'strval');
        $json = array(
            'msg_code' => 10001,
            'msg_content' => '',
        );
        do{
            $hongbao = M('bao')->where(array('number_no'=>$id))->find();
            if($hongbao){
                if($hongbao['user_id'] == $this->user_id){
                    M('bao')->where(array('id'=>$hongbao['id']))->save(array('is_sponsor'=>0));
                }
            }
        }while(false);
        echo json_encode($json);
    }

}