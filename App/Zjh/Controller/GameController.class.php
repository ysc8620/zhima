<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Zjh\Controller;
use Think\Controller;
class GameController extends BaseController {
    public function index(){
        $this->title = "充值中心";

        $this->user = M('user')->find($this->user_id);
    	//首页幻灯片获取
    	$this->display();
    }

    public function detail(){
        $id = I('request.id','','strval');
        $game = M('zhajinhua')->where(array('number_no'=>$id))->find();
        if($game['win_user']){
            $game_win_user = M('qun_user')->find($game['win_user']);
            $game['win_user_name'] = $game_win_user['nickname'];
        }
        $this->game = $game;
        $this->user = M('user')->find($this->user_id);

        $game_user = M('zhajinhua_user')->where(array('zha_id'=>$this->game['id']))->select();
        foreach($game_user as $i=>$game){
            $cards = json_decode($game['card_data']);
            $card_str = '';
            foreach($cards as $card){
                $card_str .= $card[0].$card[1].',';
            }
            $game_user[$i]['card_info'] = trim($card_str,',');
            $game_user[$i]['user'] = M('qun_user')->find($game['user_id']);
        }


        $this->game_user = $game_user;
        // print_r($game_user);
        $this->title = "游戏详情";
        $this->display();
    }
}