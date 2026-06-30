<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BotService;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request, BotService $botService)
    {
        try {
            // گرفتن کل payload
            $update = $request->all();

            // لاگ برای دیباگ
            Log::info('WEBHOOK RECEIVED', [
                'payload' => $update,
                'headers' => $request->headers->all()
            ]);

            // اجرای سرویس بات
            $result = $botService->handle($update);

            return response()->json([
                'ok' => true,
                'result' => $result
            ]);

        } catch (\Throwable $e) {

            Log::error('WEBHOOK ERROR', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'error'
            ], 500);
        }
    }
}