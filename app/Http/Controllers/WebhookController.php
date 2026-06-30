<?php

namespace App\Http\Controllers;

use App\Services\BotService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(
        Request $request,
        BotService $bot
    )
    {
        $result = $bot->handle(
            $request->all()
        );

        return response()->json([
            'ok' => true,
            'message' => $result
        ]);
    }
}