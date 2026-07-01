<?php

namespace App\Http\Controllers;

use App\Commands\BuyCommand;
use App\Services\CommandService;
use App\Services\TelegramClient;
use Illuminate\Http\Request;
use App\Services\BotService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class WebhookController extends Controller
{

    const BTN_SELL = 'فروش نقره';

    const BTN_BUY = 'خرید نقره';

    const BTN_INVENTORY = 'موجودی انبار';

    const BTN_BALANCE = 'موجودی کیف پول';

    const BTN_USERS = 'لیست کاربران';

    protected array $adminAllowed = [
        self::BTN_USERS,
    ];

    public function __construct(private CommandService $commandService)
    {

    }

    public function home()
    {
        return response('✅ Bot Webhook is running!', 200);
    }

    public function setWebhook(TelegramClient $tg)
    {
        $url = rtrim(config('telegram.webhook_url'), '/').'/'.config('telegram.token');
        $result = $tg->setWebhook($url);

        return response("Webhook set to: {$url} — result: ".$result->body(), 200);
    }

    public function webhook(Request $request, string $token, TelegramClient $tg)
    {
        if ($token !== config('telegram.token')) {
            abort(404);
        }

        try {
            $update = $request->json()->all();

            $userId = null;
            $text = null;
            $callbackData = null;

            if (isset($update['message'])) {
                $userId = $update['message']['from']['id'] ?? null;
                $text = isset($update['message']['text']) ? trim($update['message']['text']) : '';
            } elseif (isset($update['callback_query'])) {
                $userId = $update['callback_query']['from']['id'] ?? null;
                $callbackData = $update['callback_query']['data'] ?? null;
            }

            $admins = config('telegram.admins');

            // اجازه‌ی ویژه به ادمین حتی وقتی ربات خاموش است
            if (in_array($userId, $admins, true)) {
                if (
                    $text === '/start'
                    || in_array($callbackData, $this->adminAllowed, true)
                ) {
                    $this->processUpdate($update, $tg);

                    return response('ok', 200);
                }
            }

            // ربات خاموش است
                if (isset($update['callback_query'])) {
                    $tg->answerCallbackQuery($update['callback_query']['id'], '🔴 ربات خاموش است', true);
                } elseif (isset($update['message']['chat']['id']) && in_array($userId, $admins, true)) {
                    // ادمین یکی از دکمه‌های منو را زده ولی ربات خاموش است → پیام بده تا بی‌صدا نماند
                    $tg->sendMessage(
                        $update['message']['chat']['id'],
                        "🔴 ربات خاموش است\nبرای روشن کردن، دکمه‌ی «🟢 روشن کردن ربات» را بزنید.",
                        $this->mainKeyboard()
                    );


                return response('ok', 200);
            }

            $this->processUpdate($update, $tg);

            return response('ok', 200);
        } catch (\Throwable $e) {
            Log::error('🔥 WEBHOOK ERROR: '.$e->getMessage()."\n".$e->getTraceAsString());

            return response('error', 500);
        }
    }

    protected function processUpdate(array $update, TelegramClient $tg): void
    {
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query'], $tg);
        } elseif (isset($update['message']['text'])) {
            $text = trim($update['message']['text']);
            if ($text === '/start') {
                $this->handleStart($update['message'], $tg);
            } else {
                $this->handleText($update['message'], $tg);
            }
        }
    }

    protected function handleStart(array $message, TelegramClient $tg): void
    {
        $welcome = "سلام 👋\n\nبه ربات قیمت نقره خوش آمدید 🥈\nیکی از گزینه‌های زیر را انتخاب کنید:";

        $tg->sendMessage($message['chat']['id'], $welcome, $this->mainKeyboard());
    }

    /** کیبورد ثابت پایین صفحه (Reply Keyboard) به‌جای دکمه‌های inline */
    protected function mainKeyboard(): array
    {
        return [
            'keyboard' => [
                [['text' => self::BTN_BUY]],
                [['text' => self::BTN_SELL]],
                [['text' => self::BTN_INVENTORY]],
                [['text' => self::BTN_BALANCE]],
                [['text' => self::BTN_USERS]],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }

    protected function handleText(array $message, TelegramClient $tg): void
    {
        $userId = $message['from']['id'];
        $chatId = $message['chat']['id'];
        $text = trim($message['text']);
        $admins = config('telegram.admins');
        $isAdmin = in_array($userId, $admins, true);
        $state = $this->getState($userId);

        $reply = fn (string $t, ?array $kb = null) => $tg->sendMessage($chatId, $t, $kb, 'HTML');

        // فشردن یکی از دکمه‌های منوی ثابت (Reply Keyboard) همیشه اول بررسی می‌شود
        if ($this->handleMenuButton($text, $userId, $isAdmin, $reply)) {
            return;
        }

        // درصد خرید
        if ($state === 'users') {
            if (! $isAdmin) {
                $reply('❌ فقط ادمین مجاز است');
                $this->clearState($userId);

                return;
            }

        }

        // شمش 999
        if ($state === 'buy') {
            $reply('لطفا وزن را وارد کنید به گرم:');
            $clean = $this->digitsOnly($text);
            if ($clean === '') {
                $reply('❌ لطفاً عدد معتبر وارد کنید');

                return;
            }
            $trade[0] = $clean;
            $reply('لطفا مورد معامله را وارد کنید');
            $clean = $this->digitsOnly($text);
            if ($clean === '') {
                $reply('❌ لطفاً عدد معتبر وارد کنید');

                return;
            }
            $trade[1] = $clean;
            $this->commandService->execute('خرید'. json_encode($trade), $userId);

            return;
        }

        // شمش نادیر
        if ($state === 'bar_nadir') {
            if ($guard = $this->guard($isAdmin, $userId, $reply)) {
                return;
            }
            $clean = $this->digitsOnly($text);
            if ($clean === '') {
                $reply('❌ لطفاً عدد معتبر وارد کنید');

                return;
            }

            return;
        }

        // قیمت نقره 995
        if ($state === 'silver_995') {
            if ($guard = $this->guard($isAdmin, $userId, $reply)) {
                return;
            }
            $clean = $this->digitsOnly($text);
            if ($clean === '') {
                $reply('❌ لطفاً عدد معتبر وارد کنید');

                return;
            }
            $gram995 = (int) $clean;

            $this->fetchAndStore($reply);
            $this->clearState($userId);

            return;
        }

        // قیمت گرم 999/9
        if ($state === 'price') {
            if ($guard = $this->guard($isAdmin, $userId, $reply)) {
                return;
            }
            $clean = $this->digitsOnly($text);
            if ($clean === '') {
                $reply('❌ لطفاً عدد معتبر وارد کنید');

                return;
            }
            $this->fetchAndStore((int) $clean, $reply);
            $this->clearState($userId);

            return;
        }

        // پیش‌فرض
        $reply('ℹ️ از منوی پایین یکی از گزینه‌ها را انتخاب کنید');
    }

    protected function handleMenuButton(string $text, $userId, bool $isAdmin, callable $reply): bool
    {
        $menuTexts = [
            self::BTN_SELL, self::BTN_BUY, self::BTN_INVENTORY,
            self::BTN_BALANCE, self::BTN_USERS,
        ];

        if (! in_array($text, $menuTexts, true)) {
            return false;
        }

        $this->clearState($userId);

        if (! $isAdmin) {
            $reply('❌ دسترسی رد شد');

            return true;
        }

        switch ($text) {
            case self::BTN_SELL:
                $this->setState($userId, 'sell');
                $reply("✅ لطفاً قیمت گرم نقره را به تومان وارد کنید:\n\nمثال: 7500000");
                break;

            case self::BTN_BUY:
                $this->setState($userId, 'but');
                $reply("⚖️ لطفاً قیمت گرم نقره عیار 995 را به تومان وارد کنید:\n\nمثال: 7300000");
                break;

            case self::BTN_INVENTORY:
                $this->setState($userId, 'deposit');
                $reply("موجودی حساب شما:");
                break;

            case self::BTN_BALANCE:
                $reply('انبار  حساب شما:');
                break;

            case self::BTN_USERS:
                $reply('لیست کاربران');
                break;
        }

        return true;
    }

    /**
     * قیمت‌های لحظه‌ای را می‌گیرد، رکورد را درج و پیام تأیید به ادمین می‌فرستد.
     * (مشترک بین حالت price و silver_995)
     */
    protected function fetchAndStore(int $gramPrice, callable $reply): void
    {

        $now = Jalalian::now()->format('Y/m/d H:i');

        $text =
            "✅ مثقال 999/9:\n".
            "🔴 مثقال فروش: {$f($r['mithqal_price'])} تومان\n".
            "🟢 مثقال خرید: {$f($r['mithqal_price_buy'])} تومان\n\n".
            "⚖️ گرم 999/9:\n".
            "🔴 فروش: {$f($gramPrice)} تومان\n".
            "🟢 خرید: {$f($r['gram_price_buy'])} تومان\n\n".
            "✅  مثقال995: \n".
            "🔴 مثقال فروش: {$f($r['mithqal_995_price'])} تومان\n".
            "🟢 مثقال خرید: {$f($r['mithqal_995_price_buy'])} تومان\n\n".
            "⚖️ گرم 995:\n".
            "📅 {$now}";

        BotLog::info('📤 تأیید قیمت به ادمین ارسال شد', [
            'gram_price' => $gramPrice,
            'mithqal_price' => $r['mithqal_price'],
            'mithqal_price_buy' => $r['mithqal_price_buy'],
            'gram_price_buy' => $r['gram_price_buy'],
            'gram_995' => $gram995,
            'gram_995_buy' => $r['gram_995_buy'],
            'mithqal_995_price' => $r['mithqal_995_price'],
            'mithqal_995_price_buy' => $r['mithqal_995_price_buy'],
            'message_text' => $text,
        ]);

        $reply($text);
    }

    /** گارد مشترک ادمین/خاموشی. اگر باید برگردیم true می‌دهد. */
    protected function guard(bool $isAdmin, $userId, callable $reply): bool
    {
        if (! $isAdmin) {
            $reply('❌ فقط ادمین مجاز است');
            $this->clearState($userId);

            return true;
        }

        return false;
    }

    // ---------- state گفت‌وگو (جای context.user_data پایتون) ----------
    protected function stateKey($userId): string
    {
        return "tg:state:{$userId}";
    }

    protected function setState($userId, string $state): void
    {
        Cache::put($this->stateKey($userId), $state, now()->addMinutes(10));
    }

    protected function getState($userId): ?string
    {
        return Cache::get($this->stateKey($userId));
    }

    protected function clearState($userId): void
    {
        Cache::forget($this->stateKey($userId));
    }

    // ---------- کمکی اعداد ----------
    protected function normalizeDigits(string $s): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $ar = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($ar, $en, str_replace($fa, $en, $s));
    }

    protected function digitsOnly(string $s): string
    {
        return preg_replace('/\D/', '', $this->normalizeDigits($s));
    }
}