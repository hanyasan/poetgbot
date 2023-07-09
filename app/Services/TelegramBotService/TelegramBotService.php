<?php

namespace App\Services\TelegramBotService;

use Longman\TelegramBot\Telegram;

class TelegramBotService implements TelegramBotContract
{

    public function getTelegram(): Telegram
    {
        return new Telegram(
            config('telegram.api_key'),
            config('telegram.bot_username')
        );
    }

    public function setTelegramWebhook(): void
    {
        $this->getTelegram()->setWebhook('https://hanyasan.ru/hook/telegram');
    }

    public function getTelegramWithCommands(): Telegram
    {
        return $this
            ->getTelegram()
            ->addCommandsPaths(config('telegram.commands.paths'))
            ->enableAdmins(config('telegram.admins'));
    }
}
