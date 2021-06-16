<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ShipTerm
 * @package App\Models
 * @mixin \Eloquent
 */
class ShipTerm extends Model
{
    use HasFactory;

    protected $table = "shipTerm";
    protected $fillable = ["shipId", "province", "city", "type", "termType", "amount", "number"];
}
