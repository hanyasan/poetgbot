<?php

namespace App\TelegramBotCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class DivineCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'divine';

    /**
     * @var string
     */
    protected $description = 'Divine price command';

    /**
     * @var string
     */
    protected $usage = '/divine';

    /**
     * @var string
     */
    protected $version = '1.0';

    protected $show_in_help = true;

    protected $enabled = true;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        //TODO: implement new logic, not now
//        $price = app(CurrencyPriceServiceContract::class)->findByTypeDetailId('divine-orb');
//
//        return $this->replyToChat(
//            'Estimated price: ' . ($price->chaos_equivalent ? round($price->chaos_equivalent, 2) : 'Unknown') . PHP_EOL .
//            'Buy price: ' . ($price->buy_price ? round($price->buy_price, 2) : 'Unknown') . PHP_EOL .
//            'Sell price: ' . ($price->sell_price ? round($price->sell_price, 2) : 'Unknown') . PHP_EOL
//        , ['parse_mode' => 'markdown']);
    }
}
