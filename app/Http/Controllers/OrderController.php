<?php


namespace App\Http\Controllers;


use App\Services\OrderService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends Controller
{
    protected $service;

    public function __construct(OrderService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 微信支付通知
     * @throws Throwable
     */
    public function notifyWeChat()
    {
        DB::beginTransaction();
        try {
            $data = file_get_contents("php://input");
            Log::channel("weChat")->info("回调开始",[]);
            $result = $this->service->notifyWeChat($data);
            Log::channel("weChat")->info("回调成功");
            DB::commit();
            send_response($result);
        } catch (Exception$exception) {
            DB::rollBack();
            Log::channel("weChatPay")->error("回调失败,原因：" . $exception->getMessage());
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }

    /**
     * 支付宝支付通知
     * @throws Throwable
     */
    public function notifyAliPay()
    {
        DB::beginTransaction();
        try {
            $data = !empty(file_get_contents("php://input"))?file_get_contents("php://input"):$_GET;
            Log::channel("aliPay")->info("回调开始", []);
            $result = $this->service->notifyAliPay($data);
            Log::channel("aliPay")->info("回调成功");
            DB::commit();
            send_response($result);
        } catch (Exception$exception) {
            DB::rollBack();
            Log::channel("aliPay")->error("回调失败,原因：" . $exception->getMessage());
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }

    /**
     * 创建订单
     * @throws Throwable
     */
    public function createOrder()
    {
        DB::beginTransaction();
        try {
            $result = $this->service->createOrder($this->getData('data'), 1);
            DB::commit();
            send_response($result);
        } catch (Exception$exception) {
            DB::rollBack();
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }

    /**
     * 检查订单是否支付
     * @throws Throwable
     */
    public function checkOrder()
    {
        DB::beginTransaction();
        try{
            $result = $this->service->checkOrder(auth('api')->id(),request('orderSn'));
            DB::commit();
            send_response($result);
        }catch (Exception$exception){
            DB::rollBack();
            send_response([],$exception->getCode(),$exception->getMessage());
        }
    }

    /**
     * 取消订单
     * @throws Throwable
     */
    public function cancelOrder()
    {
        DB::beginTransaction();
        try {
            $result = $this->service->cancelOrder(auth('api')->id(), request('orderSn'));
            DB::commit();
            send_response($result);
        } catch (Exception$exception) {
            DB::rollBack();
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }

    /**
     * 获取订单详情
     */
    public function detailOrder()
    {
        try {
            $result = $this->service->detailOrder(auth('api')->id(),request('orderSn'));
            send_response($result);
        } catch (Exception$exception) {
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }

    /**
     * 获取订单列表
     */
    public function listOrder()
    {
        try {
            $result = $this->service->listOrder(1, $this->getData('search'));
            send_response($result);
        } catch (Exception$exception) {
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }

    /**
     * 确认订单
     * @throws Throwable
     */
    public function confirmOrder(){
        DB::beginTransaction();
        try{
            $result = $this->service->co();
            DB::commit();
            send_response($result);
        }catch (Exception$exception){
            DB::rollBack();
            send_response([],$exception->getCode(),$exception->getMessage());
        }
    }

    /**
     * 订单支付
     * @throws Throwable
     */
    public function payOrder()
    {
        DB::beginTransaction();
        try {
            $result = $this->service->payOrder(auth('api')->id(),$this->getData('data'));
            DB::commit();
            send_response($result);
        } catch (Exception $exception) {
            DB::rollBack();
            send_response([], $exception->getCode(), $exception->getMessage());
        }
    }
}
