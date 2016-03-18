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
        $this->title = '新建福利';
        $this->display();
    }

    /**
     * 添加福利
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
            $from_bao_id = I('post.from_id', '','strval');
            if($amount <= 1 || $total < 1){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '请输入福利金额或福利个数';
                break;
            }

            if($amount/$total < 1.05 || $amount/$total > 200){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '平均每个福利范围在1.05-200之间.';
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

                $data['from_user_id'] = $this->user_id;
                $data['from_openid'] = $user['openid'];

                if($from_bao_id){
                    $from_bao = M('bao')->where(array('number_no'=>$from_bao_id))->find();
                    if($from_bao){
                        $data['from_bao_id'] = $from_bao['id'];
                        $data['from_user_id'] = $from_bao['user_id'];
                        $data['from_openid'] = $from_bao['openid'];
                    }
                }

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
                $data['is_rand'] = 1;
                $re = M('bao')->add($data);
            }else{
                $order_sn = $re['order_sn'];
                $number_no = $re['number_no'];
            }

            if($re){

                $bao = M('bao')->where(array('number_no'=>$number_no))->find();
                $this->create_order($bao);

                $json['number_no'] = $from_bao_id?$from_bao_id:$number_no;
                $new = array();
                // redirect(U('/hongbao/detail', array('id'=>$data['number_no'])));
                $new['body'] = "福利";
                $new['attach'] = "福利";
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
                $json['msg_content'] = '福利创建失败.';
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
     * 福利详情
     */
    public function detail(){
        $this->title ="福利详情";

        $id = I('get.id','', 'strval');

        $this->show_share = I('get.show_share', 0,'strval');
        if($id < 1){
            $this->error('请选择查看的福利', U('/bao/notes'));
        }
        $this->hongbao = M('bao')->where(array('number_no'=>$id))->find();

        if(!$this->hongbao){
            $this->error('没找到福利详情', U('/bao/notes'));
        }

        if($this->hongbao['from_bao_id'] > 0){
            $from_bao = M('bao')->where(array('id'=>$this->hongbao['from_bao_id']))->find();
            $this->redirect(U('/bao/hongbao/detail', array('id'=>$from_bao['number_no'])));
            return true;
        }

//        if($this->hongbao['state'] == 1){
//            $this->error('福利还没支付', U('/bao/notes'));
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

        $this->title = "{$this->hongbao_user['name']}发的福利";

        $order_list = M('bao_order')->where(array('bao_id'=>$this->hongbao['id'],'user_id'=>array('gt',0), 'state'=>array('in', array(1,2))))->order("addtime desc")->select();
        $total_order_amount = M('bao_order')->where(array('bao_id'=>$this->hongbao['id'],'user_id'=>array('gt',0), 'state'=>array('in', array(1,2))))->sum('amount');
        $this->total_order_amount = number_format(floatval($total_order_amount), 2);
        $from_bao_list = M('bao')->where(array('from_bao_id'=>$this->hongbao['id'],'state'=>array('in',array(2,3))))->select();
        if($from_bao_list){
            foreach($from_bao_list as $i=>$order){
                $user = M('user')->find($order['user_id']);
                $from_bao_list[$i]['user'] = $user;
            }
        }
        $this->from_bao_list = $from_bao_list;
        $limit_part = $this->hongbao['total_num'] - count($order_list);
        $limit_part = $limit_part<0?0:$limit_part;

//        if($this->hongbao['user_id'] == $this->user_id){
//            $this->share_title = "我发起的福利-￥{$this->hongbao['total_amount']}";
//            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
//            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/logo.jpg";
//            $this->share_desc = "“{$this->hongbao['remark']}” 共{$this->hongbao['total_num']}个，还剩{$limit_part}个";
//        }else{
//
//            if(! $this->receive_order ){
//                $this->share_title = "{$this->hongbao_user['name']}发起的福利-￥{$this->hongbao['total_amount']}";
//            }else{
//                $this->share_title = "我领了”{$this->hongbao_user['name']}“发的福利，￥{$this->receive_order['amount']}元";
//            }
//            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
//            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/logo.jpg";
//            $this->share_desc = "“{$this->hongbao['remark']}” 共{$this->hongbao['total_num']}个，还剩{$limit_part}个";
//        }


        if($this->zhaopian['user_id'] == $this->user_id){
            /*
1，发起人
分享群：
标题： 我发了￥10元福利，快来抢，手慢无！
内容：“恭喜发财，大吉大利”共5份，还剩5份。

分享朋友圈：
标题：我发了￥10元福利，快来抢，手慢无！“恭喜发财，大吉大利”共5份。

2，其他人
分享群：王苏蕴发了￥10元福利，快来抢，手慢无！
内容：“恭喜发财，大吉大利”共5份，还剩5份。

分享朋友圈：
标题：王苏蕴发了￥10元福利，快来抢，手慢无！“恭喜发财，大吉大利”共5份。

3，赞助人
分享群：我赞助王苏蕴发了￥10元福利，快来抢，手慢无！
内容：“恭喜发财，大吉大利”共10份，还剩5份。     （共几份，是显示所有总共的份数，包括之前发的和赞助的，剩几份，就是当前还总共剩下几份）

分享朋友圈： 我赞助王苏蕴发了￥10元福利，快来抢，手慢无！“恭喜发财，大吉大利”共10份。
            */
            $this->share_title_friend = "我发了￥{$this->hongbao['total_amount']}元福利，快来抢，手慢无！“恭喜发财，大吉大利”共{$this->hongbao['total_num']}份。";//"我发布了{$this->zhaopian['total_pic']}张照片，想看吗？“{$this->zhaopian['remark']}”";
            $this->share_title = "我发了￥{$this->hongbao['total_amount']}元福利，快来抢，手慢无！";//"我发布了{$this->zhaopian['total_pic']}张照片，想看吗？";
            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/bao.png";
            $this->share_desc = "“{$this->hongbao['remark']}”共{$this->hongbao['total_num']}份，还剩{$limit_part}份";
        }else{

            $this->share_title_friend = "{$this->hongbao_user['name']}发了￥{$this->hongbao['total_amount']}元福利，快来抢，手慢无！“{$this->hongbao['remark']}”共{$this->hongbao['total_num']}份。";//"我买了{$this->zhaopian_user['name']}发布的照片，推荐！“{$this->zhaopian['remark']}”";
            $this->share_title = "{$this->hongbao_user['name']}发了￥{$this->hongbao['total_amount']}元福利，快来抢，手慢无！";//"我买了{$this->zhaopian_user['name']}的照片，推荐！";

            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/bao.png";
            $this->share_desc = "“{$this->hongbao['remark']}”共{$this->hongbao['total_num']}份，还剩{$limit_part}份";
        }
        $this->default_index = 0;
        $this->is_show_star = false;
        if(count($order_list) == $this->hongbao['total_num']){
            // 设置成功
            if($this->hongbao['state'] == 2){
                $state = array(
                    'state' =>3
                );
                if($order_list[0]['addtime'] > 0){
                    $state['success_time'] = $order_list[0]['addtime'];
                }else{
                    $state['success_time'] = time();
                }
                M('bao')->where(array('id'=>$this->hongbao['id']))->save($state);
            }

            $this->is_show_star = true;
            $this->total_order_amount = number_format($this->hongbao['total_amount'],2);
            $this->use_time = $this->time2Units($this->hongbao['success_time'] - $this->hongbao['addtime']);
        }
        $this->star_order = 0;
        $max_amount = 0;

        if($order_list){
            foreach($order_list as $k=>$order){
                if($order['amount'] > $max_amount){
                    $max_amount = $order['amount'];
                    $this->star_order = $order['id'];
                }
                $order_list[$k]['user'] = M('user')->find($order['user_id']);
            }
        }
        $this->share_link = U('/bao/hongbao/detail', array('id'=>$id), true,true);
        $this->order_list = $order_list;
        $this->id = $id;
        $this->display();
    }

    /**
     * 领取福利
     */
    public function order(){
        $id = I('request.id','', 'strval');
        $json = array(
            'msg_code' => 10001,
            'msg_content' => '',
        );
        do{
            $hongbao = M('bao')->where(array('number_no'=>$id))->find();
            if(!$hongbao){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没找到福利';
                break;
            }

            if($hongbao['state'] != 2){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '福利已经领取完毕.';
                break;
            }
            $order = M('bao_order')->where(array('number_no'=>$id, 'user_id'=>$this->user_id))->find();
            $user = M('user')->find($this->user_id);
            if(!$order){
                $user_id = $this->user_id;
                M('bao_order')->execute("UPDATE bao_order SET user_id='{$user_id}','openid'=>'{$user['openid']}' WHERE number_no='{$id}' AND user_id=0 LIMIT 1");
                $order = M('bao_order')->where(array('number_no'=>$id, 'user_id'=>$this->user_id))->find();
            }

            $honbao_user = M('user')->find($order['bao_user_id']);
            if($order){
                if($order['state'] == 1){
                    $data = array(
                        'partner_trade_no' => $order['order_sn'],
                        're_user_name' => $user['name'],
                        'openid' => $user['openid'],
                        'amount' => $order['amount'] * 100,
                        'desc' => "您成功领取了”{$honbao_user['name']}“发的福利,￥ {$order['amount']}元"
                    );
                    $result = sendPay($data);
                    if($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS'){
                        M('bao_order')->where(array('id'=>$order['id']))->save(array('pay_time'=>time(), 'state'=>2, 'transaction_id'=>$result['payment_no']));
                    }
                }

                $json['msg_content'] = '成功领取福利.';
                break;
            }

            $json['msg_code'] = 10002;
            $json['msg_content'] = '福利领取失败.';
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

    public function create_order($hongbao){
        $total_amount = $hongbao['total_amount'] - $hongbao['total_amount'] * 0.02;
        $total_num = $hongbao['total_num'];
        $order_total_amount = 0;
        for($i=0; $i<$total_num; $i++){
            #$order_total_amount = $total_amount - $total_amount;
            $amount = $this->get_order_amount($total_amount - $order_total_amount,$total_num - $i);
            $data = array(
                'bao_id' => $hongbao['id'],
                'bao_user_id' => $hongbao['user_id'],
                'bao_openid' => $hongbao['openid'],
                'from_bao_id' => $hongbao['from_bao_id'],
                'from_openid' => $hongbao['from_openid'],
                'number_no' => $hongbao['number_no'],
                'order_sn' => get_order_sn('HB'),
                'amount' => $amount,
                'addtime' => time(),
                'state' => 1,
                'openid'=>'',
                'user_id' => 0,
            );
            M('bao_order')->add($data);
            $order_total_amount += $amount;
        }
    }

    /**
     * 设置赞赏
     */
    public function sponsor(){
        $id = I('post.id',0, 'strval');
        $json = array(
            'msg_code' => 10001,
            'msg_content' => '',
        );
        do{
            $hongbao = M('bao')->where(array('number_no'=>$id))->find();
            if($hongbao){
                if($hongbao['user_id'] == $this->user_id){
                    M('bao')->where(array('id'=>$hongbao['id']))->save(array('is_sponsor'=>0));
                }else{
                    $json['msg_code'] = 10002;
                    $json['msg_content'] = '没有权限';
                    break;
                }
            }else{
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没找到红包';
                break;
            }
        }while(false);
        echo json_encode($json);
    }

}