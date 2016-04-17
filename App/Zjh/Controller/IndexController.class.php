<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Zjh\Controller;
use Think\Controller;
class IndexController extends BaseController {
    public function index(){
        $this->title = "个人中心";
        $page = I('request.p',1);
        $page = $page<1?1:$page;
        $limit = 20;

        $this->state = I('request.state', '');

        if($this->state != '1'){
            $list = M('zhajinhua')->table('zml_zhajinhua z')->join("zml_zhajinhua_user o on o.zha_id = z.id and o.user_id='{$this->user_id}'")->where("z.status in(1,2,3)")->field('z.*')->order('z.addtime desc')->page($page,$limit)->select();

            $total = M('zhajinhua')->table('zml_zhajinhua z')->join("zml_zhajinhua_user o on o.zha_id = z.id and o.user_id='{$this->user_id}'")->where("z.status in(1,2,3)")->count();

        }else{
            $time = time()-1200;
            $list = M('zhajinhua')->where("status=0 AND addtime > '{$time}'")->order('addtime desc')->page($page,$limit)->select();
            //$total = M('zhaopian')->where("id in(SELECT zhaopian_id FROM zml_zhaopian_order where user_id='{$this->user_id}' and state = 2) AND state=1")->count();
            $total = M('zhajinhua')->where("status=0 AND addtime > '{$time}'")->count();

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



        $this->user = M('user')->find($this->user_id);
    	//首页幻灯片获取
    	$this->display();
		//session('user',null);
    }
}