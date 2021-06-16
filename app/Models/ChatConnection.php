<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ChatConnection
 * @package App\Models
 * @mixin \Eloquent
 */
class ChatConnection extends Model
{
    use HasFactory;

    protected $table = "chatConnection";
    protected $fillable = ["roomId", "source", "fd", "status"];
}
