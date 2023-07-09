<?php

namespace App\Http\Controllers\Telegram\Hook;

use App\Services\TelegramBotService\TelegramBotContract;
use Illuminate\Routing\Controller as BaseController;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Exception\TelegramLogException;

class HookController extends BaseController
{
    public function __construct(
        protected TelegramBotContract $telegramBot
    ) {}

    public function handle()
    {
        try {
            $this
                ->telegramBot
                ->getTelegramWithCommands()
                ->handle();
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
