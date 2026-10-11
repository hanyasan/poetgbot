<?php

namespace App\Models\Currency;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read integer $id
 *
 * @property float $rate
 *
 * @property-read int $currency_type_id
 * @property-read CurrencyType $type
 *
 * @property-read int $price_check_core_id
 * @property-read PriceCheckCore $priceCheck
 */
class CurrencyPrice extends Model
{
    protected $table = 'currency_prices';

    protected $guarded = ['id'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(CurrencyType::class,'currency_type_id');
    }

    public function priceCheck(): BelongsTo
    {
        return $this->belongsTo(PriceCheckCore::class,'price_check_core_id');
    }
}
