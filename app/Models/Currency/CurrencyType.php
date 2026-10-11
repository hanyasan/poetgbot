<?php

namespace App\Models\Currency;

use App\Models\PoeLeague;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read integer $id
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 *
 * @property string $currency_type_name
 * @property string $currency_trade_id
 * @property string $currency_ninja_details_id
 *
 * @property-read int $poe_league_id
 * @property-read PoeLeague $league
 *
 */
class CurrencyType extends Model
{
    protected $table = 'currency_types';

    protected $guarded = ['id'];

    public function league(): BelongsTo
    {
        return $this->belongsTo(PoeLeague::class, 'poe_league_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(CurrencyPrice::class);
    }
}
