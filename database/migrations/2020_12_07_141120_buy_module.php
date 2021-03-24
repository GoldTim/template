<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BuyModule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('shop',function(Blueprint  $table) {
            $table->id();
            $table->integer('uId')->comment('用户ID');
            $table->string('name',100)->comment('店名');
            $table->timestamps();
        });
        Schema::create('shopCoupon',function(Blueprint  $table){
           $table->id();
           $table->integer('shopId');
           $table->string('couponName');
           $table->tinyInteger('type');
           $table->date('startDate');
           $table->date('endDate');
           $table->double('amount');
           $table->decimal('dNum');
           $table->integer('issueNum');
           $table->integer('limitNum');
        });
        Schema::create('shopShip',function(Blueprint  $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->comment('发货地址');
            $table->tinyInteger('sendTime')->comment('发货时间');
            $table->tinyInteger('type')->comment('运费类型:0自定义/1包邮');
            $table->tinyInteger('chargingType')->comment('计费方式:0件数/1重量/2体积');
            $table->tinyInteger('isFree')->comment('是否指定包邮条件')->default(array_search('否', config('params.status')));
            $table->timestamps();
        });
        Schema::create('shipDetail',function(Blueprint $table){
            $table->id();
            $table->integer('shipId')->comment('模板ID');
            $table->tinyInteger('type')->comment('运输方式')->default(array_search('快递',config('params.sendType')));
            $table->string('province')->comment('省份');
            $table->string('city')->comment('城市');
            $table->double('dNum')->comment('首件')->default(1);
            $table->decimal('dAmount')->comment('首费')->default(0);
            $table->double('cNum')->comment('续件')->default(1);
            $table->decimal('renew')->comment('续费')->default(0);
            $table->timestamps();
        });
        Schema::create('shipTerm',function (Blueprint $table) {
            $table->id();
            $table->integer('shipId')->comment('模板ID');
            $table->string('province')->comment('省份')->nullable();
            $table->string('city')->comment('城市')->nullable();
            $table->tinyInteger('type')->comment('运输方式');
            $table->tinyInteger('termType')->comment('包邮类型');
            $table->decimal('amount', 8, 2)->comment('金额');
            $table->integer('number')->default(0)->comment('数字');
            $table->timestamps();
        });
        Schema::create('product',function(Blueprint  $table) {
            $table->id();
            $table->integer('shopId');
            $table->integer('shipId')->comment('运费模板')->nullable();
            $table->string('stockId')->comment('产品编号');
            $table->string('title')->comment('标题');
            $table->decimal('weight', 6, 2)->comment('重量')->default(1);
            $table->decimal('amount', 8, 2)->comment('支付金额');
            $table->tinyInteger('type')->comment('类型')->default(1);
            $table->integer('num')->comment('库存')->default(1);
            $table->integer('saleCount')->comment('销量')->default(0);
            $table->tinyInteger('status')->comment('产品状态');
            $table->timestamps();
        });
        DB::statement('Create View productView as select shopId,shipId,stockId,title,weight,amount,type,num,saleCount from product where status='.array_search('上架',config('params.productStatus')).' order by saleCount Desc');
        Schema::create('productSku',function(Blueprint $table) {
            $table->id();
            $table->string('stockId')->comment('产品编号');
            $table->string('skuId')->comment('型号ID')->nullable();
            $table->string('colorId')->comment('颜色')->nullable();
            $table->decimal('skuAmount', 8, 2);
            $table->integer('skuNum')->comment('库存')->default(1);
            $table->string('skuImage')->comment('型号图片')->nullable();
            $table->index(['stockId', 'skuId', 'colorId']);
            $table->timestamps();
        });
        DB::statement("Create View skuView as select productSku.id,shopId,shipId,product.stockId,title,weight,amount,type,num,skuId,colorId,skuAmount,skuNum,skuImage,product.created_at,product.updated_at from productSku join product on productSku.stockId = product.stockId;");
        Schema::create('orderMaster', function (Blueprint $table) {
            $table->id();
            $table->string("orderMsn",64)->comment('支付编号');
            $table->string('uniqueKey')->comment('唯一值');
            $table->decimal('actualAmount',8,2)->comment('订单金额')->default(0);
            $table->timestamps();
        });
        Schema::create('orderInfo', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('orderSn')->comment('订单编号');
            $table->string('shopId')->comment('商家ID')->default(0);
            $table->string('paySn')->comment('支付编号')->nullable();
            $table->tinyInteger('payMethod')->comment('支付方式')->nullable();
            $table->tinyInteger('orderType')->comment('订单类型');
            $table->string('orderName')->comment('订单标题');
            $table->integer('customer')->comment('买家ID');
            $table->decimal('orderAmount')->comment('订单金额')->default(0);
            $table->decimal('actualAmount')->comment('实付金额')->default(0);
            $table->decimal('shipAmount')->comment('运费')->default(0);
            $table->decimal('disAmount')->comment('优惠金额')->default(0);
            $table->decimal('disPoint')->comment('使用积分')->default(0);
            $table->string('couponCode')->comment('优惠券码')->nullable();
            $table->string('orderRemark')->comment('订单备注')->nullable();
            $table->tinyInteger('status')->comment('订单状态')->default(0);
            $table->timestamps();
        });
        Schema::create('orderDetail', function (Blueprint $table) {
            $table->id();
            $table->string('orderSn', 64)->comment('订单编号');
            $table->string('stockId', 64)->comment('产品编号');
            $table->string('name', 64)->comment('产品名称');
            $table->string('skuId', 30)->comment('产品型号编码')->nullable();
            $table->string('colorId',30)->comment('产品颜色')->nullable();
            $table->string('skuName', 100)->comment('产品型号名称')->nullable();
            $table->string("cover", 300)->comment("规格封面");
            $table->integer('quantity')->comment('产品数量')->default(0);
            $table->decimal('amount')->comment('产品单价')->default(0);
            $table->decimal('tAmount')->comment('应付总价')->default(0);
            $table->decimal('discount')->comment('产品优惠')->default(0);
            $table->decimal('tDiscount')->comment('合计优惠')->default(0);
            $table->timestamps();
        });
        Schema::create('orderShipping', function (Blueprint $table) {
            $table->id();
            $table->string('orderSn')->comment('订单编号');
            $table->string('shippingSn')->comment('运输编码')->nullable();
            $table->tinyInteger('type')->comment('地址类型(0收件地址1账单地址')->default(0);
            $table->string("userName")->comment("收件人姓名");
            $table->string("phone")->comment("联系电话");
            $table->string("province")->comment("省份");
            $table->string("city")->comment("城市");
            $table->string("address")->comment("地址");
            $table->string('email')->comment('联系邮箱')->nullable();
            $table->tinyInteger('status')->comment('运输状态')->default(0);
            $table->timestamps();
        });
        Schema::create("orderLog",function (Blueprint $table){
            $table->id();
            $table->string("orderSn");
            $table->string("detail");
            $table->timestamps();
        });
        Schema::create('orderAfter',function(Blueprint $table){
            $table->id();
            $table->string('');
        });
        Schema::create("orderRefund",function(Blueprint $table) {
            $table->id();
            $table->string("orderSn");
            $table->string("detail");
            $table->tinyInteger("type")->comment('退款');
            $table->decimal("actualAmount");
            $table->decimal("refundAmount");
            $table->tinyInteger("status");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists("shop");
        Schema::dropIfExists("shopCoupon");
        Schema::dropIfExists("shopShip");
        Schema::dropIfExists("shipDetail");
        Schema::dropIfExists("shipTerm");
        Schema::dropIfExists("product");
        Schema::dropIfExists("productSku");
        Schema::dropIfExists("orderMaster");
        Schema::dropIfExists("orderInfo");
        Schema::dropIfExists("orderDetail");
        Schema::dropIfExists("orderShipping");
        DB::statement("Drop View productView;");
        DB::statement("Drop View skuView");
    }
}
