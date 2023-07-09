<?php

return [
    'api_key'      => env('TG_API_KEY', 'null'),
    'bot_username' => env('TG_BOT_USERNAME', 'null'),
    // [Manager Only] Secret key required to access the webhook
    'secret'       => 'super_secret',
    //TODO: webhook setter
//    'webhook'      => [
//        'url' => 'https://your-domain/path/to/hook-or-manager.php',
//         'max_connections' => 5,
//    ],
    'commands'     => [
        'paths'   => [
            app_path() . env('TG_COMMAND_PATH', '/TelegramBotCommands')
        ],
    ],
    // Define all IDs of admin users
    'admins'       => [
        env('TG_PRIMARY_ADMIN', 'null'),
    ],
];
