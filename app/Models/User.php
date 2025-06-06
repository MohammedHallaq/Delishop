<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'password',
        'role_id',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function storeRatings()
    {
        return $this->hasMany(StoreRating::class);
    }

    public function productRatings()
    {
        return $this->hasMany(ProductRating::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
    public function location()
    {
        return $this->hasMany(Locations::class);
    }
    public function notification()
    {
        return $this->hasMany(Notification::class);
    }

    public function keyword()
    {
        return $this->hasMany(Keyword::class);
    }

    public function createdRegistration()
    {
       return $this->hasMany(Registrations::class,);
    }
    public function creatorRegistration()
    {
        return $this->belongsTo(Registrations::class);
    }

}
