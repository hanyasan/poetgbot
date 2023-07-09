<?php

namespace App\Http\Controllers\Telegram;

use App\Services\TelegramBotService\TelegramBotContract;
use Illuminate\Routing\Controller as BaseController;
use Longman\TelegramBot\Exception\TelegramException;

class TelegramBotController extends BaseController
{
    public function __construct(
        protected TelegramBotContract $telegramBot
    ) {}

    public function set()
    {
        try {
            $this->telegramBot->setTelegramWebhook();
        } catch (TelegramException $e) {
            echo $e->getMessage();
        }
    }
}
