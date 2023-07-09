<?php

namespace App\TelegramBotCommands;

use App\Models\Currency\CurrencyPrice;
use App\Models\Currency\CurrencyType;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class DivineCommand extends SystemCommand
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

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $price = CurrencyPrice::where(
            'currency_type_id',
            CurrencyType::where('currency_ninja_details_id', 'divine-orb')->firstOrFail()->id
        )->firstOrFail();

        return $this->replyToChat(
            'Estimated price: ' . $price->chaos_equivalent . PHP_EOL .
            'Buy price: ' . ($price->buy_price ?? 'Unknown') . PHP_EOL .
            'Sell price: ' . ($price->sell_price ?? 'Unknown') . PHP_EOL
        , ['parse_mode' => 'markdown']);
    }
}
