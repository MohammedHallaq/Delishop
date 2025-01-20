<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registrations extends Model
{
    protected $fillable=[
        'created_user_id',
        'creator_user_id',
        'role_id',
        'process_type'
    ];
    public function role()
    {
       return $this->belongsTo(Role::class,'role_id');
    }
    public function createdUser()
    {
        return $this->belongsTo(User::class,'created_user_id');
    }
    public function creatorUser()
    {
        return $this->belongsTo(User::class,'creator_user_id');
    }
}
