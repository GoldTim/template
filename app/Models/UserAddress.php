<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserAddress
 * @package App\Models
 * @mixin Eloquent
 * @property string $lastName
 * @property string $firstName
 * @property string $contactEmail
 * @property string $contactPhone
 * @property string $country
 * @property string $province
 * @property string $city
 * @property string $area
 * @property string $company
 * @property string $addressLine1
 * @property string $addressLine2
 * @property string $postCode
 * @property integer $status
 * @property integer $isDefault
 */
class UserAddress extends Model
{
    use HasFactory;

    protected $table = "userAddress";
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
        "postCode", "status", "isDefault"
    ];
}
