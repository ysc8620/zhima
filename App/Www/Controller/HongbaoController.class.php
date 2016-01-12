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
                $this->error('红包金额范围在1-200元之间.',U('/hongbao'));
                return false;
            }

            if($amount * $total > 200 || $amount * $total <1){
                $this->error('红包金额范围在1-200元之间.',U('/hongbao'));
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

        $id = I('get.id',0, 'intval');
        if($id < 1){
            $this->error('请选择查看的红包', U('/notes'));
        }
        $this->hongbao = M('hongbao')->find(array('number_no'=>$id));
        if(!$this->hongbao){
            $this->error('没找到红包详情', U('/notes'));
        }
        $this->user = M('user')->find($this->hongbao['user_id']);
        $order_list = M('hongbao_order')->where(array('number_no'=>$id,'state'=>2))->select();
        if($order_list){
            foreach($order_list as $k=>$order){
                $order_list[$k]['user'] = M('user')->find($order['hongbao_user_id']);
            }
        }
        $this->id = $id;
        $this->display();
    }

    /**
     * 红包认购
     */
    public function buy(){
        $this->title ="追加凑红包";
        $this->display();
    }

}