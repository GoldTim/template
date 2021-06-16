<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ShipInfo
 * @package App\Models
 * @mixin \Eloquent
 * @property integer $id
 * @property string $name
 * @property string $address
 * @property string $sendTime
 * @property integer $type
 * @property integer $chargingType
 * @property integer $isFree
 */
class ShipInfo extends Model
{
    use HasFactory;

    protected $table = 'shopShip';
    protected $fillable = ['name', 'address', 'sendTime', 'type', 'chargingType', 'isFree'];
    protected $casts = [
        'address' => 'array'
    ];
}
