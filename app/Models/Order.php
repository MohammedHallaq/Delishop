<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'order_date',
        'location_id'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'products_orders')
            ->withPivot('number');
    }
    public function location()
    {
        return $this->belongsTo(Locations::class);
    }
}
