<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  预感 <442648313@qq.com>
// +----------------------------------------------------------------------
namespace User\Controller;
use Think\Controller;
class PublicController extends Controller {
     	public function _initialize(){
             $this->user_id = session('user_id');

             #$this->user_id = 2;
            if(empty($this->user_id)){
                \Wechat\Wxapi::authorize();
                exit();
            }
             $this->user = M('user')->find($this->user_id);
            $this->title = $this->user['name']."个人中心";
             /*
			if(!session('user.uin')){
				header("location: ".U('login/index'));
			}
			$this->user = M('user u')
						->join('LEFT JOIN __REGION__ p ON  p.id=u.province')
						->join('LEFT JOIN __REGION__ c ON  c.id=u.city')
						->join('LEFT JOIN __REGION__ a ON  a.id=u.area')
						->join('LEFT JOIN __USER_ATTEST__ r ON r.uin=u.uin')
						->field('r.status as rstatus,u.uin,u.name,u.phone,u.create_time,u.money,u.points,u.age,u.sex,u.province,u.city,u.area,u.address,u.header,c.name as cityname,p.name as provname,a.name as areaname')
						->where(array('u.uin'=>session('user.uin')))->find();
			$this->phone=session('user.phone');*/
		}
}