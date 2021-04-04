<?php


namespace App\Http\Controllers;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class TestController
{
    public function createOrder()
    {
        $data = [
            "shop"=>[
                [
                    "cartId"=>[
                       0,1
                    ],
                    "couponCode"=>"",
                    "orderRemark"=>"",
                    "point"=>""
                ]
            ],"address"=>1
        ];
        DB::beginTransaction();
        try{

            $service = new OrderService();
            dd($service->checkView('userCouponView'));
            $result = $service->createOrder($data,1);
            DB::commit();
            dd($result);
        }catch (\Exception $exception){
            DB::rollBack();
            dd($exception->getMessage());
        }
    }

    public function test()
    {
        $order = new OrderService();
        try {
            $result = $order->payOrder(1, [
                "orderSn" => "Msn2021040256524848",
                "payMethod" => 5,
                "code"=>"135205303866027988"
            ]);
            dd($result);
        }catch (Exception$exception){
            dd($exception->getMessage());
        }

    }
}
