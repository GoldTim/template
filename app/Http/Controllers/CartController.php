<?php


namespace App\Http\Controllers;


use App\Services\CartService;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class CartController extends Controller
{
    protected $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new CartService(1);
//        $this->service = $service;
    }

    public function __destruct()
    {
        $this->service=null;
    }

    public function getList()
    {
        try {
            $result = $this->service->getCart();
            send_response($result);
        } catch (Exception$exception) {
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function change()
    {
        DB::beginTransaction();
        try {
            $result = $this->service->changeCart($this->getData('data'));
            DB::commit();
            send_response($result);
        } catch (Exception $exception) {
            DB::rollBack();
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }

    public function commitCart()
    {
        try {
            $result = $this->service->commitCart( $this->getData('data'),1);
            send_response($result);
        } catch (Exception $exception) {
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }
}
