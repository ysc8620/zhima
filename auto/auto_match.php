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
        global $root_path;
        $this->msg = $data;
        $this->time = time() - 60000;
        $this->qun = M('qun')->where( "UserName='{$data['user']['id']}'")->find();
        $this->user = M('qun_user')->where("UserName='{$data['content']['user']['id']}'")->find();
        $this->json = $json = array(
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
        flogs($root_path.'/auto/msg.log', json_encode($data));
    }

    function dwz($url){
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,"http://dwz.cn/create.php");
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $data=array('url'=>$url);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $strRes=curl_exec($ch);
        curl_close($ch);
        $arrResponse=json_decode($strRes,true);
        if($arrResponse['status'] == 0)
        {
            /**错误处理*/
            // echo iconv('UTF-8','GBK',$arrResponse['err_msg'])."\n";
            return $url;
        }
        /** tinyurl */
        return $arrResponse['tinyurl'];
    }
    /**
     * @param $app
     * @param $param
     */
    function U($app, $param=array()){
        $url = "http://sh.kakaapp.com/index.php?s=".str_replace('__APP__','',U($app, $param));

        $data = \Wechat\Wxapi::dwz($url);
        if($data['errcode'] == '0' && $data['short_url']){
            return $data['short_url'];
        }else{
            return $url;
        }
    }

    function get_command(){
        // 【@群助理 炸金花】【准备】【加+金币数】【开始】【看牌】【跟牌】【弃牌】【开牌】
        $command = array(
            1 => '【准备】',
            2 => '【加+金币数】',
            3 => '【开始】',
            4 => '【跟牌】',
            5 => '【看牌】',
            6 => '【比牌】',
            7 => '【弃牌】',
            8 => '【加入】'
        );

        return $command;
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
            $game = M('zhajinhua')->where("qun_id = '{$this->qun['id']}' AND status in(0,1) AND update_time>{$this->time}")->find();
            if($game){
                $json['data']['message'] = "@{$this->user['nickname']} 目前还有游戏正在进行中。。。 请等游戏完成后再启动。";
                break;
            }

            if($this->user['qun_credit'] < 10){
                $json['data']['message'] = "@{$this->user['nickname']} 你的游戏金币少于10个，请充值后再进行游戏。充值地址：". $this->U('/zjh/top');// .U('/zjh/top',array(),true);
                break;
            }
            // `qun_id`, `qun_name`, `number_no`, `user_id`, `card_data`, `total_user`, `total_credit`, `win_user`, `status`, `addtime`, `finish_time`, `update_time`, `next_user_id`
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
                    'addtime' => time(),
                    'nickname' => $this->user['nickname']
                );
                M('zhajinhua_user')->add($data_user);
                $json['data']['message'] = "@{$this->user['nickname']} 游戏创建成功,底池5金币,最小投注5金币,最大投注100金币。 加入游戏请说【加入】,游戏详情：". $this->U('/zjh/game/detail',array('id'=>$data['number_no']),true);
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
        $json = $this->json;
        do{
            // 判断是否有在进行中的游戏
            $game = M('zhajinhua')->where("qun_id = '{$this->qun['id']}' AND status in(0) AND update_time>{$this->time}")->find();
            if(!$game){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没有进行中的游戏。';
                break;
            }

            if($this->user['qun_credit'] < 10){
                $json['data']['message'] = "@{$this->user['nickname']} 你的游戏金币少于10个,请充值后再进行游戏.充值地址：".$this->U('/zjh/top',array(),true);
                break;
            }


            $user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id']))->select();
            if(count($user_list) > 9){
                $game_user = M('qun_user')->find($game['user_id']);
                $json['data']['message'] = "@{$this->user['nickname']} 参与人数已满,请下次再玩. 开始游戏请【@{$game_user['nickname']} 】说【开始】";
                break;
            }

            // 判断是否加入过
            $user = M('zhajinhua_user')->where(array('zha_id'=>$game['id'], 'user_id'=>$this->user['id']))->find();
            $game_user = M('qun_user')->find($game['user_id']);
            // `zha_id`, `user_id`, `card_data`, `status`, `credit`, `addtime`, `is_win`, `update_time`, `credit_log`, `is_show`

            if( ! $user){
                $data_user = array(
                    'zha_id' => $game['id'],
                    'user_id' => $this->user['id'],
                    'nickname' => $this->user['nickname'],
                    'addtime' => time()
                );
                M('zhajinhua_user')->add($data_user);
                $user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id']))->select();
                $user_str = '';
                foreach($user_list as $uuser){
                    $user_str .= '【'.$uuser['nickname'].'】';
                }
                $user_str = $user_str?'压注顺序:'.$user_str:'';

                M('zhajinhua')->where(array('id'=>$game['id']))->save(array('update_time'=>time()));
                $json['data']['message'] = "@{$this->user['nickname']} 加入游戏,开始请【@{$game_user['nickname']} 】说【开始】$user_str 游戏详情:".$this->U('/zjh/game/detail',array('id'=>$game['number_no']),true);
            }else{
                $user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id']))->select();
                $user_str = '';
                foreach($user_list as $uuser){
                    $user_str .= '【'.$uuser['nickname'].'】';
                }
                $user_str = $user_str?'压注顺序:'.$user_str:'';
                $json['data']['message'] = "@{$this->user['nickname']} 已经加入游戏,开始请【@{$game_user['nickname']} 】说【开始】$user_str 游戏详情:".$this->U('/zjh/game/detail',array('id'=>$game['number_no']),true);
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
            $game = M('zhajinhua')->where("qun_id = '{$this->qun['id']}' AND status in(0) AND update_time>{$this->time}")->find();
            if(!$game){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没有进行中的游戏。';
                break;
            }

            if($this->user['id'] != $game['user_id']){
                $user = M('qun_user')->find($game['user_id']);
                $json['data']['message'] = "@{$this->user['nickname']} 只有游戏创建者【@{$user['nickname']} 】才能开始游戏。开始请【{$user['nickname']} 】说【开始】. ";
                break;
            }

            $user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id']))->select();
            if(count($user_list) < 2){
                $json['data']['message'] = "@{$this->user['nickname']} 参与游戏的人数必须要在2-10个人, 没加入的伙伴赶紧说【加入】参与.".count($user_list);
                break;
            }

            // 创建扑克牌
            $card = new Cards();
            $total_credit = 0;
            $card_data = array();
            $game_log = array();
            foreach($user_list as $user){
                $card_item = $card->getCard();
                $card_data[] = array(
                    'user_id'=>$user['user_id'],
                    'card_data' => $card_item
                );
                $log = array('type'=>'dichi', 'user_id'=>$user['user_id'], 'credit'=>5,'is_show'=>$user['is_show'],'time'=>time(),'total_jiaopai'=>0);
                array_push($game_log, $log);
                $item = array(
                    'card_data' => json_encode($card_item),
                    'status' => 1,
                    'credit' => 5,
                    'credit_log' => json_encode(array($log)),
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
                    'next_user_id' => $user_list[0]['user_id'],
                    'card_data' => json_encode($card_data),
                    'total_user' => count($user_list),
                    'credit_log' =>json_encode($game_log)
                )
            );

            $user = M('qun_user')->find($user_list[0]['user_id']);
            $user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id']))->select();
            $user_str = '';
            foreach($user_list as $uuser){
                $user_str .= '【'.$uuser['nickname'].'】';
            }
            $user_str = $user_str?'压注顺序：'.$user_str:'';

            $json['data']['message'] = "游戏开始了, 可以开始压注了，$user_str 接下来【@{$user['nickname']} 】说话， 可以选择【看牌】【跟牌】【弃牌】【加+金币数】";
            break;

        }while(false);
        return $json;
    }

    /**
     * 跟牌
     */
    function genpai($data){
        $json = $this->json;
        do{
            // 判断是否有在进行中的游戏
            $game = M('zhajinhua')->where("qun_id = '{$this->qun['id']}' AND status in(1) AND update_time>{$this->time}")->find();
            if(!$game){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没有进行中的游戏。';
                break;
            }

            $game_user = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'user_id'=>$this->user['id']))->find();
            if(!$game_user){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '该用户没有参加游戏。';
                break;
            }

            if($this->user['id'] != $game['next_user_id']){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没轮到该用户说话。'."{$this->user['id']}={$game['next_user_id']}";
                break;
            }

            if($game_user['status'] != 1){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '该用户已经弃牌。';
                break;
            }

            $last_user_id = $game['next_user_id'];
            $credit = $game['last_credit'] > 0?$game['last_credit']:5;

            // 判断用户跟注金币， 是否看牌为准
            if($game['last_is_show']){
                if( ! $game_user['is_show']){
                    $credit = intval($credit/2);
                }
            }else{
                if($game_user['is_show']){
                    $credit = $credit * 2;
                }
            }

            $next_user = M('zhajinhua_user')->where("zha_id='{$game['id']}' AND id>'{$game_user['id']}' AND status=1")->order("id ASC")->find();
            if(!$next_user){
                $next_user = M('zhajinhua_user')->where("zha_id='{$game['id']}' AND status=1")->order("id ASC")->find();
                if($next_user['id'] == $game_user['id']){
                    $json['data']['message'] = "@{$this->user['nickname']} 没有其他说话用户 请说【开牌】结束游戏";
                    break;
                }
            }

            $game_credit_log = $game['credit_log']?json_decode($game['credit_log']):array();
            array_push( $game_credit_log, array('type'=>'genpai', 'user_id'=>$game_user['user_id'], 'credit'=>$credit,'is_show'=>$game_user['is_show'],'time'=>time(),'total_jiaopai'=>$game_user['total_jiaopai']+1) );
            // 更新游戏信息
            M('zhajinhua')->where(array('id'=>$game['id']))->save(
                array(
                    'update_time' => time(),
                    'total_credit' => $game['total_credit'] + $credit,
                    'last_user_id' => $game_user['user_id'],
                    'last_is_show' => $game_user['is_show'],
                    'last_credit' => $credit,
                    'next_user_id' => $next_user['user_id'],
                    'total_jiaopai' => ($game['total_jiaopai'] + 1),
                    'credit_log' => json_encode($game_credit_log)
                )
            );

            $user_credit_log = $game_user['credit_log']?json_decode($game_user['credit_log']):array();
            array_push($user_credit_log, array('type'=>'genpai','credit'=>$credit, 'is_show'=>$game_user['is_show'], 'time'=>time(), 'total_jiaopai'=>$game_user['total_jiaopai']+1));
            // 更新参与用户信息
            M('zhajinhua_user')->where(array('id'=>$game_user['id']))->save(
               array(
                   'credit' => $game_user['credit'] + $credit,
                   'update_time' => time(),
                   'total_jiaopai' => ($game_user['total_jiaopai'] + 1),
                   'credit_log' => json_encode($user_credit_log)
                )
            );

            $next_user_info = M('qun_user')->find($next_user['user_id']);
            $command = '';
            if(!$next_user_info['is_show']){
                $command .= "【看牌】";
            }

            $user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'status'=>1))->select();
            $kaipai_str = '';
            if(count($user_list) == 2){
                $kaipai_str .= "【开牌】";
            }elseif(count($user_list) < 2){
                $json['data']['message'] = "@{$this->user['nickname']} 没有其他说话用户 请说【开牌】结束游戏";
                break;
            }

            $json['data']['message'] = "游戏进行中,【{$this->user['nickname']}】跟{$credit}金币,接下来轮到【{$next_user_info['nickname']}】说话,可以说$command【跟牌】【加+金币数】【比牌】$kaipai_str【弃牌】";
            break;

        }while(false);
        return $json;
    }

    /**
     * 加注
     * @param $data
     */
    function jiazhu($data){
        $json = $this->json;
        do{
            // 判断是否有在进行中的游戏
            $game = M('zhajinhua')->where("qun_id = '{$this->qun['id']}' AND status in(1) AND update_time>{$this->time}")->find();
            if(!$game){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没有进行中的游戏。';
                break;
            }

            $game_user = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'user_id'=>$this->user['id']))->find();
            if(!$game_user){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '该用户没有参加游戏。';
                break;
            }

            if($this->user['id'] != $game['next_user_id']){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没轮到该用户说话。'."{$this->user['id']}={$game['next_user_id']}";;
                break;
            }

            if($game_user['status'] != 1){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '该用户已经弃牌。';
                break;
            }

            $next_user = M('zhajinhua_user')->where("zha_id='{$game['id']}' AND id>'{$game_user['id']}' AND status=1")->order("id ASC")->find();
            if(!$next_user){
                $next_user = M('zhajinhua_user')->where("zha_id='{$game['id']}' AND status=1")->order("id ASC")->find();
                if($next_user['id'] == $game_user['id']){
                    $json['data']['message'] = "@{$this->user['nickname']} 没有可以说话用户 可以选择【开牌】";
                    break;
                }
            }

            $credit = $game['last_credit'] > 0?$game['last_credit']:5;

            // 判断用户跟注金币， 是否看牌为准
            if($game['last_is_show']){
                if(! $game_user['is_show']){
                    $credit = intval($credit/2);
                }
            }else{
                if($game_user['is_show']){
                    $credit = $credit * 2;
                }
            }

            //
            preg_match_all("/^(加|加注)\s*(\d+)$/i", $this->msg['content']['data'], $re);
            $new_credit = intval($re[2][0]);
            if($credit < 5 || ($new_credit) > 100 || $new_credit < $credit){
                $json['data']['message'] = "@{$this->user['nickname']} 您加注金币不在有效范围,加注必须在{$credit}-100金币之间. 可以选择【加+金币数】";
                break;
            }

            $game_credit_log = $game['credit_log']?json_decode($game['credit_log']):array();
            array_push( $game_credit_log, array('type'=>'jiazhu','user_id'=>$game_user['user_id'], 'credit'=>$new_credit,'is_show'=>$game_user['is_show'],'time'=>time(),'total_jiaopai'=>$game_user['total_jiaopai']+1) );
            // 更新游戏信息
            M('zhajinhua')->where(array('id'=>$game['id']))->save(
                array(
                    'update_time' => time(),
                    'total_credit' => $game['total_credit'] + $new_credit,
                    'last_user_id' => $game_user['user_id'],
                    'last_is_show' => $game_user['is_show'],
                    'last_credit' => $new_credit,
                    'next_user_id' => $next_user['user_id'],
                    'total_jiaopai' => ($game['total_jiaopai'] + 1),
                    'credit_log' => json_encode($game_credit_log)
                )
            );

            $user_credit_log = $game_user['credit_log']?json_decode($game_user['credit_log']):array();
            array_push($user_credit_log, array('type'=>'jiazhu','credit'=>$new_credit, 'is_show'=>$game_user['is_show'], 'time'=>time(), 'total_jiaopai'=>$game_user['total_jiaopai']+1));
            // 更新参与用户信息
            M('zhajinhua_user')->where(array('id'=>$game_user['id']))->save(
                array(
                    'credit' => $game_user['credit'] + $new_credit,
                    'update_time' => time(),
                    'total_jiaopai' => ($game_user['total_jiaopai'] + 1),
                    'credit_log' => json_encode($user_credit_log)
                )
            );
            $next_user_info = M('qun_user')->find($next_user['user_id']);

            $command = '';
            if(!$next_user_info['is_show']){
                $command .= "【看牌】";
            }

            $user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'status'=>1))->select();
            $kaipai_str = '';
            if(count($user_list) == 2){
                $kaipai_str .= "【开牌】";
            }elseif(count($user_list) < 2){
                $json['data']['message'] = "@{$this->user['nickname']} 没有其他说话用户 请说【开牌】结束游戏";
                break;
            }

            $json['data']['message'] = "游戏进行中,【{$this->user['nickname']}】加注{$new_credit}金币，接下来轮到【{$next_user_info['nickname']}】说话， 可以选择$command【跟牌】【加+金币数】【比牌】$kaipai_str【弃牌】";

        }while(false);
        echo json_encode($json);
        die();
    }

    /**
     * 看牌
     *
     * @param $data
     * @return array
     */
    function kanpai($data){
        $json = $this->json;
        do{
            // 判断是否有在进行中的游戏
            $game = M('zhajinhua')->where("qun_id = '{$this->qun['id']}' AND status in(1) AND update_time>{$this->time}")->find();
            if(!$game){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没有进行中的游戏。';
                break;
            }

            $game_user = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'user_id'=>$this->user['id']))->find();
            if(!$game_user){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '该用户没有参加游戏。';
                break;
            }

            if($this->user['id'] != $game['next_user_id']){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没轮到该用户说话。';
                break;
            }

            if($game_user['status'] != 1){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '该用户已经弃牌。';
                break;
            }

            M('zhajinhua')->where(array('id'=>$game['id']))->save(array('update_time'=>time()));
            M('zhajinhua_user')->where(array('id'=>$game_user['id']))->save(array('is_show'=>1, 'update_time'=>time()));

            $command = '';
            if(!$this->user['is_show']){
                $command .= "【看牌】";
            }

            $user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'status'=>1))->select();
            $kaipai_str = '';
            if(count($user_list) == 2){
                $kaipai_str .= "【开牌】";
            }elseif(count($user_list) < 2){
                $json['data']['message'] = "@{$this->user['nickname']} 没有其他说话用户 请说【开牌】结束游戏";
                break;
            }

            $json['data']['message'] = "@{$this->user['nickname']} 底牌查看：".$this->U('/zjh/game/detail',array('id'=>$game['number_no']),true).",【{$this->user['nickname']}】继续说话,可以说$command【跟牌】【加+金币数】$kaipai_str【弃牌】";
        }while(false);
        echo json_encode($json);
        die();
    }

    /**
     * 开牌
     * @param $data
     */
    function kaipai($data){
        $json = $this->json;
        do{
            // 判断是否有在进行中的游戏
            $game = M('zhajinhua')->where("qun_id = '{$this->qun['id']}' AND status in(1) AND update_time>{$this->time}")->find();
            if(!$game){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没有进行中的游戏。';
                break;
            }

            $game_user = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'user_id'=>$this->user['id']))->find();
            if(!$game_user){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '该用户没有参加游戏。';
                break;
            }

            if($game_user['status'] != 1){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '该用户已经弃牌。';
                break;
            }
            $command = '';
            if(!$game_user['is_show']){
                $command .="【看牌】";
            }
            $game_user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'status'=>1))->select();
            if(count($game_user_list) > 2){
                $json['data']['message'] = "@{$this->user['nickname']} 用户大于2人，不能开牌。 请选择$command【跟注】【加+金币数】【比牌】【弃牌】";
                break;
            }
            $game_user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'status'=>1))->select();
            if(count($game_user_list) == 1){
                 $win_user = $game_user;
            }else{
                $card1 = json_decode($game_user_list[0]['card_data']);
                $card2 = json_decode($game_user_list[1]['card_data']);
                $card = new cards();
                $bool = $card->compareCards($card1, $card2);
                if($bool > 0){
                    $win_user = $game_user_list[0];
                }elseif($bool < 0){
                    $win_user = $game_user_list[1];
                }else{
                    if($game_user['id'] == $game_user_list[0]['id']){
                        $win_user = $game_user_list[1];
                    }else{
                        $win_user = $game_user_list[0];
                    }
                }
            }

            $credit = $game['last_credit'] > 0?$game['last_credit']:5;

            // 判断用户跟注金币， 是否看牌为准
            if($game['last_is_show']){
                if(! $game_user['is_show']){
                    $credit = intval($credit/2);
                }
            }else{
                if($game_user['is_show']){
                    $credit = $credit * 2;
                }
            }

            ////////////////////////////////////////////////////////////////////////////////////////////////////
            $game_credit_log = $game['credit_log']?json_decode($game['credit_log']):array();
            array_push( $game_credit_log, array('type'=>'kaipai','user_id'=>$game_user['user_id'], 'credit'=>$credit,'is_show'=>$game_user['is_show'],'time'=>time(),'total_jiaopai'=>$game_user['total_jiaopai']+1) );
            // 更新游戏信息
            M('zhajinhua')->where(array('id'=>$game['id']))->save(
                array(
                    'update_time' => time(),
                    'total_credit' => $game['total_credit'] + $credit,
                    'last_user_id' => $game_user['user_id'],
                    'last_is_show' => $game_user['is_show'],
                    'last_credit' => $credit,
                    'next_user_id' => 0,
                    'total_jiaopai' => ($game['total_jiaopai'] + 1),
                    'credit_log' => json_encode($game_credit_log),
                    'win_user'=>$win_user['user_id'],
                    'status' => 2,
                    'kaipai_user_id'=>$game_user['user_id'],
                    'finish_time' => time()
                )
            );
            M('zhajinhua_user')->where(array('id'=>$win_user['id']))->save(array('is_win'=>1));
            $user_credit_log = $game_user['credit_log']?json_decode($game_user['credit_log']):array();
            array_push($user_credit_log, array('type'=>'kaipai','credit'=>$credit, 'is_show'=>$game_user['is_show'], 'time'=>time(), 'total_jiaopai'=>$game_user['total_jiaopai']+1));
            // 更新参与用户信息
            M('zhajinhua_user')->where(array('id'=>$game_user['id']))->save(
                array(
                    'credit' => $game_user['credit'] + $credit,
                    'update_time' => time(),
                    'total_jiaopai' => ($game_user['total_jiaopai'] + 1),
                    'credit_log' => json_encode($user_credit_log),
                    'is_kaipai' => 1,
                    'status' => 2
                )
            );
            $user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id']))->select();
            $msg = "";
            foreach($user_list as $user){
                $user_info = M('qun_user')->find($user['user_id']);
                $total_user_credit = $user['credit'] - $game['dichi'];
                $cards = json_decode($user['card_data']);
                $card_str = '';
                foreach($cards as $card){
                    $card_str .= "{$card[0]}{$card[1]},";
                }
                $card_str = trim($card_str,',');
                if($user['is_win']){
                    $card_str .= ",获胜者";
                }
                $msg .= $user_info['nickname'].",共压注{$user['total_jiaopai']}轮,压注{$total_user_credit}金币,得牌:$card_str\n";
            }
            $win_user_info = M('qun_user')->find($win_user['user_id']);
            $json['data']['message'] = "游戏结束， 恭喜【{$win_user_info['nickname']}】，在本轮游戏中获胜，获得{$game['total_credit']}金币。 继续游戏请选择【准备】\n".$msg;
            break;
            /////////////////////////////////////////////////////////////////////////////////////////////////////
        }while(false);
        echo json_encode($json);
        die();
    }

    /**
     * 弃牌
     * @param $data
     */
    function qipai($data){
        $json = $this->json;
        do{
            // 判断是否有在进行中的游戏
            $game = M('zhajinhua')->where("qun_id = '{$this->qun['id']}' AND status in(1) AND update_time>{$this->time}")->find();
            if(!$game){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没有进行中的游戏。';
                break;
            }

            $game_user = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'user_id'=>$this->user['id']))->find();
            if(!$game_user){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '该用户没有参加游戏。';
                break;
            }

            M('zhajinhua_user')->where(array('id'=>$game_user['id']))->save(array('status'=>4,'update_time'=>time()));

            $next_user = M('zhajinhua_user')->where("zha_id='{$game['id']}' AND id>'{$game_user['id']}' AND status=1")->order("id ASC")->find();
            if(!$next_user){
                $next_user = M('zhajinhua_user')->where("zha_id='{$game['id']}' AND status=1")->order("id ASC")->find();
                if($next_user['id'] == $game_user['id']){
                    $json['data']['message'] = "@{$this->user['nickname']} 没有可以说话用户 可以选择【开牌】";
                    break;
                }
            }



            $next_user_info = M('qun_user')->find($next_user['user_id']);
            $command = '';
            if(!$next_user_info['is_show']){
                $command .= "【看牌】";
            }

            $user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'status'=>1))->select();
            $kaipai_str = '';
            if(count($user_list) == 2){
                $kaipai_str .= "【开牌】";
            }elseif(count($user_list) < 2){
                $json['data']['message'] = "@{$next_user_info['nickname']} 没有其他说话用户 请说【开牌】结束游戏";
                break;
            }

            $json['data']['message'] = "游戏进行中,【{$this->user['nickname']}】弃牌，接下来轮到【{$next_user_info['nickname']}】说话， 可以选择$command【跟牌】【加+金币数】$kaipai_str【弃牌】";
            break;

        }while(false);
        echo json_encode($json);
        die();
    }

    /**
     * 准备
     */
    function zhunbei($data){
        $json = $this->json;
        do{
            // 判断是否有在进行中的游戏
            $game = M('zhajinhua')->where("qun_id = '{$this->qun['id']}' AND status in(0) AND update_time>{$this->time}")->find();

            if($this->user['qun_credit'] < 10){
                $json['data']['message'] = "@{$this->user['nickname']} 你的游戏金币少于10个，请充值后再进行游戏。充值地址：".$this->U('/zjh/top',array(),true);
                break;
            }

            if(!$game){
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
                        'addtime' => time(),
                        'nickname' =>$this->user['nickname']
                    );
                    M('zhajinhua_user')->add($data_user);
                    $json['data']['message'] = "@{$this->user['nickname']} 加入游戏,开始请按【开始】. 游戏详情：".$this->U('/zjh/game/detail',array('id'=>$zha['number_no']),true);
                    break;
                }
            }

            $user_list = M('zhajinhua_user')->where(array('qun_id'=>$game['id'],'zha_id'=>$game['id']))->select();
            if(count($user_list) > 9){
                $json['data']['message'] = "@{$this->user['nickname']} 参与人数已满，请下次再玩。请选择【开始】游戏";
                break;
            }

            // 判断是否加入过
            $user = M('zhajinhua_user')->where(array('qun_id'=>$game['id'], 'user_id'=>$this->user['id']))->find();
            $game_user = M('qun_user')->find($game['user_id']);
            // `zha_id`, `user_id`, `card_data`, `status`, `credit`, `addtime`, `is_win`, `update_time`, `credit_log`, `is_show`
            if( ! $user){
                $data_user = array(
                    'zha_id' => $game['id'],
                    'user_id' => $this->user['id'],
                    'addtime' => time(),
                    'nickname' => $this->user['nickname']
                );
                M('zhajinhua_user')->add($data_user);
                M('zhajinhua')->where(array('id'=>$game['id']))->save(array('update_time'=>time()));
                $json['data']['message'] = "@{$this->user['nickname']} 加入游戏,开始【@{$game_user['nickname']}】请说【开始】.游戏详情：".$this->U('/zjh/game/detail',array('id'=>$game['number_no']),true);
            }else{

                $json['data']['message'] = "@{$this->user['nickname']} 已经加入游戏,开始【@{$game_user['nickname']}】请说【开始】.游戏详情：".$this->U('/zjh/game/detail',array('id'=>$game['number_no']),true);
            }
            break;

        }while(false);
        return $json;
    }

    /**
     * 结束
     * @param $data
     */
    function jieshu($data){

        $json = $this->json;
        $json['data']['message'] = "接口正在紧张开发中";
        echo json_encode($json);
        die();
    }

    /**
     * 比牌
     * @param $data
     * @return mixed
     */
    function bipai($data){
        $json = $this->json;
        do{
            // 判断是否有在进行中的游戏
            $game = M('zhajinhua')->where("qun_id = '{$this->qun['id']}' AND status in(1) AND update_time>{$this->time}")->find();
            if(!$game){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没有进行中的游戏。';
                break;
            }

            $game_user = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'user_id'=>$this->user['id']))->find();
            if(!$game_user){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '该用户没有参加游戏。';
                break;
            }

            if($game_user['status'] != 1){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '该用户已经弃牌。';
                break;
            }

            if($this->user['id'] != $game['next_user_id']){
                $json['msg_code'] = 10002;
                $json['msg_content'] = '没轮到该用户说话。';
                break;
            }

            if(! $game['last_user_id']){
                $json['data']['message'] = "@{$this->user['nickname']} 现在不允许比牌。 请选择【看牌】【跟牌】【加+金币数】【弃牌】";
                break;
            }

            $next_user = M('zhajinhua_user')->where("zha_id='{$game['id']}' AND id>'{$game_user['id']}' AND status=1")->order("id ASC")->find();
            if(!$next_user){
                $next_user = M('zhajinhua_user')->where("zha_id='{$game['id']}' AND status=1")->order("id ASC")->find();
                if($next_user['id'] == $game_user['id']){
                    $json['data']['message'] = "@{$this->user['nickname']} 没有可以说话用户 可以选择【开牌】";
                    break;
                }
            }

            $credit = $game['last_credit'] > 0?$game['last_credit']:5;

            // 判断用户跟注金币， 是否看牌为准
            if($game['last_is_show']){
                if(! $game_user['is_show']){
                    $credit = intval($credit/2);
                }
            }else{
                if($game['is_show']){
                    $credit = $credit * 2;
                }
            }

            /////////////////////////////////////////////////////////////////////////////////
            $game_credit_log = $game['credit_log']?json_decode($game['credit_log']):array();
            array_push( $game_credit_log, array('type'=>'bipai','user_id'=>$game_user['user_id'], 'credit'=>$credit,'is_show'=>$game_user['is_show'],'time'=>time(),'total_jiaopai'=>$game_user['total_jiaopai']+1) );
            // 更新游戏信息
            M('zhajinhua')->where(array('id'=>$game['id']))->save(
                array(
                    'update_time' => time(),
                    'total_credit' => $game['total_credit'] + $credit,
                    'last_user_id' => $game_user['user_id'],
                    'last_is_show' => $game_user['is_show'],
                    'last_credit' => $credit,
                    'next_user_id' => 0,
                    'total_jiaopai' => ($game['total_jiaopai'] + 1),
                    'credit_log' => json_encode($game_credit_log),
                )
            );

            $user_credit_log = $game_user['credit_log']?json_decode($game_user['credit_log']):array();
            array_push($user_credit_log, array('type'=>'bipai','credit'=>$credit, 'is_show'=>$game_user['is_show'], 'time'=>time(), 'total_jiaopai'=>$game_user['total_jiaopai']+1));
            // 更新参与用户信息
            M('zhajinhua_user')->where(array('id'=>$game_user['id']))->save(
                array(
                    'credit' => $game_user['credit'] + $credit,
                    'update_time' => time(),
                    'total_jiaopai' => ($game_user['total_jiaopai'] + 1),
                    'credit_log' => json_encode($user_credit_log)
                )
            );
            /////////////////////////////////////////////////////////////////////////////////

            $last_user = M('zhajinhua_user')->where(array('zha_id'=>$game['id'], 'user_id'=>$game['last_user_id']))->find();
            $last_card = json_decode($last_user['card_data']);
            $card = json_decode($game_user['card_data']);
            $cards = new cards();
            $bool = $cards->compareCards($card, $last_card);
            $last_user_info = M('qun_user')->find($last_user['user_id']);

            $next_user_info = M('qun_user')->find($next_user['user_id']);

            $command = '';
            if(!$next_user_info['is_show']){
                $command .= "【看牌】";
            }

            $user_list = M('zhajinhua_user')->where(array('zha_id'=>$game['id'],'status'=>1))->select();
            $kaipai_str = '';
            if(count($user_list) == 2){
                $kaipai_str .= "【开牌】";
            }elseif(count($user_list) < 2){
                $json['data']['message'] = "@{$this->user['nickname']} 没有其他说话用户 请说【开牌】结束游戏";
                break;
            }

            if($bool > 0){
                M('zhajinhua_user')->where(array('id'=>$last_user['id']))->save(array('status'=>3,'update_time'=>time(),'bipai_user_id'=>$this->user['id']));
                $json['data']['message'] = "@{$this->user['nickname']} 您的牌比【{$last_user_info['nickname']}】大， 接下来【{$next_user_info['nickname']}】说话,请选择$command【跟牌】【加+金币数】$kaipai_str【比牌】【弃牌】";
                break;
            }else{
                M('zhajinhua_user')->where(array('id'=>$game_user['id']))->save(array('status'=>3,'update_time'=>time(),'bipai_user_id'=>$this->user['id']));
                $json['data']['message'] = "@{$this->user['nickname']} 您的牌比【{$last_user_info['nickname']}】小， 接下来【{$next_user_info['nickname']}】说话,请选择$command【跟牌】【加+金币数】$kaipai_str【比牌】【弃牌】";
                break;
            }
        }while(false);
        echo json_encode($json);
        die();
    }
}