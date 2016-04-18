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
require_once ($root_path . '/auto/config.php');
require_once($root_path .'/auto/cards.php');
class Automatch{
    public function __construct($data){
        $this->qun = M('qun')->where( "UserName='{$data['user']['id']}'")->find();
        $this->user = M('qun_user')->where("UserName='{$data['content']['user']['id']}'")->find();
        $this->json = $json = array(
            'test'=>array(
              'qun'=>$this->qun,
                'user'=>$this->user
            ),
            'msg_code' => 10001,
            'msg_content' => '',
            'data' => array(
                'type' => 1,
                'uid' =>$data['user']['id'],
                'message' => "",
                'expand' =>''
            ),
            //'post' => $_POST
        );
    }

    /**
     * @param $app
     * @param $param
     */
    function U($app, $param=array()){
        return "http://sh.kakaapp.com/index.php?s=".str_replace('__APP__','',U($app, $param));
    }

    /**
     * 启动游戏
     * @param $data
     */
    function open($data){
        global $base_url;
        $json = $this->json;
        do{
            // 判断是否有在进行中的游戏
            $game = M('zhajinhua')->where("status in(0,1) AND update_time>".time()-600)->find();
            if($game){
                $json['data']['message'] = "@{$this->user['nickname']} 目前还有游戏正在进行中。。。 请等游戏完成后再启动。";
                break;
            }

            if($this->user['qun_credit'] < 10){
                $json['data']['message'] = "@{$this->user['nickname']} 你的游戏金币少于10个，请充值后再进行游戏。充值地址：". $this->U('/zjh/top');// .U('/zjh/top',array(),true);
                break;
            }
            // `qun_id`, `qun_name`, `number_no`, `user_id`, `card_data`, `total_user`, `total_credit`, `win_user`, `status`, `addtime`, `finish_time`, `update_time`, `nexit_user_id`
            $data = array(
                'qun_id' => $this->qun['id'],
                'qun_name' => $this->qun['nickname'],
                'number_no' => get_order_sn('ZJH'),
                'user_id' => $this->user['id'],
                'addtime' => time(),
                'update_time' => time()
            );
            $res = M('zhajinhua')->add($data);
            if($res){
                // `zha_id`, `user_id`, `card_data`, `status`, `credit`, `addtime`, `is_win`, `update_time`, `credit_log`, `is_show`
                $zha = M('zhajinhua')->where(array('number_no'=>$data['number_no']))->find();
                $data_user = array(
                    'zha_id' => $zha['id'],
                    'user_id' => $this->user['id'],
                    'addtime' => time()
                );
                M('zhajinhua_user')->add($data_user);
                $json['data']['message'] = "@{$this->user['nickname']} 创建成功, 参加游戏请输入指令：【加入】, 游戏详情：". $this->U('/zjh/game/detail',array('id'=>$data['number_no']),true);
                break;
            }
        }while(false);

        return $json;
    }

    /**
     * 加入游戏
     * @param $data
     * @return array
     */
    function join($data){
        global $base_url;
        $json = $this->json;
        do{
            // 判断是否有在进行中的游戏
            $game = M('zhajinhua')->where("status in(0,1) AND update_time>".time()-600)->find();
            if(!$game){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没有进行中的游戏。';
                break;
            }

            if($this->user['qun_credit'] < 10){
                $json['data']['message'] = "@{$this->user['nickname']} 你的游戏金币少于10个，请充值后再进行游戏。充值地址：".$this->U('/zjh/top',array(),true);
                break;
            }

            // 判断是否加入过
            $user = M('zhajinhua_user')->where(array('qun_id'=>$game['id'], 'user_id'=>$this->user['id']))->find();

            // `zha_id`, `user_id`, `card_data`, `status`, `credit`, `addtime`, `is_win`, `update_time`, `credit_log`, `is_show`
            if( ! $user){
                $data_user = array(
                    'zha_id' => $game['id'],
                    'user_id' => $this->user['id'],
                    'addtime' => time()
                );
                M('zhajinhua_user')->add($data_user);

                $json['data']['message'] = "@{$this->user['nickname']} 加入游戏, 游戏详情：".$this->U('/zjh/game/detail',array('id'=>$game['number_no']),true);
            }else{

                $json['data']['message'] = "@{$this->user['nickname']} 已经加入游戏, 游戏详情：".$this->U('/zjh/game/detail',array('id'=>$game['number_no']),true);
            }
            break;

        }while(false);
        return $json;
    }

    /**
     * 开始游戏
     */
    function start($data){
        $json = $this->json;
        do{
            // 判断是否有在进行中的游戏
            $game = M('zhajinhua')->where("status in(0,1) AND update_time>".time()-600)->find();
            if(!$game){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没有进行中的游戏。';
                break;
            }

            if($this->user['id'] != $game['user_id']){
                $user = M('qun_user')->find($game['user_id']);
                $json['data']['message'] = "@{$this->user['nickname']} 只有游戏创建者【{$user['nickname']}】才能开始游戏。";
                break;
            }

            $user_list = M('zhajinhua_user')->where(array('qun_id'=>$game['id']))->select();
            if(count($user_list) < 2){
                $json['data']['message'] = "@{$this->user['nickname']} 参与游戏的人数必须要在2-10个人。";
                break;
            }

            // 创建扑克牌
            $card = new Cards();
            $total_credit = 0;
            $card_data = array();
            foreach($user_list as $user){
                $card_item = $card->getCard();
                $card_data[] = array(
                    'user_id'=>$user['user_id'],
                    'card_data' => $card_item
                );
                $item = array(
                    'card_data' => json_encode($card_item),
                    'status' => 1,
                    'credit' => 5,
                    'credit_log' => json_encode(array('底池+5金币')),
                    'update_time' => time()
                );
                $total_credit += 5;
                M('zhajinhua_user')->where(array('id'=>$user['id']))->save($item);
            }

            M('zhajinhua')->where(array('id'=>$game['id']))->save(
                array(
                    'status' => 1,
                    'update_time' => time(),
                    'total_credit' => $total_credit,
                    'nexit_user_id' => $user_list[0]['user_id'],
                    'card_data' => json_encode($card_data),
                    'total_user' => count($user_list)
                )
            );

            $user = M('qun_user')->find($user_list[0]['user_id']);
            $json['data']['message'] = "游戏开始了, 轮到【{$user['nickname']}】说话， 可以选择【看牌】【跟注】【弃牌】";
            break;

        }while(false);
        return $json;
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
