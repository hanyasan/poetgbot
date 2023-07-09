<?php

namespace App\Http\Controllers\Telegram;

use Illuminate\Routing\Controller as BaseController;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Exception\TelegramException;

class TelegramBotController extends BaseController
{
    public function set()
    {
        try {
            $telegram = new Telegram(
                '5919634038:AAG3HaPoe_cwX2gJJtidajsgQBWEbOsZ-E8',
                '@poeninja_price_checker_bot'
            );

            /**
             * REMEMBER to define the URL to your hook.php file in:
             * config.php: ['webhook']['url'] => 'https://your-domain/path/to/hook.php'
             */

            $result = $telegram->setWebhook('https://hanyasan.ru/hook/telegram');

            // To use a self-signed certificate, use this line instead
            // $result = $telegram->setWebhook($config['webhook']['url'], ['certificate' => $config['webhook']['certificate']]);

            echo $result->getDescription();
        } catch (TelegramException $e) {
            echo $e->getMessage();
        }
    }
}
