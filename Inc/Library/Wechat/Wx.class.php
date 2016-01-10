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
                    f_log("fromUserName=$fromUsername&11111", ROOT_PATH.'/weixin_api.log');
//
                      $user = M('user')->where("openid='$fromUsername'")->find();
//
//
//
//                    f_log("fromUserName=$fromUsername&11111", ROOT_PATH.'/weixin_api.log');
//                    if(! $user ){
//                        f_log("fromUserName=$fromUsername&2222", ROOT_PATH.'/weixin_api.log');
//                        M('user')->add(
//                            array(
//                                'openid' => $fromUsername,
//                                'create_time' => time(),
//                                'subscribe' => 1,
//                                'subscribe_time'=>time()
//                            )
//                        );
//                        f_log("fromUserName=$fromUsername&3333", ROOT_PATH.'/weixin_api.log');
//                    }else{
//                        f_log("fromUserName=$fromUsername&4444", ROOT_PATH.'/weixin_api.log');
//                        M('user')->where(array('uin'=>$user['uin']))->save(
//                            array(
//                                'openid' => $fromUsername,
//                                'create_time' => time(),
//                                'subscribe' => 1,
//                                'subscribe_time'=>time()
//                            )
//                        );
//                        f_log("fromUserName=$fromUsername&5555", ROOT_PATH.'/weixin_api.log');
//                    }
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
                    f_log("fromUserName=$fromUsername&unsubscribe", ROOT_PATH.'/weixin_api.log');
                    session('openid', '');
                    M('user')->where("openid='$fromUsername'")->save(array('subscribe'=>0));
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
