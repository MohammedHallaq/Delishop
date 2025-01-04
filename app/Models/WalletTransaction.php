<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable=[
        'wallet_id',
        'transaction_type',
        'amount',
        'balance_after_transaction'
    ];
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
