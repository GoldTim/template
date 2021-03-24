<?php
namespace App\Helpers;
use Exception;
use ReflectionException;
use ReflectionMethod;

class AliPay{
    private static $appId;//应用ID
    private static $rsaPrivateKeyFilePath;//私钥文件路径
    private static $privateKey;//私钥值
    private static $gatewayUrl = 'https://openapi.alipay.com/gateway.do';//网关
    private static $format = "JSON";//返回数据格式
    private static $apiVersion = "1.0";
    private static $postCharset = "utf-8";
    private static $fileCharset = "utf-8";
    private static $signType = "RSA2";//签名类型
    private static $checkTransferStatus = [
        'SUCCESS'=>'成功',
        "DEALING" => "处理中",
        "INIT" => "待处理",
        "UNKNOWN" => "状态未知"
    ];
    protected static $method = [
        'pay',
        'checkTransfer',
        'transfer',
        'refund',
        "payByApp",
        "checkOrder"
    ];
    public function __construct($appId,$privateKey)
    {
        self::$appId=$appId;
        self::$privateKey=$privateKey;
    }
    /**
     * 统一执行方法
     * @param $type
     * @param $data
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     */
    static function Implement($type,$data)
    {
        if (!in_array($type, self::$method)) throw new Exception("准备执行的方法不存在");
        $method = new ReflectionMethod(get_called_class(), $type);
        $method->setAccessible(true);
        return $method->invoke(null, $data);
    }


    private static function pay($data){
        return getRequest(self::$gatewayUrl . '?' . self::Assemble($data['method'], $data['capitalJson'], $data['mainArray']));
    }

    private static function payByApp($data)
    {
        return self::Assemble($data['method'],$data['capitalJson'],$data['mainArray']);
    }

    private static function checkTransfer($capitalArray)
    {
        $result = json_decode(getRequest(self::$gatewayUrl . '?' . self::Assemble('', $capitalArray)), true);
        if (!$msg = self::$checkTransferStatus[$result['status']]) $msg = $result['fail_reason'];
        return $msg;
    }

    private static function checkOrder($capital)
    {

    }
    private static function Assemble($method,$bitArray=[],$mainArray=[]){
        $capitalArray = [
            'app_id' => self::$appId,
            'method' => $method,
            'format' => self::$format,
            "charset" => self::$postCharset,
            "sign_type" => self::$signType,
            "timestamp" => date("Y-m-d H:i:s"),
            "version" => self::$apiVersion,
            "biz_content" => $bitArray
        ];
        if (isset($mainArray['notify_url'])) $capitalArray["notify_url"] = $mainArray['notify_url'];
        ksort($capitalArray);
        $capitalArray['sign'] = self::generateSign($capitalArray, self::$signType);
        foreach ($capitalArray as &$v)
            $v = self::charset($v, $capitalArray['charset']);
        return http_build_query($capitalArray);
    }

    private static function generateSign($params, $signType = "RSA"){
        ksort($params);
        $string = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === self::checkEmpty($v) && "@" !== substr($v, 0, 1)) {
                $string .= $i == 0 ? "$k" . "=" . "$v" : "&" . "$k" . "=" . "$v";
                $i++;
            }
        }
        unset($k, $v);
        $res = self::checkEmpty(self::$rsaPrivateKeyFilePath)
            ? "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap(self::$privateKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----"
            : openssl_get_privatekey(file_get_contents(self::$rsaPrivateKeyFilePath));
        ($res) or die("您使用的私钥格式错误，请检查RSA私钥配置");
        $signType == "RSA2" ? openssl_sign($string, $sign, $res, OPENSSL_ALGO_SHA256) : openssl_sign($string, $sign, $res);
        self::checkEmpty(self::$rsaPrivateKeyFilePath) ?: openssl_free_key($res);
        return base64_encode($sign);
    }

    static function getSignContentUrlEncode($params){
        ksort($params);
        $strToBeSignEd ="";
        $i = 0;
        foreach($params as $k=>$v) {
            if (false === self::checkEmpty($v) && "@" != substr($v, 0, 1)) {
                $v = self::charset($v, self::$postCharset);
                $strToBeSignEd .= ($i == 0 ?: "&") . "$k" . "=" . "$v";
                $i++;
            }
        }
    }

    /**
     * 检查是否为空
     * @param $value
     * @return bool
     */
    private static function checkEmpty($value)
    {
        if (!isset($value)) return true;
        if ($value === null) return true;
        if (trim($value) === "") return true;
        return false;
    }

    /**
     * 设置字符集
     * @param $data
     * @param $targetCharset
     * @return null|string|string[]
     */
    private static function charset($data, $targetCharset)
    {
        if (!empty($data)) {
            $fileType = self::$fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0)
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
        }
        return $data;
    }
}
