<?php
namespace Funseco\Sectors\Infrastructure;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Funseco\Core\Models\Player;

/**
 * @property int $map_id
 * @property int $player_id
 * @property int $sector
 * @property int $time
 */
class SectorRecord extends Model
{
    protected $table = 'sector_records';
    public $timestamps = false;
    protected $guarded = [];

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
