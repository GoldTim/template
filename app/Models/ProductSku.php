<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductSku
 * @package App\Models
 * @mixin \Eloquent
 */
class ProductSku extends Model
{
    use HasFactory;
    protected $table='productSku';
    protected $fillable=['stockId','skuId','colorId','skuAmount','skuNum','skuImage'];
}
