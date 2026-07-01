<?php
return [
// توکن ربات از BotFather
'token' => env('BOT_TOKEN', ''),

// آدرس عمومی اپ؛ وب‌هوک روی WEBHOOK_URL/TOKEN ست می‌شود
'webhook_url' => env('WEBHOOK_URL', ''),

// آیدی عددی ادمین‌ها (با کاما جدا)
'admins' => array_values(array_filter(array_map(
'intval',
explode(',', (string) env('TELEGRAM_ADMINS', '271469412'))
))),

];