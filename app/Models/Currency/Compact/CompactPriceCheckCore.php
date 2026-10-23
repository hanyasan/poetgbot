<?php

namespace App\Models\Currency\Compact;

use App\Models\PoeLeague;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 *
 * @property string $date
 *
 * @property int $league_id
 * @property-read PoeLeague $league
 */
final class CompactPriceCheckCore extends Model
{
    protected $table = 'compact_price_check_cores';

    protected $guarded = ['id'];

    public function league(): BelongsTo
    {
        return $this->belongsTo(PoeLeague::class, 'league_id');
    }
}
