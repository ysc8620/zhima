<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-4-13
 * Time: 下午10:15
 * To change this template use File | Settings | File Templates.
 */
class Cards
{
    // ♠♥♣♦
    public $suits = array('♠', '♥', '♣', '♦');
    public $figures = array('2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A');
    public $cards = array();
    public function __construct()
    {
        $cards = array();
        foreach($this->suits as $suit){
            foreach($this->figures as $figure){
                $cards[] = array($suit,$figure);
            }
        }
        $this->cards = $cards;
    }
    public function getCard()
    {
        shuffle($this->cards);
        //生成3张牌
        return array(array_pop($this->cards), array_pop($this->cards), array_pop($this->cards));

    }
    public function compareCards($card1,$card2)
    {
        $score1 = $this->ownScore($card1);
        $score2 = $this->ownScore($card2);
        if($score1 > $score2) return 1;
        elseif($score1 < $score2) return -1;
        return 0;
    }


    private function ownScore($card)
    {
        $suit = $figure = array();
        foreach($card as $v){
            $suit[] = $v[0];
            $figure[] = array_search($v[1],$this->figures)+2;
        }
        //补齐前导0
        for($i = 0; $i < 3; $i++){
            $figure[$i] = str_pad($figure[$i],2,'0',STR_PAD_LEFT);
        }
        rsort($figure);
        //对于对子做特殊处理
        if($figure[1] == $figure[2]){
            $temp = $figure[0];
            $figure[0] = $figure[2];
            $figure[2] = $temp;
        }
        $score = $figure[0].$figure[1].$figure[2];
        //筒子 60*100000
        if($figure[0] == $figure[1] && $figure[0] == $figure[2]){
            $score += 60*100000;
        }
        //金花 30*100000
        if($suit[0] == $suit[1] && $suit[0] == $suit[2]){
            $score += 30*100000;
        }
        //顺子 20*100000
        if($figure[0] == $figure[1]+1 && $figure[1] == $figure[2]+1 || implode($figure) =='140302'){
            $score += 20*100000;
        }
        //对子 10*100000
        if($figure[0] == $figure[1] && $figure[1] != $figure[2]){

            $score += 10*100000;
        }
        return $score;
    }
}

/*
//test
$playCard = new Cards();
$card1 = $playCard->getCard();
$card2 = $playCard->getCard();
$result = $playCard->compareCards($card1,$card2);

echo 'card1 is ',printCard($card1),"\r\n";
echo 'card2 is ',printCard($card2),"\r\n";
$str = 'card1 equit card2';
if($result == 1) $str =  'card1 is larger than card2';
elseif($result == -1) $str = 'card1 is smaller than card2';
echo $str;


function printCard($card)
{
    $str = '(';
    foreach($card as $v){
        $str .= $v[0].$v[1].',';
    }
    return trim($str,',').')';
}
*/