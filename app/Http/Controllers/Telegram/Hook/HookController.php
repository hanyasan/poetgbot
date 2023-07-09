<?php

namespace App\Http\Controllers\Telegram\Hook;

use Illuminate\Routing\Controller as BaseController;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Exception\TelegramLogException;
use Longman\TelegramBot\Telegram;

class HookController extends BaseController
{
    public function handle()
    {
        try {
            $telegram = new Telegram(
                '5919634038:AAG3HaPoe_cwX2gJJtidajsgQBWEbOsZ-E8',
                '@poeninja_price_checker_bot'
            );

            // Enable admin users
//            $telegram->enableAdmins($config['admins']);

            // Add commands paths containing your custom commands
//            $telegram->addCommandsPaths($config['commands']['paths']);

            // Requests Limiter (tries to prevent reaching Telegram API limits)
//            $telegram->enableLimiter($config['limiter']);

            // Handle telegram webhook request
            $telegram->handle();
        } catch (TelegramException $e) {
            // Log telegram errors
//            Longman\TelegramBot\TelegramLog::error($e);

            // Uncomment this to output any errors (ONLY FOR DEVELOPMENT!)
            // echo $e;
        } catch (TelegramLogException $e) {
            // Uncomment this to output log initialisation errors (ONLY FOR DEVELOPMENT!)
            // echo $e;
        }
    }
}
