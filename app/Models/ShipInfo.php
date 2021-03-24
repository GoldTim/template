<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ShipInfo
 * @package App\Models
 * @mixin \Eloquent
 */
class ShipInfo extends Model
{
    use HasFactory;
    protected $table='shopShip';
    protected $fillable=['name','address','sendTime','type','chargingType','isFree'];
    protected $casts=[
        'address'=>'array'
    ];
}
