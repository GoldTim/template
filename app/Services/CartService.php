<?php


namespace App\Services;


use App\Helpers\Cart;
use App\Models\ShipDetail;
use App\Models\ShipInfo;
use App\Models\ShipTerm;
use App\Models\ShopCoupon;
use App\Models\UserAddress;
use App\Models\View\UserCoupon;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;

class CartService extends BaseService
{
    protected $service;

    public function __construct($uId)
    {
        $this->service = new Cart($uId);
    }

    public function __destruct()
    {
        $this->service = null;
    }

    public function getCart()
    {
        return $this->service->getCart();
    }


    /**
     * 修改购物车信息
     * @param $data
     * @return array
     * @throws Exception
     */
    public function changeCart($data)
    {
        if (!Storage::disk('product')->exists($data['stockId'] . '.json')) throw new Exception("产品不存在");
        if (!$product = Storage::disk('product')->get($data['stockId'] . '.json')) throw new Exception("产品不存在");
        $product = json_decode($product, true);
        if (config('params.productStatus.' . $product['status']) == '下架') throw new Exception("商品已下架");
        if (config('params.productStatus.' . $product['status']) == '售罄') throw new Exception("商品已售罄");

        $bool = false;
        if ($cart = $this->service->getCart()) {
            foreach ($cart as $item) {
                if ($bool = ($item['stockId'] === $data['stockId'] && $item['skuId'] === $data['skuId'] && $item['colorId'] === $data['colorId']))
                    $item['skuNum'] += $data['skuNum'];
            }
        }
        if (!$bool) $cart = collect($cart)->add($data)->filter(function ($item) {
            return $item['skuNum'] > 0;
        })->values();
        if (!$this->service->changeCart($cart->toJson())) throw new Exception("修改购物车信息失败");
        return [];
    }

    public function changeCartList($data)
    {

    }


    /**
     * 提交购物车
     * @param $data
     * @param $uId
     * @return array
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function commitCart($data,$uId)
    {
        $this->checkView("userCouponView");
        $result = $productList = [];
        if (isset($data['shop'])) {
            if (!$userAddress = UserAddress::find($data['addressId'])) throw new Exception("获取地址失败");

            list($province, $city) = $this->getAddress($userAddress->province, $userAddress->city);
            foreach ($data['shop'] as $item) {
                foreach ($item['productList'] as $value) $productList[] = $this->getProduct($value);
                if (!empty($productList)) $result[] = $this->AssCart($productList, $uId, $item['coupon'], $province, $city);
            }
        } else {
            foreach ($data as $item) $productList[] = $this->getProduct($item);
            foreach (collect($productList)->groupBy('shopId')->values()->toArray() as $item) {
                $result[] = $this->AssCart($item, $uId);
            }
        }
        if (empty($result)) throw new Exception("获取商品信息失败");
        return [
            'orderAmount' => collect($result)->sum('orderAmount'),
            'actualAmount' => collect($result)->sum('actualAmount'),
            'shipAmount' => collect($result)->sum('shipAmount'),
            'disAmount' => collect($result)->sum('disAmount'),
            'shop' => $result
        ];
    }


    private function AssCart($productList,$uId,$couponCode=null,$province=null,$city=null)
    {
        $shipAmount = 0;
        foreach ($productList as $item) {

            if (!empty($province) || !empty($city)) {
                if ($ship = ShipInfo::where('type', array_search('否', config('params.status')))->find($item['shipId'])) {
                    if (!$shipDetail = ShipDetail::where('shipId', $ship->id)->where(function ($query) use ($province, $city) {
                        $query->where('province', 'like', "%" . $province['code'] . "%")->orWhere('city', 'like', '%' . $city['code']);
                    })->first()) $shipDetail = ShipDetail::where(['shipId' => $ship->id, 'province' => 'default'])->first();
                    $cal = config('params.shipType.' . $ship->chargingType) === '重量' ? $item['num'] * $item['weight'] / 1000 : $item['num'];
                    $amount = $shipDetail->dAmount + (($shipDetail->dNum > $cal ? $shipDetail->dNum : $cal) - $shipDetail->dNum) / $shipDetail->cNum * $shipDetail->renew;
                    if (config('params.status.' . $ship->isFree) === '是') {
                        $shipTerm = ShipTerm::where('shipId', $ship->id)->where('province', 'like', '%' . $province['code'] . '%')->whereNull('city');
                        if ($shipTerm->count() <= 0)
                            $shipTerm = ShipTerm::where('shipId', $ship->id)->where('city', 'like', '%' . $city['code'] . '%')->whereNull('province');
                        if ($shipTerm->where(function ($query) use ($item, $cal) {
                                $query->where([
                                    ['termType', '=', array_search('结合', config('params.termType'))],
                                    ['amount', '<', $item['tAmount']],
                                    ['number', ',', $cal]
                                ])->orWhere([
                                    [
                                        'termType', '=', array_search('数字', config('params.termType'))
                                    ], [
                                        'number', '<', $cal
                                    ]
                                ])->orderwhere([
                                    ['termType', '=', array_search('金额', config('params.termType'))],
                                    ['amount', '<', $item['tAmount']]
                                ]);
                            })->count() > 0) $amount = 0;
                    }
                    $shipAmount += $amount;
                }
                unset($amount, $ship, $shipDetail, $cal);
            }
        }
        $coupon = $this->AssCoupon($uId, collect($productList)->sum('tAmount'),$couponCode);
        $list = [
            'coupon' => $coupon,
            'productList' => $productList,
            'orderAmount' => collect($productList)->sum('tAmount'),
            'actualAmount' => collect($productList)->sum('tAmount') + $shipAmount,
            'shipAmount' => $shipAmount,
            'disAmount' => !empty($coupon) ? collect($coupon)->where('check', true)->first()['dNum'] : 0
        ];
        return !empty($list) ? $list : [];
    }

    private function AssCoupon($uId,$amount,$couponCode=null)
    {
        $couponList = UserCoupon::where([
            'uId' => $uId,
            'status' => array_search('待使用', config('params.couponStatus'))
        ])->pluck('couponId');
        $query = ShopCoupon::where([
            ['amount', '<=', $amount],
            ['startDate', '<=', Carbon::now()],
            ['endDate', '>', Carbon::now()]
        ]);
        !empty($couponList) && $query = $query->whereNotIn('id', $couponList->toArray());
        $shopCoupon = $query->get(['id', 'couponName']);
        unset($couponList, $query);
        foreach ($shopCoupon as $value) UserCoupon::create(['uId' => $uId, 'couponId' => $value->id]);
        $search = !empty($couponCode) ? ['id' => $couponCode] : [
            ['uId', '=', $uId],
            ['amount', '<=', $amount],
            ['status', '=', array_search('待使用', config('params.couponStatus'))],
        ];
        $coupon = UserCoupon::where($search)->first();
        return $shopCoupon->map(function (ShopCoupon $shopCoupon) use ($coupon) {
            return array_merge($shopCoupon->toArray(), ['check' => $shopCoupon->id == $coupon->couponId]);
        })->toArray();
    }



    /**
     * 获取产品
     * @param $data
     * @return array
     * @throws FileNotFoundException
     * @throws Exception
     */
    private function getProduct($data)
    {
        if (!$product = Storage::disk('product')->get($data['stockId'] . '.json')) throw new Exception("获取产品失败");
        $product = json_decode($product, true);
        if ($product['num'] < $data['skuNum']) throw new Exception("库存不足");
        $result = [
            'shopId' => $product['shopId'],
            'stockId' => $product['stockId'],
            'title' => $product['title'],
//            'img' => $product['skuImage'],
            'num' => $data['skuNum'],
            'amount' => $product['amount'],
            "weight"=>intval($product['weight']),
            'skuId' => "",
            'colorId' => ""
        ];
        if (!empty($product['skuList']) || !empty($product['colorList'])) {
            $sku = collect($product['skuList'])->where('skuId', $data['skuId'])->first() ?
                collect($product['skuList'])->where('skuId', $data['skuId'])->first() :
                collect($product['colorList'])->where('colorId', $data['colorId'])->first();
            if (empty($sku)) throw new Exception("规格或属性不存在");
            if ($sku['skuNum'] < $data['skuNum']) throw new Exception("库存不足");
            $result = array_merge($result, [
                'skuId' => $sku['skuId'],
                'colorId' => $sku['colorId'],
                'amount' => $sku['skuAmount']
            ]);
        }
        $result['tAmount'] = $result['num'] * $result['amount'];

        return $result;
    }
}
