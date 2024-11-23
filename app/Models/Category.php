<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public function stores()
    {
        return $this->hasMany(Store::class);
    }
}
