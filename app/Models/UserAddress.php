<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserAddress
 * @package App\Models
 * @mixin Eloquent
 */
class UserAddress extends Model
{
    use HasFactory;
    protected $table="userAddress";
    protected $fillable = [
        "uId",
        "lastName",
        "firstName",
        "contactEmail",
        "contactPhone",
        "country",
        "province",
        "city",
        "area",
        "company",
        "addressLine1",
        "addressLine2",
        "postCode","status","isDefault"
    ];
}
