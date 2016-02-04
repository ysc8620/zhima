<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Zhao\Controller;
class ZhaopianController extends BaseController {
    public function index(){
        $this->sign = md5(microtime(true));
        session('sign', $this->sign);
        $this->title = '发布红包照片';
        $this->display();
    }

    public function add(){
        do{
            $sign = I('post.sign');

            if($sign != session('sign')){
                $this->error('请不要重复提交.',U('/zhaopian'));
            }else{
                session('sign', microtime(true));
            }
            $is_rand = I('post.is_rand',0,'intval');
            $amount = I('post.amount',1.05,'floatval');
            $remark = I('post.remark','','htmlspecialchars');
            if(!$is_rand){
                if($amount < 1.05 || $amount > 200){
                    $this->error('价格在1.05-200之间.',U('/zhaopian'));
                    return false;
                }
            }

            if(empty($_FILES['imgOne'])){
                $this->error('请选择要发布的红包照片.',U('/zhaopian'));
                return false;
            }

            if($_FILES['imgOne']){
                $rootPath=C('UPLOAD_PATH');
                $config = array(
                    //'maxSize'    =    3145728,
                    'rootPath'   =>    $rootPath,
                    'savePath'   =>    $this->user_id . '/',
                    'saveName'   =>    'zp_'.time().rand(111,999),
                    'exts'       =>   explode(',',C('UPLOAD_TYPE')),
                    'autoSub'    =>    true,
                    'subName'    =>    array('date','Ymd'),
                );

                $type 	= 'Local';
                $upload = new \Think\Upload($config,$type);// 实例化上传类
                $info   =   $upload->upload();

                if(!$info) {
                    $this->error($upload->getError(),'') ;
                }else{// 上传成功
                    $data['pic_url'] = $info['imgOne']['savepath'].$info['imgOne']['savename'];
                }
            }

            if(empty($data['pic_url'])){
                $this->error('请选择要发布的红包照片.',U('/zhaopian'));
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

            $data['addtime'] = time();
            $data['state'] = 1;
            $data['openid'] = $user['openid'];
            $re = M('zhaopian')->add($data);
            if($re){
                redirect(U('/zhaopian/detail', array('id'=>$data['number_no'])));
                exit();
            }else{
                $this->error('红包照片创建失败', U('/zhaopian'));
            }
        }while(false);
        $this->error('红包照片创建失败', U('/zhaopian'));
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
        $this->title ="红包照片详情";

        $id = I('get.id',0, 'strval');

        $this->show_share = I('get.show_share', 0,'strval');
        if($id < 1){
            $this->error('请选择查看的红包照片', U('/notes'));
        }
        $this->zhaopian = M('zhaopian')->where(array('number_no'=>$id))->find();

        if(!$this->zhaopian){
            $this->error('没找到红包照片详情', U('/notes'));
        }

        $this->zhaopian_user = M('user')->find($this->zhaopian['user_id']);
        $this->user = M('user')->find($this->user_id);


        $this->title = "{$this->hongbao_user['name']}发起的红包照片";

        if($this->hongbao['user_id'] == $this->user_id){
            $this->share_title = "我发起的红包照片";
            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/logo.jpg";
            $this->share_desc = "“{$this->zhaopian['remark']}”";
        }else{
            $is_buy = M('zhaopian_order')->where(array("zhaopian_id"=>$this->zhaopian['id'], "state"=>2,'user_id'=>$this->user_id))->count();
            $this->is_buy = $is_buy > 0 ? true:false;

            if($is_buy < 1 ){
                $this->share_title = "{$this->zhaopian_user['name']}发起的红包照片";
            }else{
                $this->share_title = "我购买了{$this->zhaopian_user['name']}的红包照片";
            }
            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/logo.jpg";
            $this->share_desc = "“{$this->zhaopian['remark']}”";
        }



        $order_list = M('zhaopian_order')->where(array(array('number_no'=>$id, 'state'=>array('in', array(2)))))->order("addtime desc")->select();
        if($order_list){
            foreach($order_list as $k=>$order){
                $user = M('user')->find($order['user_id']);
                $order_list[$k]['user'] = $user;
            }
        }
        $this->share_link = U('/zhaopian/detail', array('id'=>$id), true,true);
        $this->order_list = $order_list;
        $this->id = $id;
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
            $rs = M('hongbao_order')->add($data);

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