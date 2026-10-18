<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read integer $id
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 *
 * @property-read null|LeagueCheckRule $check_rule
 *
 * @property string $league_name
 * @property string $poe_version
 * @property bool $is_active
 */
final class PoeLeague extends Model
{
    protected $table = 'poe_leagues';

    protected $guarded = ['id'];

    protected $with = ['check_rule'];

    public function check_rule(): HasOne
    {
        return $this->hasOne(LeagueCheckRule::class, 'poe_league_id');
    }
}
