<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-2-19
 * Time: 下午2:18
 * To change this template use File | Settings | File Templates.
 */
set_time_limit(0);
$root_path = realpath(dirname(dirname(__FILE__)));
include($root_path . '/auto/config.php');

class match{
    public function __construct($data){
        $this->qun = M('qun')->where(array('UserName'=>$data['user']['id']))->find();
        $this->user = M('qun_user')->where( array('UserName'=>$data['content']['user']['id']))->find();
        $this->json = $json = array(
            'msg_code' => 10001,
            'msg_content' => '',
            'data' => array(
                'type' => 1,
                'uid' =>'',
                'message' => "",
                'expand' =>''
            ),
            //'post' => $_POST
        );
    }

    /**
     * 启动游戏
     * @param $data
     */
    function start($data){

        return $this->json;
    }

    /**
     * 开始游戏
     */
    function open($data){

        return $this->json;
    }

    /**
     * 跟牌
     */
    function genpai($data){

    }

    /**
     * 加注
     * @param $data
     */
    function jiazhu($data){

        return $this->json;
    }

    /**
     * 看牌
     * @param $data
     */
    function kaipai($data){

        return $this->json;
    }

    /**
     * 弃牌
     * @param $data
     */
    function qipai($data){

        return $this->json;
    }

    /**
     * 准备
     */
    function zhubei($data){

        return $this->json;
    }

    /**
     * 结束
     * @param $data
     */
    function jieshu($data){

        return $this->json;
    }

    /**
     * 比牌
     * @param $data
     * @return mixed
     */
    function bipai($data){

        return $this->json;
    }
}
