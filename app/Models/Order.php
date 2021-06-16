<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Order
 * @package App\Models
 * @mixin Eloquent;
 */
class Order extends Model
{
    use HasFactory;

    protected $table = "orderInfo";
    protected $fillable = [""];

    public function setPaySnAttribute($value)
    {

    }
}
