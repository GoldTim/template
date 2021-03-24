<?php


namespace App\Models\View;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Product
 * @package App\Models\View
 * @mixin \Eloquent
 */
class Product extends Model
{
    use HasFactory;
    protected $table="productView";
}
