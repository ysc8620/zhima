<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Bao\Controller;
class HongbaoController extends BaseController {
    public function index(){
        $this->sign = md5(microtime(true));
        session('sign', $this->sign);
        $this->title = '新建红包';
        $this->display();
    }

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
            $hongbao = '';
            if($id){
                $re = M('bao')->where(array('number_no'=>$id))->find();

            }
            if(!$re || $re['amount'] != $amount){
                // `id`, `number_no`, `user_id`, `part_amount`, `total_amount`, `total_part`, `remark`, `addtime`, `update_time`, `state`
                $user = M('user')->find($this->user_id);
                $data['number_no'] = get_order_sn();
                $data['order_sn'] = get_order_sn();
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
            }

            if($re){
                // redirect(U('/hongbao/detail', array('id'=>$data['number_no'])));
                $data['body'] = "红包";
                $data['attach'] = "红包";
                $data['order_sn'] = $data['order_sn'] ;
                $data['total_fee'] = $amount;
                $data['time_start'] = date('YmdHis');
                $data['time_expire'] =  date("YmdHis", time() + 600);
                $data['goods_tag'] = "WXG";
                // $openid = ;//session('openid')?session('openid'):cookie('openid');
                $data['openid'] = $user['openid'];
                $data['number_no'] = $data['number_no'];
                $json['jsApiParameters'] = jsapipay($data, false);
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
//            $this->error('红包还没支付', U('/notes'));
//        }
        $this->hongbao_amount = $this->hongbao['total_amount'] * 0.98;

        if($this->hongbao['state'] == 2){
            $this->use_time = $this->time2Units($this->hongbao['success_time'] - $this->hongbao['addtime']);
        }
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


            if($this->receive_order ){

                $this->share_title = "{$this->hongbao_user['name']}发起的红包-￥{$this->hongbao['total_amount']}";
            }else{
                $this->share_title = "我领了{$this->receive_order['amount']}元到{$this->hongbao_user['name']}的红包";
            }
            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/logo.jpg";
            $this->share_desc = "“{$this->hongbao['remark']}” 共{$this->hongbao['total_num']}个，还剩{$limit_part}个";
        }
        $this->default_index = 0;
        $cookie_key = 'id'.$this->hongbao['id'].'_'.$this->user_id;
        $is_show = intval(cookie($cookie_key));
        //print_r($_COOKIE);
        if(!$is_show && $this->hongbao['state'] == 2){
            cookie($cookie_key, 1,array('expire'=>time()+2592000));
        }
        $this->is_show = $is_show?true:false;
        $this->star_name = '';
        $order_list = M('bao_order')->where(array(array('number_no'=>$id, 'state'=>array('in', array(1,2)))))->order("addtime desc")->select();
        $this->order_amount = M('bao_order')->where(array(array('number_no'=>$id, 'state'=>array('in', array(1,2)))))->sum('amount');
        $this->order_amount = floatval($this->order_amount);
        if($order_list){
            foreach($order_list as $k=>$order){

                $user = M('user')->find($order['user_id']);
                $order_list[$k]['user'] = $user;
            }
        }
        $this->share_link = U('/bao/hongbao/detail', array('id'=>$id), true,true);
        $this->order_list = $order_list;
        $this->id = $id;
        $this->display();
    }

}