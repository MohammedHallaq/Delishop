<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    protected $fillable = [
        'user_id',
        'keyword'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
