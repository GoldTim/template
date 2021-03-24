<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserModule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('userCart', function (Blueprint $table) {
            $table->id();
            $table->integer('uId');
            $table->string('stockId');
            $table->string('skuId');
            $table->string('colorId');
            $table->integer('num');
            $table->timestamps();
        });
        Schema::create('userAddress', function (Blueprint $table) {
            $table->id();
            $table->integer('uId');
            $table->string("lastName")->comment("姓氏");
            $table->string("firstName")->comment("名字");
            $table->string("contactEmail")->comment("联系邮箱");
            $table->string("contactPhone")->comment("联系电话");
            $table->string("country")->comment("国家");
            $table->string("province")->comment("省份");
            $table->string("city")->comment("城市");
            $table->string("area")->comment("区域");
            $table->string("company")->comment("公司");
            $table->string("addressLine1")->comment("详细地址");
            $table->string("addressLine2")->comment("详细地址");
            $table->string("postCode")->comment("邮编");
            $table->tinyInteger("status")->comment("是否删除(0否1是")->default(0);
            $table->tinyInteger("isDefault")->comment("是否默认")->default(1);
            $table->timestamps();
        });
        Schema::create('userCoupon',function(Blueprint $table){
            $table->id();
            $table->integer('uId');
            $table->integer('couponId');
            $table->tinyInteger('status');
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
        Schema::dropIfExists("userAddress");
        Schema::dropIfExists("userCart");
    }
}
