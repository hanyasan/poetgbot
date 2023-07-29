<?php

namespace App\Models\Currency;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CurrencyPrice extends Model
{
    protected $table = 'currency_prices';

    protected $fillable = [
        'chaos_equivalent',
        'sell_price',
        'buy_price',
        'currency_type_id',
    ];

    protected $casts = [
        'chaos_equivalent' => 'float',
        'buy_price' => 'float',
        'sell_price' => 'float',
    ];

    public function type(): HasOne
    {
        return $this->hasOne(CurrencyType::class, 'id', 'currency_type_id');
    }
}
