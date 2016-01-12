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

        $this->title = '新建红包';
        $this->display();
    }

    public function add(){

        do{
            $amount = I('post.amount',0,'intval');
            $total = I('post.total',0,'intval');
            $remark = I('post.remark','','htmlspecialchars');

           // if($amount < 1 || $total < 1 || $total > 200 || $amount > 200){
                $this->error('红包金额范围在1-200元之间.',U('/hongbao'));
                return false;
           // }
        }while(false);


        M('hongbao')->add($data);
    }

    /**
     * 红包详情
     */
    public function detail(){
        $this->title ="凑红包详情";
        $id = I('get.id');

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