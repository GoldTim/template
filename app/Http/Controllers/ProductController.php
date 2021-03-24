<?php


namespace App\Http\Controllers;


use App\Services\ProductService;
use Exception;

use Illuminate\Support\Facades\Request;
class ProductController extends Controller
{
    protected $service;

    public function __construct(ProductService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function __destruct()
    {
        $this->service = null;
    }

    public function getList()
    {
        try {
            $result = $this->service->getProductList($this->getData('search', true, []), self::$page, self::$length);
            if (Request::method() == 'GET')
                return view('');
            else
                send_response($result);
        } catch (Exception$exception) {
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }

    public function change()
    {
        try {
            $result = $this->service->changeProduct(null,$this->getData('data'));
            send_response($result);
        } catch (Exception$exception) {
            send_response([], $exception->getCode(), $exception->getMessage());
        }

    }

    public function detail()
    {
        try {
            $result = $this->service->getProduct(request('stockId'));
            send_response($result);
        } catch (Exception$exception) {
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }
}
