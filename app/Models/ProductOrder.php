<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'order_id',
        'price',
        'quantity',
        'subtotal'
    ];
    protected $table = 'product_orders';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
