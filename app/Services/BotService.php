<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BotService
{
    public function handle(array $update)
    {
        $messageId = data_get($update, 'message.id')
            ?? data_get($update, 'update_id')
            ?? uniqid();

        $userId = data_get($update, 'user.id')
            ?? data_get($update, 'from.id')
            ?? 'guest';

        $text = data_get($update, 'message.text')
            ?? data_get($update, 'text')
            ?? '';

        // idempotency
        $exists = DB::table('processed_messages')
            ->where('message_id', $messageId)
            ->exists();

        if ($exists) {
            return 'duplicate ignored';
        }

        DB::table('processed_messages')->insert([
            'message_id' => $messageId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 🔥 COMMAND ROUTER
        return $this->handleCommand($text, $userId);
    }

    private function handleCommand(string $text, $userId): string
    {
        $text = trim($text);

        return match ($text) {

            '/start' => "👋 سلام! ربات حسابداری نقره فعال شد.",

            '/help', 'help' =>
            "📌 دستورات:
            /start
            موجودی
            خرید
            فروش
            گزارش",

            'موجودی' => "💰 موجودی شما ...",

            default => "❌ دستور شناخته نشد"
        };
    }
}