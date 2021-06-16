<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use function request;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $_requestData = [];

    protected $code = 10000;

    protected $data = [];

    protected $message = "";

    protected $_headers = [];

    protected $_version = '1.0.0';

    protected $_token = '';

    protected $_device = '';

    protected static $search = [];

    protected static $length = 10;

    protected static $page = 1;

    protected static $uid = 0;

    protected static $shopId = 0;

    public function __construct()
    {
        $this->parseRequest();
    }

    private function parseRequest()
    {
        $this->_requestData = getPostData();
        $this->_headers = request()->header();
        $this->_version = isset($this->_headers['version']) ? $this->_headers['version'] : '1.0.0';
        $this->_device = @$this->_headers['device'] ? reset($this->_headers['device']) : '';

        self::$page = $this->getData('page', true, 1);
        self::$length = $this->getData('length', true, 10);
        self::$search = $this->getData('search', true, []);
        self::$uid = env('APP_ENV') == 'local' ? 0 : auth('api')->id();
    }


    protected function getData($key = '', $optional = false, $default = '')
    {
        if ($key !== null && empty($key))
            return $this->_requestData;

        if (@array_key_exists($key, $this->_requestData))
            return $this->_requestData[$key];

        if (!@array_key_exists($key, $this->_requestData) && $optional) return $default; //可选参数且接收不到此参数

        //throw new \Exception('获取参数不存在或缺少参数', 404);
        send_response([], 404, '获取参数不存在或缺少参数:' . $key);
    }


    public function saveLog($content, $path, $level)
    {
    }
}
