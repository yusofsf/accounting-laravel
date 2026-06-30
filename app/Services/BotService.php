<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BotService
{
    public function handle(array $update): string
    {
        Log::info('Webhook', $update);

        $messageId = data_get($update, 'message.id');
        $userId = data_get($update, 'user.id');

        $exists = DB::table('processed_messages')
            ->where('message_id', $messageId)
            ->exists();

        if ($exists) {
            return "duplicate ignored";
        }

// ثبت پیام
        DB::table('processed_messages')->insert([
            'message_id' => $messageId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
         * این قسمت را بعداً با فرمت واقعی روبیکا جایگزین می‌کنیم.
         */

        $userId = data_get($update, 'user.id');
        $name = data_get($update, 'user.name', 'Unknown');

        $username = data_get($update, 'user.username');

        $groupId = data_get($update, 'group.id');

        $groupTitle = data_get($update, 'group.title', 'Group');

        $text = trim(data_get($update, 'text', ''));

        if (!$userId) {
            return 'User not found';
        }

        $user = User::firstOrCreate(
            [
                'rubika_user_id' => $userId
            ],
            [
                'name' => $name,
                'username' => $username
            ]
        );

        Wallet::firstOrCreate(
            [
                'user_id' => $user->id
            ],
            [
                'balance' => 0
            ]
        );

        if ($groupId) {

            Group::firstOrCreate(
                [
                    'rubika_group_id' => $groupId
                ],
                [
                    'title' => $groupTitle
                ]
            );

        }

        return app(CommandService::class)
            ->execute($text,$user->id);
    }
}