<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Product
 * @package App\Models
 * @mixin \Eloquent
 */
class Product extends Model
{
    use HasFactory;
    protected $table="product";
    protected $fillable=["shopId","shipId","stockId","title","weight","amount","type","num","saleCount","status"];
}
