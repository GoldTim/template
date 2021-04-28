<?php
namespace App\Helpers;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionMethod;

class Order
{
    /**
     * 创建订单
     * @param $data
     * @return array
     * @throws Exception
     */
    public function createOrder($data)
    {
        $masterArray = [
            'orderMsn' => $this->getMsn(),
            'actualAmount' => collect($data)->pluck('order')->sum('orderAmount'),
            'uniqueKey' => Str::random(100)
        ];
        if (!DB::table("orderMaster")->insertGetId($masterArray)) throw new Exception("提交订单付费信息失败");

        foreach ($data as $item) {
            $orderArray = array_merge($item['order'], [
                'orderSn' => $this->getSn(),
                'paySn' => $masterArray['orderMsn'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            if (!DB::table('orderInfo')->insert($orderArray)) throw new Exception("提交订单失败");
            foreach ($item['detail'] as $key => $value) {
                $detailArray = array_merge($value, ['orderSn' => $orderArray['orderSn']]);
                if (!DB::table('orderDetail')->insert($detailArray)) throw new Exception("提交订单产品失败");
            }
        }
        return [
            'orderSn' => $masterArray['orderMsn']
        ];
    }

    /**
     * 获取订单详情
     * @param $data
     * @throws Exception
     * @return array
     */
    public function detailOrder($data)
    {
        $order = $this->setOrder($data);
        $detail = DB::table("orderDetail")->where('orderSn', $order->orderSn)->get([
            "stockId", "name", "skuId", "colorId", "skuName", "cover", "quantity", "amount", "tAmount", "discount", "tDiscount"
        ]);
        $shipInfo = DB::table("orderShipping")->where("orderSn", $order->orderSn)->first([
            "shippingSn", "type", "userName", "phone", "province", "city", "address", "email", "status"
        ]);
        return [
            "order" => $order->toArray(),
            "detail" => $detail->toArray(),
            "shipInfo" => $shipInfo->toArray()
        ];
    }

    /**
     * 订单确认收货
     * @param $data
     * @return array
     * @throws Exception
     */
    public function confirm($data)
    {
        $order = $this->setOrder($data);
        return $this->updateOrderStatus($order,"已完成");
    }

    public function Refund($data)
    {

    }

    /**
     * 取消订单
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function cancel($data)
    {
        $order = $this->setOrder($data);
        return $this->updateOrderStatus($order, '取消订单');
    }

    /**
     * 删除订单
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function delOrder($data)
    {
        $order = $this->setOrder($data);
        return $this->updateOrderStatus($order, '删除订单');
    }

    /**
     * 处理订单超时
     * @throws ReflectionException
     */
    public function overdueOrder()
    {
        $list = DB::table('orderInfo')->where('status', array_search('未支付', config('params.orderStatus')))
            ->whereRaw(Carbon::now() . '> SUBDATE(created_at,interval - 15 minute)')->get(['orderSn', 'payMethod', 'customer']);
        $orderList = [];
        foreach ($list as $item) {
            if ($item->payMethod && $this->checkOrder(["orderSn" => $item->orderSn, 'customer' => $item->customer])) continue;
            $orderList[] = $item->orderSn;
        }
        DB::table('orderSn')->whereIn('orderSn', $orderList)->update([
            'status' => array_search('订单超时', config('params.status'))
        ]);
    }

    /**
     * 检查订单是否支付
     * @param $search
     * @return bool
     * @throws ReflectionException
     * @throws Exception
     */
    public function checkOrder($search)
    {
        $order = $this->setOrder($search);
        $payStatus = false;
        if (strpos(config('params.payType.' . $order->payMethod), 'aliPay') !== false) {
            $params = [
                "capitalJson" => [
                    "out_trade_no" => $order->paySn
                ],
                "method" => "alipay.trade.query"
            ];
            $result = AliPay::Implement("checkOrder", $params);
            if ($result['code'] == 10000 && $result['msg'] == 'Success' && $result['trade_status'] == 'TRADE_SUCCESS' && config('params.orderStatus.' . $order->status) == '未支付')
                $payStatus = true;
        } elseif (strpos(config('params.payType.' . $order->payMethod), 'weChat')) {
            $params = [
                "type" => "view",
                "data" => [
                    "orderNum" => $order->paySn
                ]
            ];
            $weChat = new WeChat("");
            $result = $weChat->Implement($params);
            if ($result) $payStatus = true;
        }
        if ($payStatus) $this->notify($order->paySn);
        return $payStatus;
    }

    /**
     * 订单通知修改状态
     * @param $orderSn
     * @return array
     * @throws Exception
     */
    public function notify($orderSn)
    {
        if (!$orderList = DB::table("orderInfo")->where('paySn', $orderSn)->get(['orderSn', 'status'])) throw new Exception("订单不存在");
        foreach ($orderList as $item) {
            if (config('params.orderStatus.' . $item->status) === '未支付')
                if (!DB::table("orderInfo")->where('orderSn', $item->orderSn)->update([
                    "status" => array_search("已支付", config("params.orderStatus"))
                ])) throw new Exception("修改订单状态失败");
        }
        return [];
    }

    protected function updateOrderStatus($order, $type)
    {
        return $order->update(['status' => array_search($type, config('params.orderStatus'))]);
    }


    /**
     * @param $data
     * @return mixed
     * @throws ReflectionException
     */
    public function Pay($data)
    {
        $method = new ReflectionMethod(get_called_class(), "payBy" . ucfirst($data['payName']));
        $method->setAccessible(true);
        return $method->invoke($this, $data);
    }

    private function payByScanCode($data)
    {
        $codeType = substr($data['code'], 0, 2);
        $payType = $codeType >= 10 && $codeType <= 15 ? "alipay" : ($codeType >= 25 && $codeType <= 30 ? "weChat" : "");
        if ($codeType >= 10 && $codeType <= 16) {
            $orderData = [
                "body" => !empty($data['orderName']) ? $data['orderName'] : $data['orderSn'],
                "out_trade_no" => $data['orderSn'],
                "total_fee" => env("APP_ENV") == 'production' ? $data['actualAmount'] * 100 : 1,
                "auth_code" => $data['code']
            ];
            $weChat = new WeChat("microPay");
            $weResult = $weChat->Implement([
                "type" => "microPay",
                "data" => $orderData
            ]);
        } elseif ($codeType >= 25 && $codeType <= 30) {
            $params = [
                "capitalJson" => collect([
                    "out_trade_no" => $data['orderSn'],
                    "scene" => "bar_code",
                    "auth_code" => $data['code'],
                    "subject" => !empty($data['orderName']) ? $data['orderName'] : $data['orderSn'],
                    "total_amount" => env('APP_ENV') == 'production' ? $data['actualAmount'] : 0.01,
                    "timeout_express" => "15m",
                ])->toJson(),
                "method" => "alipay.trade.pay",
                "mainArray" => [
                    "notify_url"
                ]
            ];
            $result = json_decode(AliPay::Implement("pay", $params), true)['alipay_trade_pay_response'];
        }
        if(!$this->checkOrder(['paySn' => $data['orderSn']])) throw new Exception("");//轮询支付
    }

    /**
     * 微信PC扫码支付
     * @param $data
     * @return array
     * @throws ReflectionException
     */
    private function payByWeChatNative($data)
    {
        $orderData = [
            'body' => !empty($data['orderName']) ? $data['orderName'] : $data['orderSn'],
            'out_trade_no' => $data['orderSn'],
            'trade_type' => "NATIVE",
            "total_fee" => env('APP_ENV') == 'production' ? $data['actualAmount'] * 100 : 1,
            'product_id' => 1,
            'notify_url' => $data['notifyUrl']
        ];
        $weChat = new WeChat("native");
        $weResult = $weChat->Implement([
            "type" => "pay",
            "data" => $orderData
        ]);
        $weResult = xmlToCollection($weResult);
        $result = [
            'appId' => $weResult->get('appid'),
            'code_url' => $weResult->get('code_url'),
            'nonceStr' => md5("Pay" . time()),
            'package' => "Sign=WXPay",
            "partnerid" => $weResult->get('mch_id'),
            'prepayid' => $weResult->get('prepay_id'),
            'timestamp' => time()
        ];
        $result['sign'] = $weChat->makeSign($result);
        unset($weResult, $weChat, $orderData);
        return $result;
    }

    /**
     * 微信APP支付
     * @param $data
     * @return array
     * @throws ReflectionException
     */
    private function payByWeChatApp($data)
    {
        $orderData = [
            "body" => !empty($data['orderName']) ? $data['orderName'] : $data['orderSn'],
            "out_trade_no" => $data['orderSn'],
            "trade_type" => "APP",
            "total_fee" => env('APP_ENV') == 'production' ? $data['actualAmount'] * 100 : 1,
            "notify_url" => $data['notifyUrl']
        ];
        $weChat = new WeChat("App");
        $weResult = collect($weChat->Implement([
            "type" => "pay",
            "data" => $orderData
        ]));
        $result = [
            "appId" => $weResult->get('appid'),
            'partnerid' => $weResult->get('mch_id'),
            'prepayid' => $weResult->get('prepay_id'),
            'nonceStr' => $weResult->get('nonce_str'),
            'package' => "Sign=WXPay",
            "timestamp" => time()
        ];
        $result ['sign'] = $weChat->makeSign($result);
        return $result;
    }

    private function payByWeChatMweb($data)
    {
        $orderData = [
            'trade_type' => 'MWEB',
            "body" => !empty($data['orderName']) ? $data['orderName'] : $data['orderSn'],
            'out_trade_no' => $data['orderSn']
        ];
    }

    /**
     *
     * @param $data
     * @throws ReflectionException
     */
    private function payByWeChatJsApi($data)
    {
        $orderData = [
            'body' => !empty($data['orderName']) ? $data['orderName'] : $data['orderSn'],
            'out_trade_no' => $data['orderSn'],
            'total_fee' => env('APP_ENV') === 'production' ? $data['actualAmount'] * 100 : 1,
            'notify_url	' => $data['notifyUrl'],
            'trade_type' => 'JSAPI'
        ];
        $weChat = new WeChat("");
        $weResult = $weChat->Implement([
            "type" => "pay",
            "data" => $orderData
        ]);
        $result = [
            "appId" => $weResult->get('appid'),
            'par'
        ];
    }

    /**
     * 微信小程序支付
     * @param $data
     * @return array
     * @throws ReflectionException
     */
    private function payByWeChatProcedure($data)
    {
        $orderData = [
            "body" => !empty($data['orderName']) ? $data['orderName'] : $data['orderSn'],
            "out_trade_no" => $data['orderSn'],
            "total_fee" => env("APP_ENV") == 'production' ? $data['actualAmount'] * 100 : 1,
            "notify_url" => $data['notifyUrl'],
            "trade_type" => "JSAPI",
            "openid" => $data['openid']
        ];
        $weChat = new WeChat("Procedure");
        $weResult = $weChat->Implement([
            "type" => "pay",
            "data" => $orderData
        ]);
        $result = [
            "appId" => $weResult->get('appid'),
            'nonceStr' => md5("Pay" . time()),
            "package" => "prepay_id=" . $weResult->get('prepay_id'),
            "signType" => "MD5",
            "timestamp" => time()
        ];
        $result['sign'] = $weChat->makeSign($result);
        return $result;
    }

    /**
     * 支付宝
     * @param $data
     * @return mixed
     * @throws ReflectionException
     */
    private function payByAliPayApp($data)
    {
        $params = [
            "method" => "alipay.trade.app.pay",
            "mainArray" => [
                "notify_url" => ""
            ],
            "capitalJson" => collect([
                "total_amount" => env('APP_ENV') == 'production' ? $data['actualAmount'] : 0.01,
                "body" => !empty($data['orderName']) ? $data['orderName'] : $data['orderSn'],
                "subject" => "订单",
                "timeout_express" => "15m",
                "goods_type" => "",
                "out_trade_no" => $data['orderSn']
            ])->toJson()
        ];
        return AliPay::Implement("payByApp", $params);
    }

    private function payByAliPayNative($data)
    {
        $params = [
            "capitalJson" => collect([
                "total_amount" => env('APP_ENV') == 'production' ? $data['actualAmount'] : 0.01,
                "body" => !empty($data['orderName']) ? $data['orderName'] : $data['orderSn'],
                "subject" => "订单",
                "timeout_express" => "15m",
                "goods_type" => "",
                "out_trade_no" => $data['orderSn']
            ])->toJson(),
            "method" => "alipay.trade.precreate",
            "mainArray" => [
                "notify_url" => ""
            ]
        ];
    }

    /**
     * 设置订单信息
     * @param $data
     * @return Model|Builder|object|null
     * @throws Exception
     */
    public function setOrder($data)
    {
        if (!$order = DB::table('orderInfo')->where($data)->first()) throw new Exception("订单不存在");
        return $order;
    }

    private function getMsn()
    {
        $sn = "Msn" . date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        if ($order = DB::table('orderMaster')->where('orderMsn', $sn)->first()) $sn = $this->getMsn();
        return $sn;
    }

    private function getSn()
    {
        $sn = "" . date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        if ($order = DB::table('orderInfo')->where('orderSn', $sn)->first()) $sn = $this->getSn();
        return $sn;
    }
}
