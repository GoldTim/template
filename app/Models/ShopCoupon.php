<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ShopCoupon
 * @package App\Models
 * @mixin Eloquent
 */
class ShopCoupon extends Model
{
    use HasFactory;
    protected $table="shopCoupon";
}
