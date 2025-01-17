<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $user=User::query()->where('phone_number',$request['phone_number'])->first();
        $wallet = Wallet::query()->where('user_id',$user->id)->first();
        if (is_null($wallet)){
             $wallet =Wallet::query()->create([
                'user_id'=>$user->id,
                'balance' => 0
            ]);
        }
        $wallet->balance+=$request['balance'];
        $wallet->save();

        WalletTransaction::query()->create([
            'wallet_id'=> $wallet->id,
            'transaction_type' => 'deposit',
            'amount'=>$request['balance'],
            'balance_after_transaction'=>$wallet->balance
        ]);

        ( new NotificationController )->sendNotification($user,' Wallet ',' A balance worth : '.$request['balance'].' has been transferred to your wallet ',$wallet);

        return ResponseFormatter::success('deposit wallet successfully',$wallet,200);

    }

    public function getMyBalance()
    {
        $wallet = Wallet::query()->where('user_id',Auth::id())->first();
        if (is_null($wallet)){
            Wallet::query()->create([
                'user_id'=>Auth::id(),
                'balance' => 0
            ]);
        }


        return ResponseFormatter::success('Wallet balance retrieved successfully',['balance'=>$wallet->balance],200);
    }
    public function getTransactionDeposit()
    {
        $transactions = WalletTransaction::query()->where('transaction_type','deposit')->with('wallet.user')->get();
         $formattedTransactions = $transactions->map(function ($transaction) {
            return [
                'phone_number' => $transaction->wallet->user->phone_number, 
                'amount' => $transaction->amount,
                'created_at' => $transaction->created_at,
            ];
        });
        return ResponseFormatter::success('get transactions deposit successfully ',$formattedTransactions,200);
    }

}
