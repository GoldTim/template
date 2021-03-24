<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

if (!function_exists('log_request')) {
    /**
     * 记录请求日志
     * @return array
     */
    function log_request()
    {
        $mongoLog = new \App\Foundation\MongoLog();
        return $mongoLog->request();
    }
}

if (!function_exists('getPostData')) {
    /**
     * 接收数据
     * @return mixed
     */
    function getPostData()
    {
        $postData = file_get_contents("php://input");
        return @json_decode($postData, true);
    }
}

if (!function_exists('send_response')) {
    /**
     * 返回响应信息
     * @param array $data 返回数据
     * @param int $code 返回码
     * @param string $msg 返回信息
     */
    function send_response($data = [], $code = 0, $msg = "success")
    {
        $response = [
            'error_code' => (int)$code,
            'message' => trim($msg),
            'data' => $data,
        ];

        //记录响应日志
//        if (config('params.mongo_log_switch')) {
//            $mongoLog = new \App\Foundation\MongoLog();
//            $mongoLog->response($response, $logId, $logDatabase);
//        }

        header('Content-Type: application/json; charset=utf-8', true);
        env('APP_ENV') == "production"? (!empty($code) ? Log::error($msg) : Log::info($msg)): Log::debug($msg);
        echo json_encode($response);
        exit;
    }
}

if (!function_exists('is_mobile')) {
    /**
     * 检查是否是手机号码
     * @param string $mobile
     * @return bool
     */
    function is_mobile(string $mobile)
    {
        if (strstr($mobile, '+86')) $mobile = substr($mobile, 3);
        if (strstr($mobile, '+886') || strstr($mobile, '+852')) $mobile = substr($mobile, 4);
        if(preg_match("/^1[34578]{1}\d{9}$/", $mobile) || preg_match("/^([9])\d{8}$/",$mobile) || preg_match("/^([2|3|5|6|9])\d{7}$/", $mobile)) return true;
        return false;
    }
}

if (!function_exists('remove_area_by_mobile')) {
    /**
     * 去掉手机号码区号
     * @param string $mobile
     * @return bool
     */
    function remove_area_by_mobile(string $mobile)
    {
        if (strstr($mobile, '+86')) $mobile = substr($mobile, 3);
        if (strstr($mobile, '+886') || strstr($mobile, '+852')) $mobile = substr($mobile, 4);
        return $mobile;
    }
}

if (!function_exists('is_email')) {
    /**
     * 检查是否是邮箱
     * @param string $email
     * @return bool
     */
    function is_email(string $email)
    {
        return !preg_match("/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/", $email) ? false : true;
    }
}

if (!function_exists('generateWechatSign')) {
    /**
     * 生成微信签名
     * @param array $params
     * @param $secretKey
     * @return string
     */
    function generateWechatSign(array $params, $secretKey)
    {
        //签名步骤一：按字典序排序参数
        ksort($params);
        //签名步骤二：在string后加入KEY
        $string = "";
        foreach ($params as $p => $param)
            if ($p != "sign" && $param != "" && !is_array($param)) $string .= $p . "=" . $param . "&";
        $string = trim($string, "&");
        $string = $string . "&key=" . $secretKey;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        return strtoupper($string);
    }
}

if (!function_exists('xmlToCollection')) {
    /**
     * xml转Collection
     * @param string $xml
     * @return Collection
     */
    function xmlToCollection(string $xml)
    {
        libxml_disable_entity_loader(true);
        //将xml数据转换为数组并再次转换为Laravel的Collection集合
        return collect(json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true))->map(function($item){
            return trim($item);
        });
//        return $list;
    }
}

if (!function_exists('arrayToXml')) {
    /**
     * 数组转xml
     * @param array $data
     * @return bool|string
     */
    function arrayToXml(array $data)
    {
        if (!is_array($data) || count($data) <= 0) return false;
        $requestXml = "";
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $requestXml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $requestXml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        return "<xml>".$requestXml."</xml>";
    }
}

if(!function_exists('getRequest')){
    function getRequest($url,$method="get",$main=[],$header=[])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        $data = curl_exec($curl);
        if (curl_error($curl)) return false;
        return $data;
    }
}
