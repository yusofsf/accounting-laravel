<?php

namespace App\Commands;

use App\Models\SilverTransaction;
use App\Models\Wallet;
use App\Models\Inventory;

class ReportCommand implements CommandInterface
{
    public function execute(array $arguments, int $userId): string
    {
        $wallet = Wallet::where('user_id', $userId)->first();

        $buy = SilverTransaction::where('user_id', $userId)
            ->where('type', 'buy')
            ->sum('amount');

        $sell = SilverTransaction::where('user_id', $userId)
            ->where('type', 'sell')
            ->sum('amount');

        $profit = $sell - $buy;

        $inv = Inventory::first();

        return
            "📊 گزارش مالی

💰 کیف پول: {$wallet->balance}

📥 خرید کل: {$buy}
📤 فروش کل: {$sell}

📈 سود/زیان: {$profit}

📦 موجودی انبار: {$inv->silver_weight}";
    }
}