<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Www\Controller;
class HongbaoController extends BaseController {
    public function index(){

        //define your token
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
            $amount = I('post.amount',0,'intval');
            $total = I('post.total',0,'intval');
            $remark = I('post.remark','','htmlspecialchars');

            if($amount < 1 || $total < 1 || $total > 200 || $amount > 200){
                $this->error('红包范围在1-200之间.',U('/hongbao'));
                return false;
            }

            if($amount * $total > 200 || $amount * $total <1){
                $this->error('红包范围在1-200之间.',U('/hongbao'));
                return false;
            }
            // `id`, `number_no`, `user_id`, `part_amount`, `total_amount`, `total_part`, `remark`, `addtime`, `update_time`, `state`
            $data['number_no'] = get_order_sn();
            $data['user_id'] = $this->user_id;
            $data['part_amount'] = $amount;
            $data['total_amount'] = $total * $amount;
            $data['total_part'] = $total;
            $data['remark'] = $remark;
            $data['addtime'] = time();
            $data['state'] = 1;

            $re = M('hongbao')->add($data);
            if($re){
                $this->success('红包创建成功', U('/notes'));
                exit();
            }else{
                $this->error('红包创建失败', U('/hongbao'));
            }
        }while(false);
        $this->error('红包创建失败', U('/hongbao'));
    }

    /**
     * 红包详情
     */
    public function detail(){
        $this->title ="凑红包详情";

        $id = I('get.id',0, 'strval');
        if($id < 1){
            $this->error('请选择查看的红包', U('/notes'));
        }
        $this->hongbao = M('hongbao')->where(array('number_no'=>$id))->find();

        if(!$this->hongbao){
            $this->error('没找到红包详情', U('/notes'));
        }
        $this->user = M('user')->find($this->hongbao['user_id']);
        $order_list = M('hongbao_order')->where(array(array('number_no'=>$id,'state'=>array('in',array(1,2)))))->select();
        if($order_list){
            foreach($order_list as $k=>$order){
                $order_list[$k]['user'] = M('user')->find($order['hongbao_user_id']);
            }
        }

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

        $total_amount = intval(M('hongbao_order')->where(array("number_no"=>$id, "state"=>1,'addtime'=>array('gt', time()-1800)))->sum('total_amount'));

        if(!$this->hongbao){
            $this->error('没找到红包详情', U('/notes'));
        }
        $this->user = M('user')->find($this->hongbao['user_id']);
        $this->display();
    }

    public function order(){
        $sign = I('post.sign');
        $id = I('post.id','','strval');


        $hongbao = M('hongbao')->where(array('number_no'=>$id))->find();
        if(!$hongbao){
            $this->error('没找到红包详情', U('/notes'));
        }

        if($sign != session('sign')){
            $this->error('请不要重复提交.',U('/hongbao/buy',array('id'=>$id)));
        }else{
            session('sign', microtime(true));
        }

        $total = I('post.num', 0, 'intval');
        $total_amount = intval(M('hongbao_order')->where(array("number_no"=>$id, "state"=>1,'addtime'=>array('gt', time()-1800)))->sum('total_amount'));

        if($total < 1 || ($total_amount + $total + $hongbao['total_num']) > $hongbao['total_part']){
            $this->error('你已超过红包份额限制,请重新设置份额.',U('/hongbao/buy',array('id'=>$id)));
            return false;
        }
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
        );
        $rs = M('hongbao_order')->add($data);
        if($rs){
            redirect(U('/weixin/pay', array('id'=>$data['order_sn'])));
            // $this->success('操作成功.',U('/hongbao/detail',array('id'=>$id)));
            return true;
        }else{
            $this->error('操作失败，请重试.',U('/hongbao/buy',array('id'=>$id)));
            return false;
        }
    }

    public function info(){

    }

}