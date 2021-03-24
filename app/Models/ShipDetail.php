<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ShipDetail
 * @package App\Models
 * @mixin \Eloquent
 */
class ShipDetail extends Model
{
    use HasFactory;
    protected $table="shipDetail";
    protected $fillable=["shipId","type","province","city","dNum","dAmount","cNum","renew"];
}
