<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Www\Controller;
class NotesController extends BaseController {
    public function index(){
        $this->user = M('user')->find($this->user_id);
        $this->title = '我的记录';
        $page = I('request.p',1);
        $page = $page<1?1:$page;
        $this->state = I('request.state', '');
        if($this->state == 'creation'){
            $list = M('hongbao')->where(array('user_id'=>$this->user_id))->page($page,2)->order("addtime DESC")->select();
            $total = M('hongbao')->where(array('user_id'=>$this->user_id))->count();
        }elseif($this->state == 'star'){
            $list = M('hongbao')->where("id in(SELECT hongbao_id FROM zml_hongbao_order WHERE user_id='{$this->user_id}' AND is_star=1)")->page($page,10)->order("addtime DESC")->select();
            $total = M('hongbao')->where("id in(SELECT hongbao_id FROM zml_hongbao_order WHERE user_id='{$this->user_id}' AND is_star=1)")->count();
        }else{
            $list = M('hongbao')->where("id in(SELECT hongbao_id FROM zml_hongbao_order WHERE user_id='{$this->user_id}') or user_id='{$this->user_id}'")->page($page,10)->order("addtime DESC")->select();
            $total = M('hongbao')->where("id in(SELECT hongbao_id FROM zml_hongbao_order WHERE user_id='{$this->user_id}') or user_id='{$this->user_id}'")->count();

        }

        // $list = M('hongbao_order')->where(array('user_id'=>$this->user_id))->page($page,10)->order("id DESC")->select();
        if($list){
            foreach($list as $i=>$item){
                #$list[$i]['hongbao'] = M('hongbao')->find($item['hongbao_id']);
                $list[$i]['user'] = M('user')->find($item['user_id']);
            }
        }

        $this->list = $list;


        $Page              = new \Think\Page($total,2); // 实例化分页类 传入总记录数和每页显示的记录数(20)
        $Page->rollPage = 5;
        $Page->setConfig('prev','上一页');
        $Page->setConfig('next','下一页');
        $Page->setConfig('theme','<span style="font-size:12px; padding-bottom:4px;">第'.$page.'/%TOTAL_PAGE%页,共%TOTAL_ROW%条</span> <br/> %UP_PAGE% %DOWN_PAGE%');
        $show  = $Page->show();
        $this->page = $show;
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