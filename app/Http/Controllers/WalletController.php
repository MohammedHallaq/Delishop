<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'phone_number'=>'required|regex:/^09\d{8}$/|exists:users,phone_number',
            'balance' => 'required|numeric '
        ]);
        if ($validator->fails())
            return ResponseFormatter::error('validation errors',$validator->errors(),422);

        $user=User::query()->where('phone_number',$request->phone_number)->first();
        $wallet = Wallet::query()->where('user_id',$user->id)->first();
        if (is_null($wallet)){
             $wallet =Wallet::create([
                'user_id'=>$user->id,
                'balance' => 0
            ]);
        }
        $wallet->balance+=$request->balance;
        $wallet->save();

        WalletTransaction::create([
            'wallet_id'=> $wallet->id,
            'transaction_type' => 'deposit',
            'amount'=>$request->balance,
            'balance_after_transaction'=>$wallet->balance
        ]);


        return ResponseFormatter::success('deposit wallet successfully',$wallet,200);

    }

    public function getMyBalance(Request $request)
    {
        
        $user = $request->user(); 

        
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],  // Search criteria
            ['balance' => 0]          // Default values if not found
        );

        // Return the wallet balance
        return ResponseFormatter::success('Wallet balance retrieved successfully', [
            'balance' => $wallet->balance
        ], 200);
    }

}
