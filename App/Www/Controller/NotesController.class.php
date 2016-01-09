<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Www\Controller;
use Think\Controller;
class NotesController extends Controller {
    public function index(){
        //define your token

        $Page              = new \Think\Page(105,10); // 实例化分页类 传入总记录数和每页显示的记录数(20)
        $Page->rollPage = 5;
        $Page->setConfig('prev','上一页');
        $Page->setConfig('next','下一页');
        $Page->setConfig('theme','%UP_PAGE% %DOWN_PAGE% <li ><a>共 %TOTAL_ROW% 条记录</a></li>');
        $show  = $Page->show();
        $this->page = $show;
        $this->display();
    }

    public function join(){
        $Page              = new \Think\Page(105,10); // 实例化分页类 传入总记录数和每页显示的记录数(20)
        $Page->rollPage = 5;
        $Page->setConfig('prev','上一页');
        $Page->setConfig('next','下一页');
        $Page->setConfig('theme','%UP_PAGE% %DOWN_PAGE% <li ><a>共 %TOTAL_ROW% 条记录</a></li>');
        $show  = $Page->show();
        $this->page = $show;
        $this->display();
    }

}