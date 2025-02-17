<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable=[
        'store_id',
        'name',
        'description',
        'product_picture',
        'price',
        'discount',
        'quantity'
    ];

    protected $casts = [
        'price' => 'double',
        'discount' => 'double',
        'quantity' => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'products_orders')
            ->withPivot('number');
    }

    public function ratings()
    {
        return $this->hasMany(ProductRating::class);
    }
}
