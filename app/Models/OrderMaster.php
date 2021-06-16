<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderMaster
 * @package App\Models
 * @mixin \Eloquent
 */
class OrderMaster extends Model
{
    use HasFactory;

    protected $table = "orderMaster";
    protected $fillable = [""];

    public function setOrderMsnAttribute($value)
    {

    }
}
