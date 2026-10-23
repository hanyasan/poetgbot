<?php

namespace App\Models\Currency\Compact;

use App\Models\PoeLeague;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property-read int $id
 *
 * @property string $date
 *
 * @property int $league_id
 * @property-read PoeLeague $league
 *
 * @property-read Collection<int, CompactPriceCheckCoreType> $types
 * @property-read Collection<int, CompactCurrencyPrice> $prices
 */
final class CompactPriceCheckCore extends Model
{
    protected $table = 'compact_price_check_cores';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'date' => 'datetime',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(PoeLeague::class, 'league_id');
    }

    public function types(): HasMany
    {
        return $this->hasMany(CompactPriceCheckCoreType::class, 'core_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(CompactCurrencyPrice::class, 'core_id');
    }
}
