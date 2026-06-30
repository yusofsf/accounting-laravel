<?php

namespace App\Commands;

use App\Models\SilverTransaction;

class LastCommand implements CommandInterface
{
    public function execute(array $arguments, int $userId): string
    {
        $t = SilverTransaction::where('user_id', $userId)
            ->latest()
            ->first();

        if (!$t) {
            return "تراکنشی یافت نشد.";
        }

        return
            "آخرین تراکنش:

نوع: {$t->type}
وزن: {$t->weight}
قیمت: {$t->price}
مبلغ: {$t->amount}";
    }
}