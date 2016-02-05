<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Zhao\Controller;
class NotesController extends BaseController {
    public function index(){
        $this->user = M('user')->find($this->user_id);

        $page = I('request.p',1);
        $page = $page<1?1:$page;
        $this->state = I('request.state', '');
        $limit = 10;
        if($this->state == 'creation'){
            $this->title = '我发布的';
            $list = M('zhaopian')->where("user_id='{$this->user_id}'")->page($page,$limit)->order("addtime DESC")->select();
            $total = M('zhaopian')->where("user_id='{$this->user_id}'")->count();
        }else{
            $this->title = '我购买的';
            $list = M('zhaopian')->where("id in(SELECT zhaopian_id FROM zml_zhaopian_order where user_id='{$this->user_id}' and state = 2)")->page($page,$limit)->order("addtime DESC")->select();
            $total = M('zhaopian')->where("id in(SELECT zhaopian_id FROM zml_zhaopian_order where user_id='{$this->user_id}' and state = 2)")->count();
        }

        // $list = M('hongbao_order')->where(array('user_id'=>$this->user_id))->page($page,10)->order("id DESC")->select();
        if($list){
            foreach($list as $i=>$item){
                #$list[$i]['hongbao'] = M('hongbao')->find($item['hongbao_id']);
                $list[$i]['user'] = M('user')->find($item['user_id']);
            }
        }

        $this->list = $list;
        $this->page = $page;

        $Page              = new \Think\Page($total,$limit); // 实例化分页类 传入总记录数和每页显示的记录数(20)
        $Page->rollPage = 5;
        $Page->setConfig('prev','上一页');
        $Page->setConfig('next','下一页');
        $Page->setConfig('theme','%UP_PAGE% %DOWN_PAGE%');
        $show  = $Page->show();
        $this->totalRows = $Page->totalRows;
        $this->total_page = $Page->totalPages;
        $this->page_show = $show;
        $this->display();
    }

    public function join(){
        $this->title = '我参与的';

        $this->user = M('user')->find($this->user_id);
        $page = I('request.p',1);
        $page = $page<1?1:$page;

        $list = M('hongbao_order')->where(array('user_id'=>$this->user_id))->page($page,10)->order("id DESC")->select();
        if($list){
            foreach($list as $i=>$item){
                $list[$i]['hongbao'] = M('hongbao')->find($item['hongbao_id']);
                $list[$i]['user'] = M('user')->find($list[$i]['hongbao']['user_id']);
            }
        }
        $total = M('hongbao_order')->where(array('user_id'=>$this->user_id))->count();
        $this->list = $list;
        $Page              = new \Think\Page($total,10); // 实例化分页类 传入总记录数和每页显示的记录数(20)
        $Page->rollPage = 5;
        $Page->setConfig('prev','上一页');
        $Page->setConfig('next','下一页');
        $Page->setConfig('theme','%UP_PAGE% %DOWN_PAGE% <li ><a>共 %TOTAL_ROW% 条记录</a></li>');
        $show  = $Page->show();
        $this->page = $show;
        $this->display();
    }


}