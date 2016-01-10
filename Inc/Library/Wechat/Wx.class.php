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
    {f_log("fromUserName=&0000=====" , ROOT_PATH.'/weixin_api.log');
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        //extract post data
        if (!empty($postStr)){
            f_log("fromUserName=&0000" , ROOT_PATH.'/weixin_api.log');
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
               the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $msgType = $postObj->MsgType;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);

            $time = time();

            f_log("fromUserName=$fromUsername&8888" , ROOT_PATH.'/weixin_api.log');
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
            f_log("fromUserName=$fromUsername&9999" , ROOT_PATH.'/weixin_api.log');
//            if(!empty( $keyword ))
//            {
//                $msgType = "text";
//                $contentStr = "Welcome to wechat world!";
//                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
//                echo $resultStr;
//            }else{
//                echo "Input something...";
//            }
            f_log("fromUserName=$fromUsername&55555" , ROOT_PATH.'/weixin_api.log');
            // 事件消息
            if ($msgType == 'event') {
                $event = $postObj->Event;
                f_log("fromUserName=$fromUsername&66666" , ROOT_PATH.'/weixin_api.log');
                session('openid', $fromUsername);
                cookie('openid',$fromUsername,array('expire'=>time()+2592000));
                f_log("fromUserName=$fromUsername&77777" , ROOT_PATH.'/weixin_api.log');
                // 用户关注
                if($event == 'subscribe'){
                    f_log("fromUserName=$fromUsername&wwwwww");
                    $user = M('user')->where(array('openid'=>$fromUsername))->find();
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
                        M('user')->where(array('uin'=>$user['uin']))->save(
                            array(
                                'openid' => $fromUsername,
                                'create_time' => time(),
                                'subscribe' => 1,
                                'subscribe_time'=>time()
                            )
                        );
                    }
                    f_log("fromUserName=$fromUsername&11111111111111" , ROOT_PATH.'/weixin_api.log');
                    $weixin = F('weixin','',CONF_PATH);
                    $contentStr = htmlspecialchars_decode($weixin['weixin_regMsg']);
                    if($contentStr){
                        f_log("233333333333$contentStr" , ROOT_PATH.'/weixin_api.log');
                        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
                        echo $resultStr;
                    }else{
                        f_log("4444444444444&errorrr" , ROOT_PATH.'/weixin_api.log');
                        echo "";
                    }
                    exit;
                }elseif($event == 'unsubscribe'){
                    M('user')->where(array('openid'=>$fromUsername))->save(array('subscribe'=>0));
                    session('openid', '');
                }
            // 普通消息处理
            }else{
                //
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
