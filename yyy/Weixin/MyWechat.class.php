<?php
require_once dirname(__FILE__) . "/Wechat.class.php";
require_once dirname(__FILE__) . "/File.class.php";
/**
 * 随机字符
 * @param number $length 长度
 * @param string $type 类型
 * @param number $convert 转换大小写
 * @return string
 */
function random($length = 6, $type = 'string', $convert = 0) {
    $config = array(
        'number' => '1234567890',
        'letter' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'string' => 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',
        'all' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
    );

    if (!isset($config[$type]))
        $type = 'string';
    $string = $config[$type];

    $code = '';
    $strlen = strlen($string) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $string{mt_rand(0, $strlen)};
    }
    if (!empty($convert)) {
        $code = ($convert > 0) ? strtoupper($code) : strtolower($code);
    }
    return $code;
}

class MyWechat extends Wechat
{

    /**
     * 设置缓存，按需重载
     * @param string $cachename
     * @param mixed $value
     * @param int $expired
     * @return boolean
     */
    protected function setCache($cachename, $value, $expired)
    {
        $file = $cachename.".json";
        return File::write_file($cachename, json_encode($value),'w+');
    }

    /**
     * 获取缓存，按需重载
     * @param string $cachename
     * @return mixed
     */
    protected function getCache($cachename)
    {
        $file = $cachename.".json";
        if(file_exists($file)){
            return json_decode(File::read_file($cachename),true);
        }else{
            return [];
        }

    }

    /**
     * 清除缓存，按需重载
     * @param string $cachename
     * @return boolean
     */
    protected function removeCache($cachename)
    {
        $file = $cachename.".json";
        if(file_exists($file)){
            return unlink($file);
        }else{
            return true;
        }

    }

    /**
     * 微信企业付款、发红包
     *
     * 集合了下面几个方法，有特殊要求再单独使用以下几个方法
     *
     * @param string $url 接口URL
     * @param array $data 要提交的数据
     * @param string $payKey 支付密钥
     * @param int $city_id 城市id
     * @return array|bool
     */
    public static function pay($url, $data, $payKey, $city_id)
    {
        $data['nonce_str'] = random(32);
        $data['spbill_create_ip'] = empty($_SERVER['SERVER_ADDR'])?'127.0.0.1':$_SERVER['SERVER_ADDR'];
        $sign = self::getSign($data, $payKey);
        $data['sign'] = $sign;
        $xml = self::arrayToXml($data);
        $response = self::curl_post_ssl($url, $xml, $city_id);
        if ($response) {
            $returnData = self::xmlProcess($response);
        } else {
            $returnData = false;
        }
        return $returnData;
    }

    /**
     * 生成签名
     *
     * @see pay() 已在该方法使用
     *
     * @param $data
     * @param $payKey
     * @return string
     */
    public static function getSign($data, $payKey)
    {
        ksort($data);
        $stringA = '';
        foreach ($data as $key => $value) {
            if (strlen($stringA) == 0)
                $stringA .= $key . "=" . $value;
            else
                $stringA .= "&" . $key . "=" . $value;
        }
        $signTemplate = $stringA . '&key=' . $payKey;
        return strtoupper(md5($signTemplate));
    }

    /**
     * 数组转xml
     *
     * @see pay() 已在该方法使用
     *
     * @param $array
     * @return string
     */
    public static function arrayToXml($array)
    {
        $xml = "<xml>";
        foreach ($array as $key => $value) {
            $xml .= "<{$key}><![CDATA[{$value}]]></{$key}>";
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 将带有CDATA的XML转换成数组
     *
     * @see pay() 已在该方法使用
     *
     * @param $xml
     * @return array
     */
    public static function xmlProcess($xml)
    {
        libxml_disable_entity_loader(true);
        $array = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $array;
    }

    /**
     * 发送微信支付请求
     *
     * @see pay() 已在该方法使用
     *
     * @param $url
     * @param $data
     * @param $city_id
     * @param int $second
     * @param array $aHeader
     * @return bool|mixed
     */
    public static function curl_post_ssl($url, $data, $city_id, $second = 30, $aHeader = [])
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $path = __DIR__ . '/zhengshu_' . $city_id;
        curl_setopt($ch, CURLOPT_SSLCERT, $path . '/apiclient_cert.pem');
        curl_setopt($ch, CURLOPT_SSLKEY, $path . '/apiclient_key.pem');
        curl_setopt($ch, CURLOPT_CAINFO, $path . '/rootca.pem');

        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        if ($result) {
            curl_close($ch);
            return $result;
        } else {
            curl_close($ch);
            return false;
        }
    }
}