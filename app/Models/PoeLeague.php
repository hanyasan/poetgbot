<?php

namespace App\Models;

use App\Enums\PoeVersionEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read integer $id
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 *
 * @property string $league_name
 * @property PoeVersionEnum $poe_version
 * @property bool $is_active
 */
final class PoeLeague extends Model
{
    protected $table = 'poe_leagues';

    protected $guarded = ['id'];
}
