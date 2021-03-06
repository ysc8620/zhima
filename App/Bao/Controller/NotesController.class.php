<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Bao\Controller;
class NotesController extends BaseController {
    public function index(){
        $this->user = M('user')->find($this->user_id);
        $this->title = '我的记录';
        $page = I('request.p',1);
        $page = $page<1?1:$page;
        $this->state = I('request.state', '');
        $limit = 10;
        if($this->state == 'creation'){
            $list = M('bao')->where(array('user_id'=>$this->user_id,'from_bao_id'=>0, 'state'=>array('in','2,3,4')))->page($page,$limit)->order("addtime DESC")->select();
            $total = M('bao')->where(array('user_id'=>$this->user_id,'from_bao_id'=>0,'state'=>array('in','2,3,4')))->count();
        }elseif($this->state == 'receive'){
            $list = M('bao')->where("number_no in(SELECT from_number_no FROM zml_bao_order WHERE user_id='{$this->user_id}') and state in(2,3,4)")->page($page,$limit)->order("addtime DESC")->select();
            $total = M('bao')->where("number_no in(SELECT from_number_no FROM zml_bao_order WHERE user_id='{$this->user_id}') and state in(2,3,4)")->count();
        }elseif($this->state == 'sponsor'){
            $list = M('bao')->where(array('user_id'=>$this->user_id,'from_bao_id'=>array('gt',0), 'state'=>array('in','2,3,4')))->page($page,$limit)->order("addtime DESC")->select();
            $total = M('bao')->where(array('user_id'=>$this->user_id,'from_bao_id'=>array('gt',0) ,'state'=>array('in','2,3,4')))->count();
        }else{
            $list = M('bao')->where("number_no in(SELECT from_number_no FROM zml_bao_order WHERE user_id='{$this->user_id}') or (user_id='{$this->user_id}' and state in(2,3,4))")->page($page,$limit)->order("addtime DESC")->select();
            $total = M('bao')->where("number_no in(SELECT from_number_no FROM zml_bao_order WHERE user_id='{$this->user_id}') or (user_id='{$this->user_id}' and state in(2,3,4))")->count();

        }

        // $list = M('hongbao_order')->where(array('user_id'=>$this->user_id))->page($page,10)->order("id DESC")->select();
        if($list){
            foreach($list as $i=>$item){
                #$list[$i]['hongbao'] = M('hongbao')->find($item['hongbao_id']);
                $list[$i]['user'] = M('user')->find($item['from_user_id']?$item['from_user_id']:$item['user_id']);
                if($this->state == 'receive'){
                    $list[$i]['order'] = M('bao_order')->where(array('from_number_no'=>$item['from_number_no'],'user_id'=>$this->user_id))->find();
                }
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



}