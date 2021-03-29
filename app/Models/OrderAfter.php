<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderAfter
 * @package App\Models
 * @mixin Eloquent
 */
class OrderAfter extends Model
{
    use HasFactory;

    protected $fillable = ["orderSn", "afterType", "isShip", "afterReason", "refundAmount", "actualAmount", "refundDescription", "picture"];

    protected $casts = [
        "picture" => "JSON"
    ];

    public function setPictureAttribute($value)
    {

    }
}
