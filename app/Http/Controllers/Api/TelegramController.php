<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    /**
     * Get Telegram settings
     */
    public function getSettings()
    {
        return response()->json([
            'telegram_bot_token' => Setting::get('telegram_bot_token', ''),
            'telegram_chat_id' => Setting::get('telegram_chat_id', ''),
            'telegram_notify_sales' => Setting::get('telegram_notify_sales', '0'),
            'telegram_notify_low_stock' => Setting::get('telegram_notify_low_stock', '0'),
            'low_stock_threshold' => Setting::get('low_stock_threshold', '5'),
        ]);
    }

    /**
     * Save Telegram settings
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'telegram_bot_token' => 'nullable|string',
            'telegram_chat_id' => 'nullable|string',
            'telegram_notify_sales' => 'nullable|string',
            'telegram_notify_low_stock' => 'nullable|string',
            'low_stock_threshold' => 'nullable|integer|min:1',
        ]);

        foreach (['telegram_bot_token', 'telegram_chat_id', 'telegram_notify_sales', 'telegram_notify_low_stock', 'low_stock_threshold'] as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->$key);
            }
        }

        return response()->json(['message' => 'Sozlamalar saqlandi']);
    }

    /**
     * Send test message
     */
    public function test(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'chat_id' => 'required|string',
        ]);

        // Vaqtinchalik token va chat_id bilan test
        Setting::set('telegram_bot_token', $request->token);
        Setting::set('telegram_chat_id', $request->chat_id);

        $telegram = new TelegramService();
        $success = $telegram->sendMessage("✅ <b>Test xabar!</b>\n\nPOS tizimi Telegram integratsiyasi muvaffaqiyatli sozlandi.\n🕐 " . now()->format('d.m.Y H:i:s'));

        if ($success) {
            return response()->json(['message' => 'Test xabar muvaffaqiyatli yuborildi!']);
        }

        return response()->json(['message' => 'Xabar yuborilmadi. Token yoki Chat ID ni tekshiring.'], 422);
    }
}
