<?php


namespace App\Services;


use App\Helpers\WeChat;
use App\Helpers\Cart;
use App\Helpers\Order as OrderHelper;
use App\Models\Order;
use App\Models\OrderAfter;
use App\Models\OrderDetail;
use App\Models\OrderMaster;
use App\Models\ShipTerm;
use App\Models\ShipDetail;
use App\Models\ShipInfo;
use App\Models\UserAddress;
use App\Models\View\UserCoupon;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Support\HigherOrderCollectionProxy;
use ReflectionException;

class OrderService extends BaseService
{

    /**
     * 创建订单
     * @param $data
     * @param $uId
     * @return array
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function createOrder($data, $uId)
    {
        $orderList = [];
        if (!$userAddress = UserAddress::find($data['addressId'])) throw new Exception("获取用户地址失败");
        $cartService = new Cart($uId);
        $cart = $cartService->getCart();
        foreach ($data['shop'] as $item) {
            $shipAmount = $disAmount = $shopId = 0;
            $productList = [];
            foreach ($item['productList'] as $value) {
                $proService = new ProductService();
                $product = $proService->getProduct($value['stockId']);
                $p = [
                    'stockId' => $product['stockId'],
                    'name' => $product['title'],
                    "skuId" => null,
                    "colorId" => null,
                    "cover" => '',
                    "quantity" => $value['skuNum'],
                    'amount' => $product['amount'],
                    "tAmount" => $product['amount'] * $value['skuNum'],
                    "discount" => 0,
                    "tDiscount" => 0,
                ];
                $shopId = $product['shopId'];
                $product = array_merge($product, [
                    "saleCount" => $product['saleCount'] + $value['skuNum'],
                    "num" => $product['num'] - $value['skuNum'],
                    "updated_at" => Carbon::now()->format("Y-m-d H:i:s")
                ]);
                if (!empty($product['skuList']) || !empty($product['colorList'])) {
                    $skuList = array_merge($product['skuList'], $product['colorList']);
                    if (isset($value['skuId'])) $skuList = collect($product['skuList'])->where('skuId', $value['skuId'])->values();
                    if (isset($value['colorId'])) $skuList = collect($product['colorList'])->where('colorId', $value['colorId'])->values();
                    if ($skuList->count() < 1) throw new Exception("获取产品规格失败");
                    if ($skuList->count() > 1) throw new Exception("产品规格过多");
                    $sku = $skuList->first();
                    $p = array_merge($p, [
                        "amount" => $sku['skuAmount'],
                        "tAmount" => $sku['skuAmount'] * $value['skuNum'],
                        "skuId" => $sku['skuId'],
                        "colorId" => $sku['colorId']
                    ]);
                    if (isset($value['skuId']) && !empty($value['skuId']))
                        $product['skuList'] = collect($product['skuList'])->map(function ($val) use ($value) {

                            if ($val['skuId'] == $value['skuId']) $val['skuNum'] = $val['skuNum'] - $value['skuNum'];
                            return $val;
                        })->values()->toArray();
                    if (isset($value['colorId']) && !empty($value['colorId']))
                        $product['colorList'] = collect($product['colorList'])->map(function ($val) use ($value) {
                            if ($val['colorId'] == $value['colorId']) $val['skuNum'] = $val['skuNum'] - $value['skuNum'];
                            return $val;
                        })->values()->toArray();
                    unset($sku, $skuList);
                }
                $shipAmount += $this->checkShipAmount([
                    "shipId" => $product['shipId'],
                    'weight' => $product['weight'] * $value['skuNum'] / 1000,
                    'num' => $value['skuNum']
                ], $userAddress);
                $productList [] = $p;

                foreach ($cart as $val) {
                    if ($val['stockId'] === $p['stockId'] && $val['skuId'] === $p['skuId'] && $val['colorId'] === $p['colorId']) $val['skuNum'] = $val['skuNum'] - $p['quantity'];
                }
                $proService->changeProductFile($product);
                unset($p);
            }
            if (!empty($item['coupon'])) $disAmount = $this->checkCoupon($item['coupon'], $productList, $uId);
            $orderList[] = [
                "order" => [
                    "shopId" => $shopId,
                    "customer" => $uId,
                    "orderName" => implode("-", collect($productList)->pluck("name")->toArray()),
                    "orderAmount" => collect($productList)->sum("tAmount"),
                    'actualAmount' => collect($productList)->sum('tAmount') + $shipAmount - $disAmount,
                    'disAmount' => $disAmount,
                    'shipAmount' => $shipAmount,
                    "disPoint" => isset($item['point']) ? $item['point'] : 0,
                    "couponCode" => $item['coupon'],
                    "orderType" => 1,
                    "orderRemark" => isset($item['orderRemark']) ? $item['orderRemark'] : ""
                ],
                "detail" => $productList
            ];
        }
        $order = new OrderHelper();
        $result = $order->createOrder($orderList);
        $cart = collect($cart)->filter(function ($item) {
            return $item['skuNum'] > 0;
        })->values()->toJson();
        if (!$cartService->changeCart($cart)) throw new Exception("修改购物车信息失败");
        return $result;
    }

    /**
     * 计算优惠
     * @param $couponCode
     * @param $productList
     * @param $uId
     * @return HigherOrderBuilderProxy|HigherOrderCollectionProxy|int|mixed
     * @throws Exception
     */
    private function checkCoupon($couponCode, $productList, $uId)
    {
        if (!$userCoupon = UserCoupon::where('uId', $uId)
            ->where('startDate', '<=', Carbon::now())
            ->where('endDate', '>', Carbon::now())
            ->find($couponCode)) throw new Exception("获取优惠券失败");
        $disAmount = 0;
        if (config('params.couponType.' . $userCoupon->type) === '店铺') {
            $disAmount = collect($productList)->sum('tAmount') > $userCoupon->dAmount ? $userCoupon->amount : 0;
        } elseif (config('params.couponType.' . $userCoupon->type) === '商品') {
            $product = collect($productList)->where('stockId', $userCoupon->stockId)->first();
            $disAmount = $product['tAmount'] >= $userCoupon->dAmount ? $userCoupon->amount : 0;
        }
        return $disAmount;
    }

    /**
     * 计算邮费
     * @param $product
     * @param $address
     * @return float|HigherOrderBuilderProxy|int|mixed
     * @throws FileNotFoundException
     */
    private function checkShipAmount($product, $address)
    {
        $shipAmount = 0;
        list($province, $city) = $this->getAddress($address->province, $address->city);
        if ($ship = ShipInfo::find($product['shipId'])) {
            if (config('params.status.' . $ship->type) === '否') {
                if (!$shipDetail = ShipDetail::where(function ($query) use ($province, $city, $ship) {
                    $query->where('shipId', $ship->id)->where('province', 'like', '%' . $province['code'] . '%')->whereNull('city');
                })->orWhere(function ($query) use ($province, $city, $ship) {
                    $query->where('shipId', $ship->id)->whereNull('province')->where('city', 'like', '%' . $city['code'] . '%');
                })->first())
                    $shipDetail = ShipDetail::where([
                        'shipId' => $ship->id,
                        'province' => 'default',
                        'city' => 'default'
                    ])->first();
                $cal = config('params.shipType.' . $ship->chargingType) === '重量' ? $product['weight'] : $product['num'];
                $shipAmount = $shipDetail->dAmount + (($shipDetail->dNum > $cal ? $shipDetail->dNum : $cal) - $shipDetail->dNum) / $shipDetail->cNum * $shipDetail->renew;
                if (config('params.status.' . $ship->isFree) === '是') {
                    $shipTerm = ShipTerm::where('shipId', $ship->id)
                        ->where('province', 'like', '%' . $province['code'] . '%')->get();
                    if ($shipTerm->count() > 0) {
                        if ($shipTerm->where('city', 'like', '%' . $city['code'] . '%')->count() > 0)
                            $shipTerm = $shipTerm->where('city', 'like', '%' . $city['code'] . '%');
                        else
                            $shipTerm = $shipTerm->whereNull('city');
                    } else {
                        $shipTerm = ShipTerm::where('shipId', $ship->id)
                            ->where('city', 'like', '%' . $city['code'] . '%')->get();
                        if ($shipTerm->where('province', 'like', '%' . $province['code'] . '%')->count() > 0)
                            $shipTerm = $shipTerm->where('province', 'like', '%' . $province['code'] . '%');
                        else
                            $shipTerm = $shipTerm->whereNull('province');
                    }
                    foreach ($shipTerm as $item) {
                        if (config('params.termType.' . $item->termType) === '数字') {
                            $shipAmount = $cal > $item->number ? 0 : $shipAmount;
                            break;
                        } elseif (config('params.termType.' . $item->termType) === '金额') {
                            $shipAmount = $product['tAmount'] > $item->amount ? 0 : $shipAmount;
                            break;
                        } elseif (config('params.termType.' . $item->termType) === '结合') {
                            $shipAmount = ($cal > $item->number && $product['tAmount'] > $item->amount) ? 0 : $shipAmount;
                            break;
                        }
                    }
                }
            }
        }
        return $shipAmount;
    }

    /**
     * 支付
     * @param $uId
     * @param $data
     * @return mixed
     * @throws ReflectionException
     * @throws Exception
     */
    public function payOrder($uId, $data)
    {
        $payResult = [];
        $search = [
            'customer' => $uId
        ];
        if (strpos($data['orderSn'], "Msn") !== false) {
            $search['paySn'] = $data['orderSn'];
        } else {
            $search['orderSn'] = $data['orderSn'];
        }
        if (!$orderList = Order::where($search)->get()) throw new Exception("订单不存在");
        if (!$payResult['payName'] = config('params.payMethod.' . $data['payMethod'])) throw new Exception("支付方式不存在");

        $orderHelper = new OrderHelper();
        if (!empty($orderList->first()['payMethod'])) {
            $oldOrder = $orderList->first();
            if ($viewResult = $orderHelper->checkOrder(['orderSn' => $oldOrder->orderSn])) throw new Exception("订单已支付");
            if (isset($search['paySn'])) {
                $orderMaster = OrderMaster::where('orderMsn', $search['paySn'])->first();
                if (!$orderMaster->update([
                    "orderMsn" => ""
                ])) throw new Exception("");//支付单号
                if (!Order::where('paySn', $search['paySn'])->update([
                    "paySn" => $orderMaster->orderMsn,
                ])) throw new Exception("");//修改支付单号
            } else {
                if (!Order::where('orderSn', $search['orderSn'])->update([
                    "paySn" => ""
                ])) throw new Exception("");//修改支付单号
            }
        }
        foreach ($orderList as $item) {
            if (!Order::where('orderSn', $item->orderSn)->update([
                "payMethod" => $data['payMethod']
            ])) throw new Exception("修改支付方式失败");
        }
        if (isset($data['code'])) $payResult['code'] = $data['code'];
        $payResult = array_merge($payResult, [
            'orderSn' => isset($search['paySn']) ? $search['paySn'] : $search['orderSn'],
            'orderName' => isset($search['paySn']) ? '合并订单支付' : $orderList->first()->orderName,
            'actualAmount' => $orderList->sum('actualAmount'),
            'notifyUrl' => 'test/ckWeChat'
        ]);
        return $orderHelper->Pay($payResult);
    }

    /**
     * 获取订单列表
     * @param $uId
     * @param $search
     * @return array
     */
    public function listOrder($uId, $search)
    {
        $query = Order::where('customer', $uId)->orderByDesc('created_at');
        if ($result = $query->paginate($search['length'], ['orderSn', 'shopId', 'status', 'title', 'actualAmount', 'orderAmount', 'disAmount', 'shipAmount'], 'page', $search['page'])) {
            $this->list = [
                'page' => $result->perPage(),
                'length' => $result->currentPage(),
                'total' => $result->total(),
                'total_page' => $result->lastPage(),
                'list' => collect($result->items())->map(function (Order $order) {
                    $detail = OrderDetail::where('orderSn', $order->orderSn)->get(['stockId', 'name', 'tAmount', 'quantity', 'cover', 'skuId', 'colorId']);

                    return [
                        'orderSn' => $order->orderSn,
                        'title' => $order->title,
                        'detail' => $detail->toArray(),
                    ];
                })->values()->toArray()
            ];
        }
        return $this->list;
    }

    /**
     * 订单售后
     * @param $uId
     * @param $data
     * @return array
     * @throws Exception
     */
    public function afterOrder($uId, $data)
    {
        $data = [
            "orderSn" => "",
            "afterType" => "",//仅退款,退款退货,换货
            "shipStatus" => "",
            "afterReason" => "",//售后原因
            "refundAmount" => "",
            "refundDescription" => "",
            "picture" => []
        ];
        $throwArray = [
            "未支付", "退款中"
        ];
        if (!$order = Order::where([
            "orderSn" => $data['orderSn'],
            'uId' => $data['uId']
        ])) throw new Exception("订单不存在");
        if (Carbon::now()->diffInDays(Carbon::parse($order->created_at)) > 30) throw new Exception("超过售后时间");
        if (!OrderAfter::create([
            "orderSn" => $order->orderSn,
            "afterType" => $data['afterType'],
            "isShip" => $data['isShip'],
            "afterReason" => $data['afterReason'],
            "refundAmount" => $data['refundAmount'],
            "actualAmount" => $order->actualAmount,
            "refundDescription" => $data['refundDescription'],
            "picture" => $data['picture']
        ])) throw new Exception("提交申请失败");
        return [];
    }

    /**
     * 订单退款
     * @param $uId
     * @param $data
     * @throws Exception
     * //     * @return array
     */
    public function refundOrder($uId, $data)
    {
//        $throwArray = [
//            "未支付",
//            "退款中",
//            ""
//        ];
//        if (!$order = Order::where('orderSn', $orderSn)->where('uId', $uId)->first()) throw new Exception("订单不存在");
//        if(config('params.orderStatus.'.$order->status)=='退款中') throw new Exception("您已申请退款,请勿重复申请");
//        if(config('params.orderStatus.'.$order->status)=='未支付') throw new Exception("");
//        if (!$order->update([
//            'status' => array_search('退款中', config('params.orderStatus'))
//        ])) throw new Exception("发起退款申请失败");
//        if (!OrderRefund::create([
//            "orderSn" => $order->orderSn,
//            "detail" => "用户发起退款",
//            "type" => "",
//            "actualAmount" => $order->actualAmount,
//            "refundAmount" => $order->refundAmount
//        ])) throw new Exception("发起退款申请失败");
//        if (!OrderLog::create([
//            "orderSn" => $order->orderSn,
//            "detail" => "用户发起了订单退款"
//        ])) throw new Exception("申请退款失败");
//        return [];
    }

    /**
     * 确认订单
     * @param $uId
     * @param $orderSn
     * @return array
     * @throws Exception
     */
    public function confirmOrder($uId, $orderSn)
    {
        $order = new OrderHelper();
        if (!$order->confirm([
            "orderSn" => $orderSn,
            "customer" => $uId
        ])) throw new Exception("确认订单失败");
        return [];
    }

    /**
     * 取消订单
     * @param $uId
     * @param $orderSn
     * @return array
     * @throws Exception
     */
    public function cancelOrder($uId, $orderSn)
    {
        $order = new OrderHelper();
        if (!$order->cancel([
            "customer" => $uId,
            "orderSn" => $orderSn
        ])) throw new Exception("取消订单失败");
        return [];
    }

    /**
     * 删除订单
     * @param $uId
     * @param $orderSn
     * @return array
     * @throws Exception
     */
    public function deleteOrder($uId, $orderSn)
    {
        $orderHelper = new OrderHelper();
        if (!$orderHelper->delOrder([
            'orderSn' => $orderSn,
            'customer' => $uId
        ])) throw new Exception("删除订单失败");
        return [];
    }

    /**
     * 订单详情
     * @param $uId
     * @param $orderSn
     * @return array
     * @throws Exception
     */
    public function detailOrder($uId, $orderSn)
    {
        $order = new OrderHelper();
        return $order->detailOrder([
            "customer" => $uId,
            "orderSn" => $orderSn
        ]);
    }

    /**
     * 微信回调通知
     * @param $data
     * @return bool|string
     * @throws Exception
     */
    public function notifyWeChat($data)
    {
        $xml = xmlToCollection($data)->toArray();
        $weChat = new WeChat("");
        $verify = $weChat->verification($xml);
        if ($verify != "success") throw new Exception($verify);
        $order = new OrderHelper();
        $order->notify($xml['out_trade_no']);
        return arrayToXml([
            "return_code" => "SUCCESS",
            "return_msg" => "OK"
        ]);
    }

    /**
     * 支付宝回调支付
     * @param $data
     * @return array
     * @throws Exception
     */
    public function notifyAliPay($data)
    {
        $orderSn = is_array($data) && isset($data['out_trade_no']) && !empty($data['out_trade_no']) ? $data['out_trade_no'] : "";
        if (!$orderSn && is_string($data)) {
            $data = explode("&", $data);
            $orderSn = isset($data['out_trade_no']) && !empty($data['out_trade_no']) ? $data['out_trade_no'] : "";
        }
        if (empty($orderSn)) throw new Exception("订单编号为空或订单编号不存在");
        $orderHelp = new OrderHelper();
        return $orderHelp->notify($orderSn);
    }

    /**
     * 检查订单是否支付
     * @param $uId
     * @param $orderSn
     * @return array
     * @throws ReflectionException
     * @throws Exception
     */
    public function checkOrder($uId, $orderSn)
    {
        $search = ["customer" => $uId];
        $search = array_merge($search, strpos($orderSn, "Msn") !== false ? ["paySn" => $orderSn] : ["orderSn" => $orderSn]);
        $help = new OrderHelper();
        $payStatus = $help->checkOrder($search);
        return [
            "status" => $payStatus,
            "msg" => !$payStatus ? "订单未支付" : "订单已支付"
        ];
    }
}
