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

    public function productsOrder()
    {
        return $this->hasMany(ProductOrder::class);
    }

    public function location()
    {
        return $this->belongsTo(Locations::class);
    }

}
