<?php

namespace App\Models\Currency;

use App\Models\PoeLeague;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read integer $id
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 *
 * @property-read Collection<PriceCheckCoreType> $priceCheckCoreTypes
 *
 * @property-read int $poe_league_id
 * @property-read PoeLeague $league
 */
class PriceCheckCore extends Model
{
    protected $table = 'price_check_cores';

    protected $guarded = ['id'];

    public function league(): BelongsTo
    {
        return $this->belongsTo(PoeLeague::class, 'poe_league_id');
    }

    public function priceCheckCoreTypes(): HasMany
    {
        return $this->hasMany(PriceCheckCoreType::class, 'price_check_core_type_id');
    }
}
