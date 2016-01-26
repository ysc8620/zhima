<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  预感 <442648313@qq.com>
// +----------------------------------------------------------------------
namespace Home\Controller;
use Think\Controller;
class VerifyController extends Controller {
    public function index(){
		ob_clean();
        $Verify 			= new \Think\Verify();
        $Verify->useCurve   = false;
        $Verify->fontSize 	= 36;
        $Verify->length   	= 4;
        $Verify->useNoise 	= true;
        $Verify->useImgBg 	= false;
        $Verify->entry();
    }
}