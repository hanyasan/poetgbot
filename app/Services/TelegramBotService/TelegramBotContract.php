<?php

namespace App\Services\TelegramBotService;

use Longman\TelegramBot\Telegram;

interface TelegramBotContract
{
    public function getTelegram(): Telegram;

    public function setTelegramWebhook(): void;

    public function getTelegramWithCommands(): Telegram;
}
