<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable=[
        'name',
        'category_picture'
    ];
    public function stores()
    {
        return $this->hasMany(Store::class);
    }
}
