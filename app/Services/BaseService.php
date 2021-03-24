<?php
namespace App\Services;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BaseService{
    protected $list = [
        'page'=>0,
        'per_page'=>0,
        'total'=>0,
        'total_page'=>0,
        'list'=>[]
    ];

    protected function setFile($name,$fileType)
    {
        return $name.".".$fileType;
    }

    /**
     * 文件处理
     * @param string $disk 存储空间
     * @param string $name 文件名
     * @param string $type 处理方式
     * @param null $data 数据
     * @return array|bool|string
     * @throws FileNotFoundException
     */
    protected function storage(string $disk, string $name, string $type, $data=null)
    {
        $storage = Storage::disk($disk);
        $method = [
            "exists" => $storage->exists($name),
            "change" => $storage->prepend($name, $data),
            "get" => $storage->exists($name) ? $storage->get($name) : [],
            "del" => $storage->exists($name) ? $storage->delete($name) : false
        ];
        return $method[$type];
    }

    /**
     * 获取地址信息
     * @param $province
     * @param $city
     * @return array
     * @throws FileNotFoundException
     */
    protected function getAddress($province,$city)
    {
        $file = $this->storage("json","address.json","get");
        $data = json_decode($file);
        $cityResult = [];
        if ($provinceResult = collect($data)->where('name', $province)->values()->first()) {
            $cityResult = collect($provinceResult['children'])->where('name', $city)->values()->first();
            unset($provinceResult['children']);
        }
        return [$provinceResult, $cityResult];
    }

    public function checkView($name)
    {
        $sql = "select table_name from information_schema.tables where table_schema='".env('DB_DATABASE')."' and table_name='{$name}'";
        return DB::select($sql)?true:$this->createView($name);
    }

    public function createView($name)
    {
        $sql = "Create View " . $name . ' as select ';

        $sqlArray = [
            "userCouponView" => "`userCoupon`.`id`,`couponId`,`uId`,`status`,`couponName`,`type`,`amount`,`dNum`,`startDate`,`endDate`  from userCoupon join shopCoupon on shopCoupon.id = userCoupon.couponId",
            "skuView"=>"",
            "productView"=>"",
        ];
        if ($sqlArray[$name]) return DB::statement($sql . $sqlArray[$name]);
        return false;
    }
}
