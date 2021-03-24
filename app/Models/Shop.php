<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Shop
 * @package App\Models
 * @mixin Eloquent;
 */
class Shop extends Model
{
    use HasFactory;
    protected $table="shop";

}
