<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreRating extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
