<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ShipDetail;
use App\Models\ShipInfo;
use App\Models\ShipTerm;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $product = Product::create([
            'shopId' => 1,
            'shipId' => 1,
            'stockId' => 'std0000001',
            'title' => '测试',
            'weight' => 1,
            'amount' => 15,
            'type' => 1,
            'num' => 100,
            'saleCount' => 0,
            'status' => array_search('上架', config('params.productStatus'))
        ]);
        ProductSku::create([
            'stockId' => $product->stockId,
            'skuId' => 'L码',
            'colorId' => '军绿色',
            'skuAmount' => 1000,
            'skuNum' => 50,
            'skuImage' => ''
        ]);
        ProductSku::create([
            'stockId' => $product->stockId,
            'skuId' => 'S码',
            'colorId' => '军绿色',
            'skuAmount' => 1000,
            'skuNum' => 50,
            'skuImage' => ''
        ]);
        $ship = ShipInfo::create([
            'name' => '测试模板',
            'address' => [
                'province' => '广东',
                'city' => '广州',
                'area' => '番禺'
            ],
            'sendTime'=>array_search('4小时内',config('params.sendTime')),
            'type'=>array_search('否',config('params.status')),
            'chargingType'=>array_search('重量',config('params.shipType')),
            'isFree'=>array_search('是',config('params.status'))
        ]);
        ShipDetail::create([
            'shipId' => $ship->id,
            'type' => array_search('快递', config('params.shipType')),
            'province' => "default",
            'city' => 'default',
            'dNum' => '1',
            'dAmount' => 10,
            'cNum' => 1,
            'renew' => 5
        ]);
        ShipDetail::create([
            'shipId' => $ship->id,
            'type' => array_search('快递', config('params.shipType')),
            'province' => "广东",
            'city' => '广州',
            'dNum' => '1',
            'dAmount' => 5,
            'cNum' => 1,
            'renew' => 5
        ]);
        ShipTerm::create([
            "shipId"=>$ship->id,
            "type"=>array_search('快递',config('params.sendType')),
            'province'=>'44',
            'city'=>'4405',
            'termType'=>array_search('数字',config('params.termType')),
            'amount'=>0,
            'number'=>2
        ]);
        ShipTerm::create([
            "shipId"=>$ship->id,
            "type"=>array_search('快递',config('params.sendType')),
            'province'=>'44',
            'city'=>'4405',
            'termType'=>array_search('金额',config('params.termType')),
            'amount'=>10,
            'number'=>0
        ]);
    }
}
