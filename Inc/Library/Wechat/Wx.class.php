<?php
/**
 * 微信服务器回调处理
 */
namespace Wechat;
class Wx
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;
            return true;
        }
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        //extract post data
        if (!empty($postStr)){
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
               the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $msgType = $postObj->MsgType;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);

            $time = time();

            $textTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            <FuncFlag>0</FuncFlag>
                            </xml>";
            $imageTpl = "<xml>
                         <ToUserName><![CDATA[%s]]></ToUserName>
                         <FromUserName><![CDATA[%s]]></FromUserName>
                         <CreateTime>%s</CreateTime>
                         <MsgType><![CDATA[%s]]></MsgType>
                         <ArticleCount>%s</ArticleCount>
                         <Articles>
                         %s
                         </Articles>
                         <FuncFlag>0</FuncFlag>
                         </xml>";
            $newsTpl = "<xml>
                         <ToUserName><![CDATA[%s]]></ToUserName>
                         <FromUserName><![CDATA[%s]]></FromUserName>
                         <CreateTime>%s</CreateTime>
                         <MsgType><![CDATA[%s]]></MsgType>
                         <ArticleCount>%s</ArticleCount>
                         <Articles>
                         %s
                         </Articles>
                         <FuncFlag>0</FuncFlag>
                         </xml>";
//            if(!empty( $keyword ))
//            {
//                $msgType = "text";
//                $contentStr = "Welcome to wechat world!";
//                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
//                echo $resultStr;
//            }else{
//                echo "Input something...";
//            }
            // 事件消息
            if ($msgType == 'event') {
                $event = $postObj->Event;
                session('openid', $fromUsername);
                cookie('openid',$fromUsername,array('expire'=>time()+2592000));
                // 用户关注
                if($event == 'subscribe'){
                    $user = M('user')->where("openid='$fromUsername'")->find();
                    if(! $user ){
                        M('user')->add(
                            array(
                                'openid' => $fromUsername,
                                'create_time' => time(),
                                'subscribe' => 1,
                                'subscribe_time'=>time()
                            )
                        );
                    }else{
                        M('user')->where("uin='{$user['uin']}'")->save(
                            array(
                                'openid' => $fromUsername,
                                'create_time' => time(),
                                'subscribe' => 1,
                                'subscribe_time'=>time()
                            )
                        );
                    }
                    $weixin = F('weixin','',CONF_PATH);
                    $contentStr = htmlspecialchars_decode($weixin['weixin_regMsg']);
                    if($contentStr){
                        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
                        echo $resultStr;
                    }else{
                        echo "";
                    }
                    exit;
                }elseif($event == 'unsubscribe'){
                    session('openid', '');
                    M('user')->where("openid='$fromUsername'")->save(array('subscribe'=>0));
                }
            // 普通消息处理
            }else{
                //
                if($keyword == 'test'){
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', '逗你玩');
                    echo $resultStr;
                    exit();
                }
            }
        }
        echo "";
        exit;
    }

    private function checkSignature()
    {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }


}
