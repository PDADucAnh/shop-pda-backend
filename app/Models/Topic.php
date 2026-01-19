<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Topic extends Model
{
    use HasFactory;
    protected $table = 'topics';
    protected $fillable = ['name', 'slug', 'sort_order', 'description', 'status'];

    public function posts()
    {
        return $this->hasMany(Post::class, 'topic_id', 'id');
    }
}
