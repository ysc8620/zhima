<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  预感 <442648313@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
//新闻管理
class HongbaoController extends CommonController {

    //新闻列表
    public function index(){
        $status = I('request.status',0,'intval');
		$count    = M('hongbao')->count();
        $where = "state > 0";
        if($status){
            $where .= " AND state ='{$status}'";
        }
        $counts    = M('hongbao')->where($where)->count();
        $page     = new \Think\Page($counts,20);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $show     = $page->show();
		$limit    = $page->firstRow.','.$page->listRows;

        $hongbao_list = M('hongbao')->where($where)->limit($page->firstRow.','.$page->listRows)->order("id DESC")->select();
		$this->assign('page',$show);
        foreach($hongbao_list as $i=>$hongbao){
            $hongbao_list[$i]['user'] = M('user')->find($hongbao['user_id']);
        }
        $this->hongbao_count = $count;
        $this->assign('hongbao_list',$hongbao_list);
        $this->display();
    }

    /**
     * 订单列表
     */
    public function order_list(){
        $status = I('request.status',0,'intval');
        $count    = M('hongbao')->count();
        $where = "state > 0";
        if($status){
            $where .= " AND state ='{$status}'";
        }
        $counts    = M('hongbao')->where($where)->count();
        $page     = new \Think\Page($counts,20);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $show     = $page->show();
        $limit    = $page->firstRow.','.$page->listRows;

        $hongbao_list = M('hongbao')->where($where)->limit($page->firstRow.','.$page->listRows)->order("id DESC")->select();
        $this->assign('page',$show);
        foreach($hongbao_list as $i=>$hongbao){
            $hongbao_list[$i]['user'] = M('user')->find($hongbao['user_id']);
        }
        $this->hongbao_count = $count;
        $this->assign('hongbao_list',$hongbao_list);
        $this->display();
    }

    /**
     * 红包详情
     */
    public function detail(){
        $id = I('request.id', 0, 'intval');
        $hongbao = M('hongbao')->find($id);
        $order_list = M('hongbao_order')->where(array('hongbao_id'=>$id, 'state'=>array('gt', 1)))->order("is_star DESC, id DESC")->select();
        $hongbao['user'] = M('user')->find($hongbao['user_id']);
        foreach($order_list as $i=>$order){
            $order_list[$i]['user'] = M('user')->find($order['user_id']);
        }

        $this->assign('hongbao', $hongbao);
        $this->assign('order_list', $order_list);
        $this->display();
    }

}