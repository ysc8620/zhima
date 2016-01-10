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