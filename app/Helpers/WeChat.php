<?php
namespace App\Helpers;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionMethod;

class WeChat{
    protected $appId;
    protected $secret;
    protected $mchId;
    protected $charset = "utf-8";
    protected $getAccessTokenUrl = "https://api.weixin.qq.com/sns/oauth2/access_token";
    protected $getOpenIdUrl = "https://api.weixin.qq.com/sns/jscode2session";
    protected $refundUrl='https://api.mch.weixin.qq.com/secapi/pay/refund';
    protected $enquiryUrl = "https://api.mch.weixin.qq.com/pay/orderquery";
    protected $createUrl="https://api.mch.weixin.qq.com/pay/unifiedorder";
    protected $methodArray = [
        "pay","view","refund","checkOrder"
    ];

    /*
     *
     */
    public function __construct($typeName)
    {
        $this->getWeChatInfo($typeName);
    }

    /**
     * @param $mainArray
     * @return mixed
     * @throws ReflectionException|Exception
     */
    public function Implement($mainArray)
    {
        if (!in_array($mainArray['type'], $this->methodArray)) throw new Exception("操作类型不存在");
        $method = new ReflectionMethod(get_called_class(), ucfirst($mainArray['type']));
        $method->setAccessible(true);
        return $method->invoke($this, $mainArray['data']);
    }

    /**
     * 设置微信值
     * @param $typeName
     * @throws Exception
     */
    private function getWeChatInfo($typeName){
        if (!Schema::hasTable("system")) throw new Exception("获取系统微信信息失败");
        if (!Schema::hasColumn("system", "weChatInfo")) throw new Exception("获取系统微信信息失败");
        if (!$info = DB::table("system")->select("weChatInfo")->first()) throw new Exception("获取系统微信信息失败");
        $weChatInfo = json_decode($info->weChatInfo,true);
        $this->mchId = $weChatInfo['mchId'];
        $this->appId = $weChatInfo['appId'];
        $this->secret = $weChatInfo['secret'];
        if ($typeName === 'Procedure') {
            $this->appId = $weChatInfo['procedure'];
            $this->secret = $weChatInfo['procedureSecret'];
        } elseif ($typeName === 'Application') {
            $this->appId = $weChatInfo['application'];
        } elseif ($typeName === 'JsApi') {
            $this->secret = $weChatInfo['pubSecret'];
        }
    }

    private function Pay($mainArray)
    {
        $params = [
            "appid" => $this->appId,
            "mch_id" => $this->mchId,
            "nonce_str" => Str::random(32),
            "sign_type" => "MD5",
            "fee_type" => "CNY",
            "spbill_create_ip" => $_SERVER['REMOTE_ADDR'],
            "time_start" => date("YmdHis"),
            "time_expire" => date("YmdHis", time() + 1800),
            "notify_url" => $mainArray['notify_url'],
            "limit_pay" => "no_credit",
            "trade_type" => $mainArray['trade_type']
        ];
//        dd(date("YmdHis", time() + 1800));
        $params = array_merge($params, $mainArray);
        $params['sign'] = $this->makeSign($params);
        $result = sendRequest($this->createUrl, "POST", ["body" => arrayToXml($params)], ["Content-Type" => "application/xml"]);
        if ($result['status'] !== true || $result['message'] !== 'SUCCESS') throw new Exception("发送请求失败");//发送请求失败
        $xmlResult = xmlToCollection($result['result'])->toArray();
        if ($xmlResult['return_code'] === 'FAIL' || $xmlResult['return_code'] !== 'SUCCESS' || $xmlResult['return_msg'] !== 'OK') throw new Exception($xmlResult['return_msg']);//发起支付失败
        return $xmlResult;
    }

    private function View($mainArray)
    {
        $params = [
            "appid" => $this->appId,
            "mch_id" => $this->mchId,
            "out_trade_no" => $mainArray['orderNum'],
            "nonce_str" => md5("checkOrder" . time())
        ];
        $params['sign'] = $this->makeSign($params);
        $result = getRequest($this->enquiryUrl, "post", [
            "body" => arrayToXml($params)
        ], ["headers" => ["Content-Type" => "application/xml"]]);
        return $result['trade_state'] !== "SUCCESS" ? false : true;
    }

    private function Refund($mainArray)
    {
        $params = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => md5("Refund" . time()),
            'out_trade_no'=>$mainArray['out_trade_no'],
            'out_refund_no'=>$mainArray['out_refund_no'],
            'total_fee'=>$mainArray['total_fee'],
            'refund_fee'=>$mainArray['refund_fee']
        ];
        $params['sign'] = $this->makeSign($params);
        return sendRequest($this->refundUrl,"POST",[
            "body"=>arrayToXml($params),
            "cert"=>[
                $mainArray['cert'],
                $mainArray['key']
            ],[]
        ],[
            "Content-Type"=>"application/xml"
        ]);
    }

    private function CheckOrder($mainArray)
    {

    }

    /**
     * 获取用户OpenId
     * @param $mainArray
     * @return mixed
     * @throws Exception
     */
    private function getOpenId($mainArray){
        $params = [
            'appid' => $this->appId,
            'secret' => $this->secret,
            'grant_type' => 'authorization_code',
        ];
        if ($mainArray['type'] === 'weChatJsApi') {
            $url = $this->getAccessTokenUrl;
            $params['code'] = $mainArray['code'];
        } else {
            $url = $this->getOpenIdUrl;
            $params['js_code'] = $mainArray['code'];
        }
        $result = sendRequest($url . '?' . $this->urlEncode($params),"GET");
        if (!$result['status'] || $result['message'] === 'ERROR') throw new Exception("请求失败");
        $result = $result['result'];
        if (empty($result['openid'])) throw new Exception("获取OpenId失败");
        return $result['openid'];
    }

    /**
     * 获取全局Token
     * @param $mainArray
     * @return mixed
     * @throws Exception
     */
    private function getAccessToken(){
        $params = [
            'appid' => $this->appId,
            'secret' => $this->secret,
            'grant_type' => 'client_credential'
        ];
        $result = sendRequest($this->getAccessTokenUrl . '?' . $this->urlEncode($params),"GET");
        if (!$result['status'] || $result['message'] === 'ERROR') throw new Exception("请求失败");
        if (empty($result['result']['openid'])) throw new Exception("获取全局AccessToken失败");
        return $result['result']['access_token'];
    }

    private function decryptData($mainArray){
        if (strlen($mainArray['sessionKey']) != 24) return false;
        $aesKey = base64_decode($mainArray['sessionKey']);
        if (strlen($mainArray['iv']) != 24) return false;
        $aesIv = base64_decode($mainArray['iv']);
        $aesCipher = base64_decode($mainArray['encrypt']);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIv);
        $dataObj = json_decode($result);
        return $dataObj === NULL ? false : ($dataObj->watermark->appid != $this->appId ? false : json_decode($result));
    }

    public function verification($params){
        $sign = $this->makeSign($params);
        if (!$sign || $sign !== strtoupper($params['sign'])) return "sign error";
        if ($this->mchId != $params['mch_id']) return "mch_id error";
        if ($params['return_code'] !== "SUCCESS") return "result error";
        return "success";
    }


    private function urlEncode($params){
        ksort($params);
        $str = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false == $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                $v = self::charAct($v, $this->charset);
                if ($i == 0) $str .= $k . "=" . urlencode($v);
                else $str .= "&" . $k . "=" . urlencode($v);
                $i++;
            }
        }
        unset($k, $v);
        return $str;
    }

    public function makeSign($params){
        ksort($params);
        $string = "";
        foreach ($params as $p => $param)
            if ($p != "sign" && $param !=  "" && !is_array($param)) $string .= $p . "=" . $param . "&";
        return strtoupper(md5(trim($string, "&") . "&key=" . $this->secret));
    }

    private function checkEmpty($value){
        if (!isset($value)) return true;
        if ($value === null) return true;
        if (trim($value) === "") return true;
        return false;
    }

    private function charAct($data,$target){
        if (empty($data)) return $data;
        if (strcasecmp($this->charset, $target) != 0) $data = mb_convert_encoding($data, $target, $this->charset);
        return $data;
    }
}
