<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Setting;

class TelegramService
{
    protected ?string $token;
    protected ?string $chatId;

    public function __construct()
    {
        $this->token = Setting::get('telegram_bot_token');
        $this->chatId = Setting::get('telegram_chat_id');
    }

    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->chatId);
    }

    public function sendMessage(string $text, ?string $chatId = null): bool
    {
        if (!$this->token) return false;

        $targetChat = $chatId ?? $this->chatId;
        if (!$targetChat) return false;

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id' => $targetChat,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            \Log::error('Telegram send error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendSaleNotification($sale): bool
    {
        if (!$this->isConfigured()) return false;
        if (!Setting::get('telegram_notify_sales')) return false;

        $items = '';
        foreach ($sale->items as $item) {
            $items .= "  • {$item->product_name} x{$item->quantity} = " . number_format($item->total, 0, '.', ',') . "\n";
        }

        $paymentMethod = $sale->payments->first()?->method ?? 'noma\'lum';
        $methodLabel = match($paymentMethod) {
            'cash' => '💵 Naqd',
            'card' => '💳 Karta',
            'debt' => '📝 Nasiya',
            default => $paymentMethod,
        };

        $text = "🛒 <b>Yangi sotuv!</b>\n\n"
            . "📋 Chek: {$sale->sale_number}\n"
            . ($sale->customer ? "👤 Mijoz: {$sale->customer->name}\n" : "")
            . "👨‍💼 Kassir: {$sale->cashier->name}\n\n"
            . "<b>Tovarlar:</b>\n{$items}\n"
            . "💰 <b>Jami: " . number_format($sale->total, 0, '.', ',') . " so'm</b>\n"
            . "💳 To'lov: {$methodLabel}\n"
            . "🕐 " . $sale->created_at->format('d.m.Y H:i');

        return $this->sendMessage($text);
    }

    public function sendLowStockAlert($products): bool
    {
        if (!$this->isConfigured()) return false;
        if (!Setting::get('telegram_notify_low_stock')) return false;

        $list = '';
        foreach ($products as $p) {
            $list .= "  ⚠️ {$p->name} — qoldiq: <b>{$p->stock_quantity}</b>\n";
        }

        $text = "📦 <b>Omborda kam qolgan tovarlar!</b>\n\n{$list}\n"
            . "Iltimos, ombor zaxirasini to'ldiring.";

        return $this->sendMessage($text);
    }

    public function sendDailyReport($data): bool
    {
        if (!$this->isConfigured()) return false;

        $text = "📊 <b>Kunlik hisobot</b> — " . date('d.m.Y') . "\n\n"
            . "🛒 Sotuvlar soni: <b>{$data['sales_count']}</b>\n"
            . "💰 Jami tushum: <b>" . number_format($data['total_revenue'], 0, '.', ',') . " so'm</b>\n"
            . "💵 Naqd: <b>" . number_format($data['cash_total'], 0, '.', ',') . " so'm</b>\n"
            . "💳 Karta: <b>" . number_format($data['card_total'], 0, '.', ',') . " so'm</b>\n"
            . "📝 Nasiya: <b>" . number_format($data['debt_total'] ?? 0, 0, '.', ',') . " so'm</b>\n";

        return $this->sendMessage($text);
    }
}
