<?php

namespace App\Models\View;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserCoupon
 * @package App\Models\View
 * @mixin Eloquent
 */
class UserCoupon extends Model
{
    use HasFactory;
    protected $table="userCouponView";

    public function add($data)
    {
        return \App\Models\UserCoupon::create($data);
    }
}
