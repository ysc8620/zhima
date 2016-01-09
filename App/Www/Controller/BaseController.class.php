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
class BaseController extends Controller {
    protected function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
        $this->assign('time', time());
        $this->view->display($templateFile,$charset,$contentType,$content,$prefix);
    }

}