<?php


namespace App\Http\Controllers;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;

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
}
