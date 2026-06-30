<?php

namespace App\Services;

use App\Models\Wallet;

class WalletService
{
    public function balance(int $userId): string
    {
        $wallet = Wallet::where('user_id', $userId)->first();

        return "موجودی کیف پول:\n".$wallet->balance;
    }

    public function deposit($userId,$amount): string
    {
        $wallet=Wallet::where('user_id',$userId)->first();

        $wallet->increment('balance',$amount);

        return "شارژ انجام شد.";
    }

    public function withdraw($userId,$amount): string
    {
        $wallet=Wallet::where('user_id',$userId)->first();

        if($wallet->balance<$amount)
            return "موجودی کافی نیست.";

        $wallet->decrement('balance',$amount);

        return "برداشت انجام شد.";
    }
}