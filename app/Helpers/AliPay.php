<?php

namespace App\Helpers;

use Exception;
use ReflectionException;
use ReflectionMethod;

class AliPay
{
    private static $appId = "2018121362561267";//应用ID
    private static $rsaPrivateKeyFilePath;//私钥文件路径
    private static $privateKey = "MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDROJRhbGHzoUEO7Vt5vPm6rrxaPAJb7TfxRzjRoLagaEvJi1Lrb28p6QBOqV4WL/C/oA79hL1F7h+amptwlE+GPE8jLyf5EHQ+c4da3grQr+d7trXPsQq3lpWcf5EYFGbW8sY1YBYE2/InvCjouM/2BBwxFvNH/gQIkz4zH0hWdjMt2MFvpZ25ofUhjx5n8Fv/aFcgsINUPzU2KTUZFAX809ODYMOtxfgt8ew1cPsPtClHNLLuHzjAAH+eS9FEtoIo2JE9ahbTdvgNBHhk4BxjO08ZZ7X+BDlYPaeVts7Iy8uNBTt5wikzOUhYDSvGOjPChVFMjxn/QfpfK/+vlKfxAgMBAAECggEANRvjrodQpgN8/EkMO27ZBaZtcYpiHuusk7a8mZnuQfG3q0WOmu0CjuTyiwU7OO6ackozA++6DBJSO3CbnIxJU43jmM7TmsmueFvjNBHBNRAx9pl+tWX/jnLPL5bCQhaLtGyUG+B0Nm+OnL+KsuiXAaAxd9SXlOLKK1MVKuz589g/N2C/I8yPZjhowJVFbLXvGXV3twoFSQ+DRp29YRqkNsUf2JIL22k1UQ/TJRjOxvTCQbnI115O2H56+9I1aWJ0Lcwc14diZ/eovOT1dUIf92StGYmZ3xiBbCizl582ZFazqvia7jty83DB9xqeUeAjLNdsieU3eH5skIQzy24tcQKBgQD/11gPuMRSpzcELiElG5PxxQHlx/TaJkcWIw199yCsVrNK3TTjg7nvBbmDB+/Z3K//7LZ/j5h+KS+ynyuwMtg2qfTIEwW0x1VVXRrGeJldPqp3ZkMXqqF/+rKRNIpzsnOlQNJnXC8ORR+40TNH1ZNa4+QRvjtuzKArBDFuyJHxhwKBgQDRWdPAmhPnAU8SyjglN4Eahqfq2E5mGcR5GCPXKHmT20nbeUUbHB6dGKE7nTlsDTrNWszTg1klN8HKMPcdoQsU5OEJbsY5EBm+7nyGXuUatfHXyfAPonHrdaO2ab9gJD0Q6bWJ1rCxsOuey0oUf3qiv9AxMZnBwvH/YhtuQOjYxwKBgQClmAm8q1gPM4Itp3n2ncIFhAF1bBY0uP/b+TY7aSBxy/BirYkVFebcKfVoNVPuzKPyX5HEQPpv9lKUJ+hMNKyzvQ/eDEnuN/MovImfGuIRc4U8oSkeAWhlAKxhxgMzXbbyqGFHW2htsRoWMvohLcEh3E17moi3b6TgEue4EAQ+swKBgEV6YFJNUEmcH5gG0LdZQlmBUv0XqH0uFAx0PIDNh/vQDSTvjEXBAU/1upzEQyhfA6LffZa8wrsdVA08TubgaYMXqq+sudB6TXEWSPF3UOWaeJa7CBbIPLJ+KkUBt2e63yFbzsneHGn8Y1Yh9YX0AMk+i2OoKHUrs5CkCKKAnEZFAoGAC4fjIkrvCO2cStTQjhIr1gPFBdRctT/3iH6EGUBmIDZc4yz6qDvIsyLhLbaakLjSl9or9MlIaOpCSYBb0jZsKSgthkyiSoye/1C1hJQKPHt7Q+o6BWxBbEY+ZcZ1IBb0zB+vrzN9Z6RWjB8N5DwQ2/lGa1cWRRHsjafcn/+OU3Y=";//私钥值
    private static $gatewayUrl = 'https://openapi.alipay.com/gateway.do';//网关
    private static $format = "JSON";//返回数据格式
    private static $apiVersion = "1.0";
    private static $postCharset = "utf-8";
    private static $fileCharset = "utf-8";
    private static $signType = "RSA2";//签名类型
    private static $checkTransferStatus = [
        'SUCCESS' => '成功',
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

    public function __construct($appId, $privateKey)
    {
        self::$appId = $appId;
        self::$privateKey = $privateKey;
    }

    /**
     * 统一执行方法
     * @param $type
     * @param $data
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     */
    static function Implement($type, $data)
    {
        if (!in_array($type, self::$method)) throw new Exception("准备执行的方法不存在");
        $method = new ReflectionMethod(get_called_class(), $type);
        $method->setAccessible(true);
        return $method->invoke(null, $data);
    }


    private static function pay($data)
    {
        return getRequest(self::$gatewayUrl . '?' . self::Assemble($data['method'], $data['capitalJson'], $data['mainArray']));
    }

    private static function payByApp($data)
    {
        return self::Assemble($data['method'], $data['capitalJson'], $data['mainArray']);
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

    private static function Assemble($method, $bitArray = [], $mainArray = [])
    {
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

    private static function generateSign($params, $signType = "RSA")
    {
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

    static function getSignContentUrlEncode($params)
    {
        ksort($params);
        $strToBeSignEd = "";
        $i = 0;
        foreach ($params as $k => $v) {
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
