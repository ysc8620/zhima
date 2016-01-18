<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  预感 <442648313@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
class SystemController extends CommonController {
    public function index(){
		if(IS_POST){
			$post = I('post.');			
			if(F('web_config',$post,CONF_PATH)){
				$this->success('配置成功');
			}else{
				$this->error('配置失败,检查App/Common/Conf 目录是否可写');
			}
		}else{
			$this->display();
		}
    	
    }
	//文件上传配置
	public function uploadset(){
		if(IS_POST){
			$post = I('post.');
			//print_r($post);exit;
			if(F('upload',$post,CONF_PATH)){
				$this->success('配置成功');
			}else{
				$this->error('配置失败');
			}
		}else{
			$this->display();
		}
	}

    //支付接口列表
    public function weixin(){
        if(IS_POST){
            $post = I('post.');
            if(F('weixin',$post,CONF_PATH)){
                $this->success('配置成功');
            }else{
                $this->error('配置失败');
            }
        }else{
            $this->display();
        }
    }

	//支付接口列表
	public function payment(){
		if(IS_POST){
		}else{
			$payment_data 				= C('payment');
	    	$payment_data_array_keys 	= array_keys($payment_data);
	    	foreach ($payment_data_array_keys as $key => $value) {
	    		if ($payment_data[$value]['status']==1 or $payment_data[$value]['status']==0) {
	    			$payment_lists[$key]['title'] 		= $payment_data[$value]['title'];
	    			$payment_lists[$key]['type'] 		= $value;
	    			$payment_lists[$key]['ico'] 		= $payment_data[$value]['ico'];
	    			$payment_lists[$key]['partner'] 	= $payment_data[$value]['partner'];
	    			$payment_lists[$key]['business'] 	= $payment_data[$value]['business'];
	    			$payment_lists[$key]['sort'] 		= $payment_data[$value]['sort'];
	    			$payment_lists[$key]['status'] 		= $payment_data[$value]['status'];
	    		}
	    	}
	    	foreach ($payment_lists as $key => $value) {
	    		$sort[$key] = $value['sort'];
	    	}
	    	array_multisort($sort, SORT_DESC, $payment_lists);
	    	$this->assign('lists',$payment_lists);
			$this->display();
		}
	}

	//支付接口配置
	public function payment_set(){
		$http_get = I('get.');

		if (IS_POST) {
			$http_post 			= I('post.');
	    	$pay_data_list 		= C('payment');
	    	$pay_data_list[$http_get['type']] = $http_post;
	    	$pay_config_text  = "<?php\r\n";
            $pay_config_text .= "return array(\r\n";
            $pay_config_text .= "  /* 支付配置 */\r\n";
            $pay_config_text .= "  'payment' => ";
            $pay_config_text .= var_export($pay_data_list,true);
            $pay_config_text .= "\r\n";
            $pay_config_text .= ");\r\n";
			$pay_config_text = str_replace('array (', 'array(', $pay_config_text);
			$put_pay_config = file_put_contents(APP_PATH.'Common/Conf/pay_config.php', $pay_config_text);

			if ($http_get['status']=='install') {
				$info_name = '安装';
				$info_url = U('Admin/System/payment_set',array('type'=>$http_get['type']));
			}elseif ($http_get['status']=='uninstall'){
				$info_name = '卸载';
			}else{
				$info_name = '配置保存';
			}
			if ($put_pay_config) {
				$this->ajaxReturn(array('info'=>$info_name.'成功!','url'=>$info_url,'status'=>1));
			}else{
				$this->ajaxReturn(array('info'=>$info_name.'失败,请重试!','status'=>0));
			}
		}else{
			$payment_info = C('payment.'.$http_get['type']);
			$this->assign('info',$payment_info);
			$this->display();
		}
	}
	//支付接口安装卸载
	public function payment_status(){
		$http_get = I('get.');
		$payment_info = C('payment.'.$http_get['type']);
		$this->assign('info',$payment_info);
		$this->display();
	}

	//短信平台
	public function sms(){
		if(IS_POST){
			$http_post = I('post.');

			$message = '您的验证码:'.date('His',time());

			$send_sms = send_sms($http_post['phone'],$message);

			if ($send_sms['code']!=0) {
				$this->ajaxReturn(array('info'=>'发送失败,代码'.$send_sms['code'],'status'=>0));
			}else{
				$this->ajaxReturn(array('info'=>'发送成功,请注意查收!','message'=>$message,'status'=>1));
			}
		}else{
	    	$ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, "http://sms.zhimale.com/api/sms/status.json");

		    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		    curl_setopt($ch, CURLOPT_HEADER, FALSE);

		    curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
		    curl_setopt($ch, CURLOPT_USERPWD  , 'api:key-'.C('ZHIMALE.SMS_API_KEY'));

		    curl_setopt($ch, CURLOPT_POST, TRUE);
		    curl_setopt($ch, CURLOPT_POSTFIELDS, array('phone' => $phone,'message' => $message));
		    $res = curl_exec( $ch );
		    curl_close( $ch );

		    $status_info = json_decode($res,true);
		    $this->assign('status_info',$status_info);
			$this->display();
		}
	}

	//短信平台
	public function sms_config(){
		if (IS_POST) {
			$http_post 			= I('post.');

	    	$sms_config_text  = "<?php\r\n";
            $sms_config_text .= "return array(\r\n";
            $sms_config_text .= "	/* 短信平台配置 */\r\n";
            $sms_config_text .= "	'ZHIMALE' => array(\r\n";
            $sms_config_text .= "		'SMS_API_KEY'=>'".$http_post['key']."',\r\n";
            $sms_config_text .= "		'SMS_SIGN'=>'".$http_post['sign']."',\r\n";
            $sms_config_text .= "	)\r\n";
            $sms_config_text .= ");";

			$put_sms_config = file_put_contents(APP_PATH.'Common/Conf/sms_config.php', $sms_config_text);
			if ($put_sms_config) {
				$this->ajaxReturn(array('info'=>'保存成功!','status'=>1));
			}else{
				$this->ajaxReturn(array('info'=>'保存失败,请重试!','status'=>0));
			}
		}else{
			$this->display();
		}
	}

    /**
     * 微信菜单
     */
    public function menu(){
        $pid_list = M('weixin_menu')->where(array('parent_id'=>0))->order('sort_order asc')->select();
        $this->assign('pid_list',$pid_list);

        $lists = $pid_list;

        foreach ($lists as $key => $value) {
            $lists[$key]['lower'] = M('weixin_menu')->where(array('parent_id'=>$value['cat_id']))->order('sort_order asc')->select();

        }
        //print_r($lists);
        $this->assign('lists',$lists);
        $this->display();
    }

    /**
     * 微信菜单编辑
     */
    public function menu_add(){
        if (IS_POST) {
            $http_post 		= I('post.');
            $data['cat_name'] 	= $http_post['cat_name'];
            $data['parent_id'] 	= $http_post['parent_id'];
            $data['sort_order'] 	= $http_post['sort_order'];
            $data['weixin_type'] 	= $http_post['weixin_type'];
            $data['links'] 	= $http_post['links'];

            $add = M('weixin_menu')->add($data);

            if ($add) {
                $this->success('添加成功!');
            }else{
                $this->error('添加失败!');
            }
        }else{
            $lists = M('weixin_menu')->where(array('parent_id'=>0))->select();
            $this->assign('lists',$lists);
            $this->display();
        }
    }

    public function menu_edit(){
        $http_get 	= I('get.');
        $http_post 	= I('post.');

        if (IS_POST) {
            $data['cat_name'] 	= $http_post['cat_name'];
            $data['parent_id'] 	= $http_post['parent_id'];
            $data['sort_order'] 	= $http_post['sort_order'];
            $data['weixin_type'] 	= $http_post['weixin_type'];
            $data['links'] 	= $http_post['links'];

            $delete = M('weixin_menu')->where(array('cat_id'=>$http_get['id']))->setField($data);

            if ($delete) {
                $this->ajaxReturn(array('info'=>'更新成功!','id'=>$http_post['id'],'status'=>1));
            }else{
                $this->ajaxReturn(array('info'=>'更新失败!','status'=>0));
            }
        }else{

            $info = M('weixin_menu')->where(array('cat_id'=>$http_get['id']))->find();
            $this->assign('info',$info);

            $lists = M('weixin_menu')->where(array('parent_id'=>0))->select();
            $this->assign('lists',$lists);
            $this->display('menu_add');
        }
    }

    function menu_del(){
        if (IS_POST) {
            $http_post 	= I('post.');
            $sub 		= M('weixin_menu')->where(array('parent_id'=>$http_post['id']))->find();
            if($sub){
                $this->error('还有下级分类，先删除一级分类');
            }

            $delete = M('weixin_menu')->where(array('cat_id'=>$http_post['id']))->delete();
            if ($delete) {
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }
    }

    function menu_post(){
        $keyword = array();
        //$sql="SELECT cat_id, cat_name, weixin_type, links, weixin_key as wkey FROM weixin_menu WHERE parent_id = 0 order by sort_order ASC,cat_id desc";
        $topmemu = M('weixin_menu')->field("cat_id, cat_name, weixin_type, links, weixin_key as wkey")->where(array('parent_id'=>0))->order("sort_order, cat_id desc")->select();
        foreach ($topmemu as $key) {
            //$sql="SELECT cat_id, cat_name, weixin_type, links, weixin_key as wkey FROM ". $GLOBALS['hhs']->table('weixin_menu') ." WHERE parent_id =". $key['cat_id']."  order by sort_order ASC,cat_id desc";
            $nextmenu = $topmemu = M('weixin_menu')->field("cat_id, cat_name, weixin_type, links, weixin_key as wkey")->where(array('parent_id'=>$key['cat_id']))->order("sort_order, cat_id desc")->select();
            if(count($nextmenu) != 0){//没有下级栏目
                foreach ($nextmenu as $key2) {
                    if($key2['weixin_type']>0){
                        $kk[] = array('type' => 'view', 'name' => $key2['cat_name'], 'url' => $key2['links']);
                    }else{
                        $kk[] = array('type' => 'click', 'name' => $key2['cat_name'], 'key' => $key2['wkey']);
                    }
                }
                $keyword['button'][] = array('name' => $key['cat_name'], 'sub_button' => $kk);
                $kk = '';
            }else{
                if($key['weixin_type']>0){
                    $keyword['button'][] = array('type' => 'view', 'name' => $key['cat_name'], 'url' => $key['links']);
                }else{
                    $keyword['button'][] = array('type' => 'click', 'name' => $key['cat_name'], 'key' => $key['wkey']);
                }
            }
        }
        $data = json_encode($keyword);
         $d = preg_replace("#\\\u([0-9a-f]+)#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", $data);

        $msg = \Wechat\Wxapi::create_menu($d);
        print_r($msg);
        if ($msg['errmsg'] == 'ok') {

            echo 'ok!';

            exit;

        } else {

            echo 'false!';

            exit;

        }

    }


}