<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BotService
{
    public function handle(array $update)
    {
        // 🔥 ساخت پیام ID امن (برای همه سیستم‌ها)
        $messageId =
            data_get($update, 'message.id')
            ?? data_get($update, 'update_id')
            ?? hash('sha256', json_encode($update));

        $userId =
            data_get($update, 'user.id')
            ?? data_get($update, 'from.id')
            ?? 'guest';

        $text =
            data_get($update, 'message.text')
            ?? data_get($update, 'text')
            ?? '';

        // 🔥 idempotency (جلوگیری از دوبار پردازش)
        $exists = DB::table('processed_messages')
            ->where('message_id', $messageId)
            ->exists();

        if ($exists) {
            Log::info('DUPLICATE IGNORED', ['message_id' => $messageId]);
            return 'duplicate ignored';
        }

        DB::table('processed_messages')->insert([
            'message_id' => $messageId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 🔥 تست ساده
        if ($text === 'test') {
            return 'bot is working';
        }

        return 'ok message';
    }
}