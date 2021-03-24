<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ChatMessage
 * @package App\Models
 * @mixin \Eloquent
 */
class ChatMessage extends Model
{
    use HasFactory;
    protected $table="chatMessage";
    protected $fillable=["type","source","message","status","roomId"];
}
