<?php

namespace App\Models\Currency;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read integer $id
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 *
 * @property bool $is_primary
 * @property float $rate
 * @property string $type_id
 *
  * @property-read int $price_check_core_id
 * @property-read PriceCheckCore $priceCheckCore
 */
class PriceCheckCoreType extends Model
{
    protected $table = 'price_check_core_types';

    protected $guarded = ['id'];

    public function priceCheckCore(): BelongsTo
    {
        return $this->belongsTo(PriceCheckCore::class, 'price_check_core_id');
    }
}
