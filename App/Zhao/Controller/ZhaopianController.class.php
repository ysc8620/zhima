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
            if(!$is_rand){
                if($amount < 1.05 || $amount > 200){
                    $this->error('价格在1.05-200之间.',U('/zhao/zhaopian'));
                    return false;
                }
            }

            if(empty($_FILES['imgOne'])){
                $this->error('请选择要发布的红包照片.',U('/zhao/zhaopian'));
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
                }else{
                    // 上传成功
                    $data['pic_url'] = $info['imgOne']['savepath'].$info['imgOne']['savename'];

                    $img = new \Think\Image(\Think\Image::IMAGE_IMAGICK);
                    $img->open($rootPath . $data['pic_url']);
                    $width = $img->width();
                    $height = $img->height();
                    $x = $y = 0;
                    if($width > $height){
                        $x = floor(($width - $height)/2);
                        $width = $height;
                    }elseif($height> $width){
                        $y = floor(($height - $width)/2);
                        $height = $width;
                    }
                    $img->crop($width, $height,$x,$y, 300, 300)->save($rootPath . $data['pic_url'] . '_thumb.jpg');
//                    $img->thumb(500, 1000)->save($rootPath . $data['pic_url'] . '_thumb1.jpg');
//
//
//                    $img2 = new \Think\Image(\Think\Image::IMAGE_IMAGICK);
//
//                    $img2->open($rootPath . $data['pic_url'] . '_thumb1.jpg')->gaussianBlurImage(40,30)->save($rootPath . $data['pic_url'] . '_thumb2.jpg');

                }
            }

            if(empty($data['pic_url'])){
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

            $data['addtime'] = time();
            $data['state'] = 1;
            $data['openid'] = $user['openid'];
            $re = M('zhaopian')->add($data);
            if($re){
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

        if(!$this->zhaopian){
            $this->error('没找到红包照片详情', U('/zhao/notes'));
        }
        //
        $path = C('UPLOAD_PATH') .$this->zhaopian['pic_url'];

//        if(file_exists($path) && !file_exists($path."_thumb2.jpg")){
//            $img = new \Think\Image(\Think\Image::IMAGE_IMAGICK);
//            $img->open($path);
//            $img->thumb(500, 1000)->save($path . '_thumb1.jpg');
//
//
//            $img2 = new \Think\Image(\Think\Image::IMAGE_IMAGICK);
//
//            $img2->open($path . '_thumb1.jpg')->gaussianBlurImage(40,30)->save($path . '_thumb2.jpg');
//
//        }


        $this->zhaopian_user = M('user')->find($this->zhaopian['user_id']);
        $this->user = M('user')->find($this->user_id);

        $this->title = "{$this->hongbao_user['name']}发起的红包照片";
        $zhaopian_order = M('zhaopian_order')->where(array('zhaopian_id'=>$this->zhaopian['id'], 'user_id'=>$this->user_id,'state'=>2))->find();
        $this->zhaopian_order = $zhaopian_order;

        $this->total_amount = M('zhaopian_order')->where(array('zhaopian_id'=>$this->zhaopian['id'], 'state'=>2))->sum('amount');
        $this->total_num = M('zhaopian_order')->where(array('zhaopian_id'=>$this->zhaopian['id'], 'state'=>2))->count();
        $this->is_buy = $zhaopian_order ? true:false;
//        $this->is_buy=true;
        if(!$this->is_buy){
            $order = M('zhaopian_order')->where(array('zhaopian_id'=>$this->zhaopian['id'],'user_id'=>$this->user_id, 'state'=>1))->find();
            if(!$order){
                $user = M('user')->find($this->user_id);
                if($this->zhaopian['is_rand']>0){
                    $amount = number_format($this->randomFloat(1.05,5),2);
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
            }
            $this->order = $order;
        }


        if($this->hongbao['user_id'] == $this->user_id){
            $this->share_title = "我发布了1张私照，想看吗？";
            $this->share_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->share_imgUrl = "http://$_SERVER[HTTP_HOST]/images/silogocover.jpg";
            $this->share_desc = "“{$this->zhaopian['remark']}”";
        }else{
            if($this->is_buy ){
                $this->share_title = "我买了{$this->zhaopian_user['name']}的私照，推荐！";
            }else{
                $this->share_title = "{$this->zhaopian_user['name']}发布了1张私照，想看吗？";
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