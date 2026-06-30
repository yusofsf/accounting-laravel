<?php

namespace App\Commands;

use App\Models\SilverTransaction;

class TransactionsCommand implements CommandInterface
{
    public function execute(array $arguments, int $userId): string
    {
        $items = SilverTransaction::where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get();

        if ($items->isEmpty()) {
            return "هیچ تراکنشی وجود ندارد.";
        }

        $text = "آخرین تراکنش‌ها:\n\n";

        foreach ($items as $t) {
            $text .=
                strtoupper($t->type) .
                " | وزن: {$t->weight} | قیمت: {$t->price} | مبلغ: {$t->amount}\n";
        }

        return $text;
    }
}