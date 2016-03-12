<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Cou\Controller;
class HongbaoController extends BaseController {
    public function index(){
        $this->sign = md5(microtime(true));
        session('sign', $this->sign);
        $this->title = '新建红包';
        $this->display();
    }

    public function add(){
        do{
            $sign = I('post.sign');

            if($sign != session('sign')){
                $this->error('请不要重复提交.',U('/hongbao'));
            }else{
                session('sign', microtime(true));
            }
            $amount = I('post.amount',0,'floatval');
            $total = I('post.total',0,'intval');
            $remark = I('post.remark','','htmlspecialchars');

            if($amount <= 0 || $total < 1 || $amount > 200){
                $this->error('红包范围在2-200之间.',U('/hongbao'));
                return false;
            }

            if($amount * $total > 200 || $amount * $total <2){
                $this->error('红包范围在2-200之间.',U('/hongbao'));
                return false;
            }
            // `id`, `number_no`, `user_id`, `part_amount`, `total_amount`, `total_part`, `remark`, `addtime`, `update_time`, `state`
            $user = M('user')->find($this->user_id);
            $data['number_no'] = get_order_sn();
            $data['user_id'] = $this->user_id;
            $data['part_amount'] = $amount;
            $data['total_amount'] = $total * $amount;
            $data['total_part'] = $total;
            $data['remark'] = $remark;
            $data['addtime'] = time();
            $data['state'] = 1;
            $data['openid'] = $user['openid'];

            $re = M('hongbao')->add($data);
            if($re){
                redirect(U('/hongbao/detail', array('id'=>$data['number_no'])));
                exit();
            }else{
                $this->error('红包创建失败', U('/hongbao'));
            }
        }while(false);
        $this->error('红包创建失败', U('/hongbao'));
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
        $this->title ="凑红包详情";

        $id = I('get.id',0, 'strval');

        $this->show_share = I('get.show_share', 0,'strval');
        if($id < 1){
            $this->error('请选择查看的红包', U('/notes'));
        }
        $this->hongbao = M('hongbao')->where(array('number_no'=>$id))->find();

        if(!$this->hongbao){
            $this->error('没找到红包详情', U('/notes'));
        }
        $this->hongbao_amount = $this->hongbao['total_amount'] * 0.98;

        if($this->hongbao['state'] == 2){
            $total_user = M('hongbao_order')->where(array('hongbao_id'=>$this->hongbao['id'], 'state'=>2))->group('user_id')->select();

            $this->total_user = intval(count($total_user));
            $this->use_time = $this->time2Units($this->hongbao['success_time'] - $this->hongbao['addtime']);


        }
        $this->hongbao_user = M('user')->find($this->hongbao['user_id']);
        $this->user = M('user')->find($this->user_id);

        $percentage = ceil($this->hongbao[total_num]/$this->hongbao[total_part]*100);
        $this->percentage = $percentage>100?100:$percentage;

        $difference = $this->hongbao[total_part]-$this->hongbao[total_num];
        $this->difference = $difference < 0?0:$difference;

        $this->wait_total = M('hongbao_order')->where(array("hongbao_id"=>$this->hongbao['id'], "state"=>1, "addtime"=>array('gt', time()-30)))->sum('part_num');
        $this->wait_total = intval($this->wait_total);

        $this->title = "{$this->hongbao_user['name']}发起的凑红包";

//        我发起的的凑红包-￥200
//
//“凑红包，有福利，你懂得”
//共40份，还剩40份
//
//我凑了20元到王苏蕴的红包
//
//“凑红包，有福利，你懂得”
//共40份，还剩40份

        $limit_part = $this->hongbao[total_part] - $this->hongbao[total_num];
        $limit_part = $limit_part<0?0:$limit_part;
        if($this->hongbao['user_id'] == $this->user_id){
            $this->share_title = "我发起的凑红包-￥{$this->hongbao['total_amount']}";
            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/logo.jpg";
            $this->share_desc = "“{$this->hongbao['remark']}” 共{$this->hongbao['total_part']}份，还剩{$limit_part}份";
        }else{
            $amount = M('hongbao_order')->where(array("hongbao_id"=>$this->hongbao['id'], "state"=>2,'user_id'=>$this->user_id))->sum('total_amount');
            $amount = floatval($amount);

            if($amount <= 0 ){
                $this->share_title = "{$this->hongbao_user['name']}发起的凑红包-￥{$this->hongbao['total_amount']}";
            }else{
                $this->share_title = "我凑了{$amount}元到{$this->hongbao_user['name']}的红包";
            }
            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/logo.jpg";
            $this->share_desc = "“{$this->hongbao['remark']}” 共{$this->hongbao['total_part']}份，还剩{$limit_part}份";
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
        $order_list = M('hongbao_order')->where(array(array('number_no'=>$id, 'state'=>array('in', array(2,3,4)))))->order("addtime desc")->select();
        if($order_list){
            foreach($order_list as $k=>$order){

                $user = M('user')->find($order['user_id']);
                if($order[is_star] == 1){
                    $this->default_index = $k;
                    $this->star_name =  $user['name'];
                }
                $order_list[$k]['user'] = $user;
            }
        }
        $this->share_link = U('/hongbao/detail', array('id'=>$id), true,true);
        $this->order_list = $order_list;
        $this->id = $id;
        $this->display();
    }

    /**
     * 红包认购
     */
    public function buy(){
        $this->sign = md5(microtime(true));
        session('sign', $this->sign);

        $this->title ="追加凑红包";
        $id = I('get.id',0, 'strval');
        if($id < 1){
            $this->error('请选择查看的红包', U('/notes'));
        }
        $this->hongbao = M('hongbao')->where(array('number_no'=>$id))->find();

        // $total_amount = intval(M('hongbao_order')->where(array("number_no"=>$id, "state"=>1,'addtime'=>array('gt', time()-1800)))->sum('total_amount'));

        if(!$this->hongbao){
            $this->error('没找到红包详情', U('/notes'));
        }
        $this->user = M('user')->find($this->user_id);

        $this->hongbao_user = M('user')->find($this->hongbao['user_id']);

        $order_list = M('hongbao_order')->where(array(array('number_no'=>$id)))->order("is_star DESC, field(state,2,4,3,1),addtime desc")->select();
        if($order_list){
            foreach($order_list as $k=>$order){
                $order_list[$k]['user'] = M('user')->find($order['user_id']);
            }
        }

        $this->order_list = $order_list;

        $this->display();
    }

    public function order(){
        // $sign = I('post.sign');
        $id = I('post.id','','strval');
        $json = array(
            'error' => 0,
            'message' => '',
            'data'=>''
        );
        do{
            $hongbao = M('hongbao')->where(array('number_no'=>$id))->find();
            if(!$hongbao){
                // $this->error('没找到红包详情', U('/notes'));
                $json['error'] = 1;
                $json['message'] = '没找到红包详情';
                break;
            }

//            if($sign != session('sign')){
//                $this->error('请不要重复提交.',U('/hongbao/buy',array('id'=>$id)));
//            }else{
//                session('sign', microtime(true));
//            }

            $total = I('post.num', 0, 'intval');
           // $total_amount = intval(M('hongbao_order')->where(array("number_no"=>$id, "state"=>1))->sum('total_amount'));
            $total_num = M('hongbao_order')->where(array("hongbao_id"=>$hongbao['id'], "state"=>1, "addtime"=>array('gt', time()-30)))->sum('part_num');
            $total_num = intval($total_num);

            if($total < 1 || ( $total + $hongbao['total_num']+$total_num) > $hongbao['total_part']){
//                $this->error('你已超过红包份额限制,请重新设置份额.',U('/hongbao/buy',array('id'=>$id)));
//                return false;
                $json['error'] = 2;
                $json['message'] = '被人抢先一步了。由于有人在您之前支付，剩余的份数小于您想要购买的份数了，请重新确认参与份数.';
                break;
            }

            $user = M('user')->find($this->user_id);
//            $hongbao_order = M('hongbao_order')->where(array('user_id'=>$this->user_id, 'state'=>1, 'hongbao_id'=>$hongbao['id']))->find();
//            if($hongbao_order){
//                $data = array(
//                    'addtime' => time(),
//                    'part_num' => $total,
//                    'total_amount'=>$hongbao['part_amount'] * $total
//                );
//                $rs = M('hongbao_order')->where(array('id'=>$hongbao_order['id']))->save($data);
//                if($rs){
//                    $json['data'] = $hongbao_order['order_sn'];
//                    break;
//                }else{
//                    $json['error'] = 1;
//                    $json['message'] = '操作失败，请重试.';
//                }
//            }
            $data = array(
                'hongbao_id' => $hongbao['id'],
                'hongbao_user_id' => $hongbao['user_id'],
                'number_no' =>$hongbao['number_no'],
                'order_sn' =>get_order_sn(),
                'user_id' => $this->user_id,
                'part_num' => $total,
                'part_amount' =>$hongbao['part_amount'],
                'total_amount' => $hongbao['part_amount'] * $total,
                'addtime' => time(),
                'state' => 1,
                'openid' => $user['openid']
            );
            $rs = M('hongbao_order')->add($data);

            if($rs){
//                redirect(U('/weixin/pay', array('id'=>$data['order_sn'])));
//                // $this->success('操作成功.',U('/hongbao/detail',array('id'=>$id)));
//                return true;

                $json['data'] = $data['order_sn'];
                break;
            }else{
//                $this->error('操作失败，请重试.',U('/hongbao/buy',array('id'=>$id)));
//                return false;
                $json['error'] = 1;
                $json['message'] = '操作失败，请重试.';
                break;
            }
        }while(false);
        echo json_encode($json);
    }

    public function remark(){
        $this->title ="凑红包玩法";
        $this->display();
    }
}