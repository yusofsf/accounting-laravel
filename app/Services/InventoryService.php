<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\SilverTransaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function buy(int $userId, float $weight, float $price): string
    {
        return DB::transaction(function () use ($userId, $weight, $price) {

            $amount = $weight * $price;

            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

            if ($wallet->balance < $amount) {
                return "موجودی کافی نیست.";
            }

            // کم کردن کیف پول
            $wallet->decrement('balance', $amount);

            // گرفتن انبار
            $inventory = Inventory::first();

            if (!$inventory) {
                $inventory = Inventory::create([
                    'silver_weight' => 0,
                    'average_price' => 0,
                ]);
            }

            // محاسبه میانگین قیمت جدید
            $totalWeight = $inventory->silver_weight + $weight;

            $inventory->average_price =
                (($inventory->silver_weight * $inventory->average_price) + ($weight * $price))
                / $totalWeight;

            $inventory->silver_weight = $totalWeight;

            $inventory->save();

            SilverTransaction::create([
                'user_id' => $userId,
                'type' => 'buy',
                'weight' => $weight,
                'price' => $price,
                'amount' => $amount,
                'description' => 'خرید نقره',
            ]);

            return "خرید انجام شد.\nوزن: $weight\nقیمت: $price";
        });
    }

    public function sell(int $userId, float $weight, float $price): string
    {
        return DB::transaction(function () use ($userId, $weight, $price) {

            $amount = $weight * $price;

            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

            $inventory = Inventory::first();

            if (!$inventory || $inventory->silver_weight < $weight) {
                return "موجودی نقره کافی نیست.";
            }

            // اضافه کردن پول به کیف پول
            $wallet->increment('balance', $amount);

            // کاهش موجودی انبار
            $inventory->silver_weight -= $weight;

            $inventory->save();

            SilverTransaction::create([
                'user_id' => $userId,
                'type' => 'sell',
                'weight' => $weight,
                'price' => $price,
                'amount' => $amount,
                'description' => 'فروش نقره',
            ]);

            return "فروش انجام شد.\nوزن: $weight\nقیمت: $price";
        });
    }

    public function inventory(): string
    {
        $inv = Inventory::first();

        if (!$inv) {
            return "انبار خالی است.";
        }

        return "موجودی نقره: {$inv->silver_weight}\nمیانگین قیمت: {$inv->average_price}";
    }
}