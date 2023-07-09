<?php

namespace App\Models\Currency;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurrencyType extends Model
{
    protected $table = 'currency_types';

    protected $fillable = [
        'currency_type_name',
        'currency_trade_id',
        'currency_ninja_details_id',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(CurrencyPrice::class);
    }
}
