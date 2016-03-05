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
require_once ROOT_PATH .'/Inc/Library/Wxpay/weizhao.php';
class ZhaopianController extends BaseController {
    public function index(){
        $this->sign = md5(microtime(true));
        session('sign', $this->sign);
        $this->title = '发布红包照片';
        $this->display();
    }

    public function add(){
        set_time_limit(0);
        do{
            $sign = I('post.sign');

            if($sign != session('sign')){
                $this->error('请不要重复提交.',U('/zhao/zhaopian'));
            }else{
                session('sign', microtime(true));
            }
            $is_rand = I('post.is_rand',0,'intval');
            $amount = I('post.amount',1.05,'floatval');
            $remark = I('post.remark','','htmlspecialchars');
            $media_ids = I('post.media_ids', '', 'strval');
            if(!$is_rand){
                if($amount < 1.05 || $amount > 200){
                    $this->error('价格在1.05-200之间.',U('/zhao/zhaopian'));
                    return false;
                }
            }

            if(empty($media_ids)){
                $this->error('请选择要发布的红包照片.',U('/zhao/zhaopian'));
                return false;
            }
            // `id`, `number_no`, `user_id`, `part_amount`, `total_amount`, `total_part`, `remark`, `addtime`, `update_time`, `state`
            $user = M('user')->find($this->user_id);
            $data['number_no'] = get_order_sn('zp');
            $data['user_id'] = $this->user_id;
            $data['remark'] = $remark;
            $data['is_rand'] = $is_rand;
            if($is_rand){
                $data['min_amount'] = 1.05;
                $data['max_amount'] = 5;
            }else{
                $data['min_amount'] = $amount;
                $data['max_amount'] = 200;
            }
            $media_ids = trim($media_ids, ',');
            $media_ids = explode(',', $media_ids);
            $data['total_pic'] = count($media_ids);
            $data['addtime'] = time();
            $data['state'] = 1;
            $data['openid'] = $user['openid'];
            $re = M('zhaopian')->add($data);
            if($re){

                $bool = true;
                foreach($media_ids as $id){
                    $pic = array(
                        'zhaopian_id'=>$re,
                        'user_id'=> $this->user_id,
                        'media_id'=>$id,
                        'addtime'=>time()
                    );
                    if($bool){
                        $pic['is_default'] = 1;
                        $bool = false;
                    }else{
                        $pic['is_default'] = 0;
                    }
                    $pic_id = M('zhaopian_pic')->add($pic);
                }
                redirect(U('/zhao/zhaopian/detail', array('id'=>$data['number_no'])));
                exit();
            }else{
                $this->error('红包照片创建失败', U('/zhao/zhaopian'));
            }
        }while(false);
        $this->error('红包照片创建失败', U('/zhao/zhaopian'));
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

    function getRandomAmount(){
        $num = mt_rand(1,10);
        //
        if($num <= 5){
            $amount = $this->randomFloat(1.05, 1.5);
        }elseif(5 < $num && $num <= 7){
            $amount = $this->randomFloat(1.5, 2);
        }elseif($num < 7 && $num <= 9){
            $amount = $this->randomFloat(2, 3);
        }else{
            $amount = $this->randomFloat(3, 5);
        }

        return number_format($amount, 2);
    }

    function randomFloat($min = 0, $max = 1) {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }

    public function del(){
        $id = I('get.id', '','strval');
        $zhaopian = M('zhaopian')->where(array('number_no'=>$id))->find();
        if(empty($zhaopian)){
            return $this->error('找不到要删除的照片', U('/zhao/notes'));
        }
        if($zhaopian['user_id'] != $this->user_id ){
            return $this->error('您没有删除该照片权限', U('/zhao/notes'));
        }
        // 删除照片
        M('zhaopian')->where(array('id'=>$zhaopian['id']))->save(array('state'=>99));
        return $this->error('照片删除成功', U('/zhao/notes'));
    }

    public function zhuanfa(){
        $id = I('get.id',0, 'strval');
        if($id < 1){
            $this->error('请选择查看的红包照片', U('/zhao/notes'));
        }
        $zhaopian = M('zhaopian')->where(array('number_no'=>$id))->find();

        if(!$zhaopian){
            $this->error('没找到红包照片详情', U('/zhao/notes'));
        }

        if($zhaopian['state'] == 99){
            $this->error('该红包照片已删除', U('/zhao/notes'));
        }

        $new = M('zhaopian')->where(array('zhuan_id'=>$zhaopian['id'], 'user_id'=>$this->user_id))->find();
        if($new){
            $this->error('转发成功', U('/zhao/zhaopian/detail',array('id'=>$new['number_no'])));
        }
        $zhaopian_id = $zhaopian['id'];
        unset($zhaopian['id']);
        $user = M('user')->find($this->user_id);
        $zhaopian['number_no'] = get_order_sn('zp');

        $zhaopian['is_zhuan'] = 1;
        $zhaopian['zhuan_user_id'] = $zhaopian['user_id'];
        $zhaopian['zhuan_id'] = $zhaopian_id;

        $zhaopian['user_id'] = $this->user_id;
        $zhaopian['openid'] = $user['openid'];
        $zhaopian['create_time'] = time();
        $zhaopian['addtime'] = time();
        $rs = M('zhaopian')->add($zhaopian);
        if($rs){
            $list = M('zhaopian_pic')->where(array('zhaopian_id'=>$zhaopian_id))->select();
            foreach($list as $v){
                $v['zhaopian_id'] = $rs;
                $v['user_id'] = $this->user_id;
                unset($v['id']);
                M('zhaopian_pic')->add($v);
            }
        }
        $this->success('准备就绪，赶快分享吧', U('/zhao/zhaopian/detail',array('id'=>$zhaopian['number_no'])));
    }

    /**
     * 红包详情
     */
    public function detail(){
        $this->title ="红包照片详情";

        $id = I('get.id',0, 'strval');

        $this->show_share = I('get.show_share', 0,'strval');
        if($id < 1){
            $this->error('请选择查看的红包照片', U('/zhao/notes'));
        }
        $this->zhaopian = M('zhaopian')->where(array('number_no'=>$id))->find();
        if($this->zhaopian['state'] == 99){
            $this->error('该红包照片已删除', U('/zhao/notes'));
        }
        if(!$this->zhaopian){
            $this->error('没找到红包照片详情', U('/zhao/notes'));
        }
        //
        $path = C('UPLOAD_PATH') .$this->zhaopian['pic_url'];
        if(file_exists($path."_thumb2.jpg")){
            $this->gaosi_img = "/uploads/".$this->zhaopian['pic_url'].'_thumb2.jpg';
        }else{
            $this->gaosi_img = '/img/default.jpg';
        }

        $this->zhaopian_user = M('user')->find($this->zhaopian['user_id']);
        $this->user = M('user')->find($this->user_id);

        $this->title = "{$this->zhaopian_user['name']}发布的红包照片";
        $zhaopian_order = M('zhaopian_order')->where(array('zhaopian_id'=>$this->zhaopian['id'], 'user_id'=>$this->user_id,'state'=>2))->find();
        $this->zhaopian_order = $zhaopian_order;

        $total_amount = M('zhaopian_order')->where(array('zhaopian_id'=>$this->zhaopian['id'], 'state'=>2))->sum('amount');
        $this->total_amount = floatval($total_amount);
        $this->total_num = M('zhaopian_order')->where(array('zhaopian_id'=>$this->zhaopian['id'], 'state'=>2))->count();
        $this->is_buy = $zhaopian_order ? 1:0;
        $this->jsApiParameters = '1';
        //$this->is_buy=true;
        if(!$this->is_buy ){
            $img_path = ROOT_PATH .'uploads/'. $this->zhaopian['pic_url'];
            $this->width = 750;
            $this->height = 1334;
            if(file_exists($img_path) && is_file($img_path)){
                // 获取图片宽高
                $img = new \Think\Image(2);
                $img->open($img_path);
                $this->width = $img->width();
                $this->height = $img->height();
            }

            $order = M('zhaopian_order')->where(array('zhaopian_id'=>$this->zhaopian['id'],'user_id'=>$this->user_id, 'state'=>1))->find();
            if(!$order){
                $user = M('user')->find($this->user_id);
                if($this->zhaopian['is_rand']>0){
                    $amount = $this->getRandomAmount();
                }else{
                    $amount = $this->zhaopian['min_amount'];
                }

                $data = array(
                    'zhaopian_id' => $this->zhaopian['id'],
                    'zhaopian_user_id' => $this->zhaopian['user_id'],
                    'zhaopian_openid' => $this->zhaopian['openid'],
                    'number_no' =>$this->zhaopian['number_no'],
                    'order_sn' =>get_order_sn('zo'),
                    'user_id' => $this->user_id,
                    'amount' => $amount,
                    'addtime' => time(),
                    'state' => 1,
                    'openid' => $user['openid']
                );
                $rs = M('zhaopian_order')->add($data);
                if($rs){
                    $order = M('zhaopian_order')->find($rs);
                }
            }else{
                if($this->zhaopian['is_rand']>0){
                    $amount = $this->getRandomAmount();
                    $order['amount'] = $amount;
                    $order['order_sn'] = get_order_sn();
                    M('zhaopian_order')->where(array('id'=>$order['id']))->save(array('amount'=>$amount, 'order_sn'=>$order['order_sn']));
                }
            }

            if($order['state'] == 1){
                $data['body'] = "红包照片";
                $data['attach'] = "红包照片";
                $data['order_sn'] = $order['order_sn'];
                $data['total_fee'] = ceil(bcmul($order['amount'], 100));
                $data['time_start'] = date('YmdHis');
                $data['time_expire'] =  date("YmdHis", time() + 600);
                $data['goods_tag'] = "WXG";
                // $openid = ;//session('openid')?session('openid'):cookie('openid');
                $data['openid'] = cookie('openid')?cookie('openid'):$order['openid'];

                $jsApiParameters = jsapipay($data, false);
                // print_r($jsApiParameters);
                $this->jsApiParameters = $jsApiParameters;
            }
            $this->order = $order;
        }
        $this->pic_list = array();
        $this->img_left = ceil((20 * $this->zhaopian['total_pic'] )/2);
        if($this->is_buy ){
            if($this->zhaopian['total_pic'] > 1){

                $this->pic_list = M('zhaopian_pic')->where(array('zhaopian_id'=>$this->zhaopian['id']))->select();
            }
        }

        if($this->hongbao['user_id'] == $this->user_id){
            /*
             1，分享自己发的
标题：我发布了3张照片，想看吗？
内容：“用户输入的内容”
2，分享别人发的
李陆鸣发布了3张照片，想看吗？
3，买了别人的照片
我买了李陆鸣的照片，推荐！

            分享朋友圈
1，分享自己发的
标题：我发布了3张照片，想看吗？“据说看了能提升幸福感~”
2，分享别人的
标题：李陆鸣发布了3张照片，想看吗？“据说看了能提升幸福感~”
3，买了别人的照片
标题：我买了李陆鸣发布的照片，推荐！“据说看了能提升幸福感~”
            */
            $this->share_title_friend = "我发布了{$this->zhaopian['total_pic']}张照片，想看吗？“{$this->zhaopian['remark']}”";
            $this->share_title = "我发布了{$this->zhaopian['total_pic']}张照片，想看吗？";
            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/silogocover.jpg";
            $this->share_desc = "“{$this->zhaopian['remark']}”";
        }else{
            if($this->is_buy ){
                $this->share_title_friend = "我买了{$this->zhaopian_user['name']}发布的照片，推荐！“{$this->zhaopian['remark']}”";
                $this->share_title = "我买了{$this->zhaopian_user['name']}的照片，推荐！";
            }else{
                $this->share_title_friend = "{$this->zhaopian_user['name']}发布了{$this->zhaopian['total_pic']}张照片，想看吗？“{$this->zhaopian['remark']}”";
                $this->share_title = "{$this->zhaopian_user['name']}发布了{$this->zhaopian['total_pic']}张照片，想看吗？";
            }
            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/silogocover.jpg";
            $this->share_desc = "“{$this->zhaopian['remark']}”";
        }

        $order_list = M('zhaopian_order')->where(array(array('number_no'=>$id, 'state'=>array('in', array(2)))))->order("addtime desc")->select();
        if($order_list){
            foreach($order_list as $k=>$order){
                $user = M('user')->find($order['user_id']);
                $order_list[$k]['user'] = $user;
            }
        }
        $this->share_link = U('/zhao/zhaopian/detail', array('id'=>$id), true,true);
        $this->order_list = $order_list;
        $this->base_url = "http://$_SERVER[HTTP_HOST]";
        $this->id = $id;
        $this->display();
    }

    /**
     *
     */
    public function detail_list(){
        $id = I('post.id','','strval');
        $page = I('post.page',1,'intval');
        $page = $page < 1?1:$page;
        $zhaopian = M('zhaopian')->where(array('number_no'=>$id))->find();
        if(!$zhaopian){
            echo '<div style="text-align: center; width: 100%; padding: 20px;">暂无购买记录.</div>';
            die();
        }
        $total_num = M('zhaopian_order')->where(array('zhaopian_id'=>$zhaopian['id'], 'state'=>2))->count();

        $this->page = $page;
        $this->all_page = ceil($total_num/10);

        $order_list = M('zhaopian_order')->where(array('zhaopian_id'=>$zhaopian['id'], 'state'=>2))->limit(($page-1)*10,10)->select();
        if($order_list){
            foreach($order_list as $i=>$order){
                $user = M('user')->find($order['user_id']);
                $order_list[$i]['user'] = $user;
            }
        }
        //default.jpg

        $this->list = $order_list;
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
            $zhaopian = M('zhaopian')->where(array('number_no'=>$id))->find();
            if(!$zhaopian){
                // $this->error('没找到红包详情', U('/notes'));
                $json['error'] = 1;
                $json['message'] = '没找到红包照片详情';
                break;
            }

            $amount = I('post.amount', 0, 'floatval');
            if($amount > $zhaopian['max_amount'] || $amount< $zhaopian['min_amount']){
                $json['error'] = 1;
                $json['message'] = '红包金额不在有效范围内.有效范围'.$zhaopian['min_amount'].'-'.$zhaopian['max_amount'].'之间.';
                break;
            }

            $zhaopian_order = M('zhaopian_order')->where(array('zhaopian_id'=>$zhaopian['id'], 'user_id'=>$this->user_id,'state'=>2))->find();
            if($zhaopian_order){
                $json['error'] = 1;
                $json['message'] = '你已经购买过该照片,请刷新后访问该照片.';
                break;
            }
            $user = M('user')->find($this->user_id);
            $data = array(
                'zhaopian_id' => $zhaopian['id'],
                'zhaopian_user_id' => $zhaopian['user_id'],
                'zhaopian_openid' => $zhaopian['openid'],
                'number_no' =>$zhaopian['number_no'],
                'order_sn' =>get_order_sn('zo'),
                'user_id' => $this->user_id,
                'amount' => $amount,
                'addtime' => time(),
                'state' => 1,
                'openid' => $user['openid']
            );
            $rs = M('zhaopian_order')->add($data);

            if($rs){
                $json['data'] = $data['order_sn'];
                break;
            }else{
                $json['error'] = 1;
                $json['message'] = '操作失败，请重试.';
                break;
            }
        }while(false);
        echo json_encode($json);
    }
}