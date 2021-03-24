<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserCoupon
 * @package App\Models
 * @mixin Eloquent
 */
class UserCoupon extends Model
{
    use HasFactory;

    protected $table = "userCoupon";
    protected $fillable = ["uId", "couponId", "status"];
    protected $attributes = ["status" => 0];
}
