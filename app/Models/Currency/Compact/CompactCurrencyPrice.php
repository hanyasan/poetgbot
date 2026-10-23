<?php

namespace App\Models\Currency\Compact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 *
 * @property string $currency_type_name
 * @property float $price_avg_rate
 * @property string $currency_trade_id
 *
 * @property int $core_id
 * @property-read CompactPriceCheckCore $core
 */
final class CompactCurrencyPrice extends Model
{
    protected $table = 'compact_currency_prices';

    protected $guarded = ['id'];

    public function core(): BelongsTo
    {
        return $this->belongsTo(CompactPriceCheckCore::class, 'core_id');
    }
}
