<?php

namespace App\Services\TelegramBotService;

use Longman\TelegramBot\Telegram;

final class TelegramBotService implements TelegramBotContract
{

    #[\Override]
    public function getTelegram(): Telegram
    {
        return new Telegram(
            config('telegram.api_key'),
            config('telegram.bot_username')
        );
    }

    #[\Override]
    public function setTelegramWebhook(): void
    {
        $this->getTelegram()->setWebhook('https://hanyasan.ru/hook/telegram');
    }

    #[\Override]
    public function getTelegramWithCommands(): Telegram
    {
        return $this
            ->getTelegram()
            ->addCommandsPaths(config('telegram.commands.paths'))
            ->enableAdmins(config('telegram.admins'));
    }
}
