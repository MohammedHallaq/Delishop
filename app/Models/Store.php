<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable=[
        'user_id',
        'category_id',
        'name',
        'store_picture',
        'description',
        'location'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function ratings()
    {
        return $this->hasMany(StoreRating::class);
    }
    public function order()
    {
        return $this->hasMany(Order::class);
    }
}
