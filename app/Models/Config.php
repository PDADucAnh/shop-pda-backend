<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Config extends Model
{
    use HasFactory;
    protected $table = 'configs';
    protected $fillable = ['site_name', 'email', 'phone', 'hotline', 'address', 'status'];
    public $timestamps = false;
}
