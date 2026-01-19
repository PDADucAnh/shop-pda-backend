<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    //
    use HasFactory;
    protected $table = 'contacts';
    protected $fillable = ['user_id', 'name', 'email', 'phone', 'content', 'reply_id', 'status'];
}
