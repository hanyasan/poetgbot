<?php

namespace App\Models\Currency\Compact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 *
 * @property string $type_name
 * @property float $avg_type_rate
 * @property string $type_id
 *
 * @property int $core_id
 * @property-read CompactPriceCheckCore $core
 */
final class CompactPriceCheckCoreType extends Model
{
    protected $table = 'compact_price_check_core_types';

    protected $guarded = ['id'];

    public function core(): BelongsTo
    {
        return $this->belongsTo(CompactPriceCheckCore::class, 'core_id');
    }
}
