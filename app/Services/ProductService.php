<?php


namespace App\Services;


use App\Models\Product;
use App\Models\Shop;
use App\Models\View\Product as ProductView;
use App\Models\ProductSku;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ProductService extends BaseService
{
    protected $fileType = "json";
    protected $disk = "product";
//    protected $product = [
//        "id" => "",
//        "shopId" => "",
//        "shipId" => "",
//        "stockId" => $data['stockId'],
//        "title" => "",
//        "weight" => "",
//        "amount" => "",
//        "type" => "",
//        "num" => "",
//        "saleCount" => "",
//        "status" => "",
//        "created_at",
//        "updated_at",
//        "colorList" => [
//            [
//                'skuId' => "",
//                'colorId' => "",
//                'skuAmount' => 0,
//                'skuNum' => 0,
//                'skuImage' => ""
//            ]
//        ],
//        "skuList" => [
//            [
//                'skuId' => "",
//                'colorId' => "",
//                'skuAmount' => 0,
//                'skuNum' => 0,
//                'skuImage' => ""
//            ]
//        ]
//    ];

    /**
     * 修改产品
     * @param $uId
     * @param $data
     * @return array
     * @throws Exception
     */
    public function changeProduct($uId, $data)
    {
        $shop = Shop::where('uId', $uId)->first();
        if (!$product = Product::firstOrCreate(['stockId' => $data['stockId']], [
            "shopId" => 1,
            'shipId' => $data['shipId'],
            'title' => $data['title'],
            "weight" => $data['weight'],
            "amount" => $data['amount'],
            "type" => $data['type'],
            "num" => !empty($data['skuList']) ? collect($data['skuList'])->sum('skuNum') : $data['num'],
            "saleCount" => 0,
            "status" => $data['status']
        ])) throw new Exception("提交失败", 10001);
        $skuList = ProductSku::where('stockId', $product->id)->get(['id', 'skuId', 'colorId']);
        foreach ($skuList as $key => $value) {
            if (collect($data['skuList'])->where('skuId', $value->skuId)->where('colorId', $value->colorId)->count() <= 0)
                if (!ProductSku::where('id', $value->id)->delete()) throw new Exception("移除无用规格属性失败");
        }
        $skuList = $colorList = [];
        $data['num'] = collect($data['skuList'])->sum('skuNum');
        foreach ($data['skuList'] as $item) {
            if (!ProductSku::updateOrCreate([
                'stockId' => $product->stockId,
                'skuId' => $item['skuId'],
                'colorId' => $item['colorId']
            ], [
                'skuAmount' => $item['skuAmount'],
                'skuNum' => $item['skuNum'],
                'skuImage' => $item['skuImage']
            ])) throw new Exception("编辑产品规格信息失败");
            if (isset($item['skuId']) && !empty($item['skuId']))
                $skuList[] = [
                    'skuId' => $item['skuId'],
                    'colorId' => isset($item['colorId']) && !empty($item['colorId']) ? $item['colorId'] : '',
                    'skuAmount' => $item['skuAmount'],
                    'skuNum' => $item['skuNum'],
                    'skuImage' => $item['skuImage']
                ];
            if (isset($item['colorId']) && !empty($item['colorId']))
                $colorList[] = [
                    'skuId' => isset($item['skuId']) && !empty($item['skuId']) ? $item['skuId'] : '',
                    'colorId' => $item['colorId'],
                    'skuAmount' => $item['skuAmount'],
                    'skuNum' => $item['skuNum'],
                    'skuImage' => $item['skuImage']
                ];
        }
        if (!empty($data['skuList'])) $product->update(['num' => collect($data['skuList'])->sum('skuNum')]);
        $product = array_merge($product->toArray(), ['skuList' => $skuList, 'colorList' => $colorList]);
        $this->changeProductFile($product);
        return [];
    }

    /**
     * 修改产品文件
     * @param $product
     * @return bool
     * @throws FileNotFoundException
     */
    public function changeProductFile($product)
    {
        $file = $this->setFile($product['stockId'], $this->fileType);
        if ($this->storage($this->disk, $file, "get"))
            if (!$this->storage($this->disk, $file, "del")) throw new Exception("修改产品信息失败", 1);
        if (!$this->storage($this->disk, $file, "change", collect($product)->toJson())) throw new Exception("修改产品信息失败", 2);
        return true;
    }

    /**
     * 获取商品信息
     * @param $stockId
     * @return array
     * @throws Exception
     */
    public function getProduct($stockId)
    {
        $file = $this->setFile($stockId, $this->fileType);
        if (!$product = $this->storage($this->disk, $file, "get")) {
            if (!$product = Product::where('stockId', $stockId)->first()) throw new Exception("获取商品详情失败,该商品可能已下架");
            $sku = $color = [];
            foreach (ProductSku::where('stockId', $product->stockId)->get(['skuId', 'colorId', 'skuAmount', 'skuNum', 'skuImage']) as $item) {
                $sku[] = !empty($item->skuId) ? $item->toArray() : [];
                $color[] = !empty($item->colorId) ? $item->toArray() : [];
            }
            $product = collect(array_merge($product->toArray(), ['skuList' => $sku, 'colorList' => $color]))->toJson();
            $this->storage($this->disk, $this->setFile($stockId, $this->fileType), 'change', $product);
        }
        $product = json_decode($product, true);
        if (config('params.productStatus.' . $product['status']) == '下架') throw new Exception("商品已下架");
        return $product;
    }

    public function getProductList($search, $page, $length)
    {
        $this->list = array_merge($this->list, ['per_page' => $length, 'page' => $page]);
        $query = ProductView::orderByDesc('saleCount');
        isset($search['shopId']) && !empty($search['shopId']) && $query = $query->where('shopId', $search['shopId']);
        isset($search['title']) && !empty($search['title']) && $query = $query->where('title', 'like', '%' . $search['title'] . '%');
        isset($search['minAmount']) && !empty($search['minAmount']) && $query = $query->where('amount', '>=', $search['minAmount']);
        isset($search['maxAmount']) && !empty($search['maxAmount']) && $query = $query->where('amount', '<=', $search['maxAmount']);
        if ($result = $query->paginate($length, ['*'], 'page', $page)) {
            $this->list = array_merge($this->list, [
                'total' => $result->total(),
                'total_page' => $result->lastPage(),
                'list' => $result->items()
            ]);
        }
        return $this->list;
    }

    public function AssembleProduct($data)
    {

    }
}
