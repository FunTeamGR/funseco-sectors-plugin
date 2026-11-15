<?php

namespace Funseco\Sectors;

use Funseco\Core\Models\Map;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\Services\Cache\ServerInfo;
use Funseco\Sectors\Infrastructure\SectorRecord;

/**
 * Sector records of the current map: the time between two checkpoints. In memory, loaded per map;
 * every improvement is written straight to sector_records.
 */
class SectorRecords
{
    private const BEST = 'sectors:best';

    private const OWN = 'sectors:own:';

    private const LAST = 'sectors:last:';

    /** @return array<int, array{time: int, login: string, nickname: string}> by sector, ascending */
    public function best(): array
    {
        return state()->get(self::BEST, []);
    }

    /** @return array<int, int> time by sector */
    public function own(string $login): array
    {
        return state()->get(self::OWN.$login, []);
    }

    public function load(Map $map): void
    {
        $this->clear();
        $best = [];
        $own = [];
        foreach (SectorRecord::where('map_id', $map->id)->with('player:id,login,nickname')->get() as $row) {
            if (! $row->player) {
                continue;
            }
            $own[$row->player->login][$row->sector] = $row->time;
            if (! isset($best[$row->sector]) || $row->time < $best[$row->sector]['time']) {
                $best[$row->sector] = ['time' => $row->time, 'login' => $row->player->login, 'nickname' => $row->player->nickname];
            }
        }
        ksort($best);
        state()->forever(self::BEST, $best);
        state()->forever('sectors:players', array_fill_keys(array_keys($own), true));
        foreach ($own as $login => $times) {
            ksort($times);
            state()->forever(self::OWN.$login, $times);
        }
    }

    /** Forget the map's sectors and every player's running sector. */
    public function clear(): void
    {
        foreach (array_keys(state()->get('sectors:players', [])) as $login) {
            state()->forget(self::OWN.$login);
            state()->forget(self::LAST.$login);
        }
        state()->forget(self::BEST);
        state()->forget('sectors:players');
    }

    /** Delete the records of sectors $from..$to (all sectors when null) of the current map; returns the rows removed. */
    public function delete(?int $from = null, ?int $to = null): int
    {
        if (! ($map = CurrentMap::get())) {
            return 0;
        }
        $query = SectorRecord::where('map_id', $map->id);
        if ($from !== null) {
            $query->whereBetween('sector', [$from, $to ?? $from]);
        }
        $removed = $query->delete();
        $this->load($map);

        return $removed;
    }

    public function forgetRun(string $login): void
    {
        state()->forget(self::LAST.$login);
    }

    public function enabledHere(): bool
    {
        return config('sectors.enabled', true) && in_array(ServerInfo::getGameMode() ?? 1, (array) config('sectors.modes', []), true);
    }

    public function passed(Player $player, int $index, int $time): void
    {
        $last = state()->get(self::LAST.$player->login);
        state()->forever(self::LAST.$player->login, ['index' => $index, 'time' => $time]);
        if (! $this->enabledHere() || ! ($map = CurrentMap::get())) {
            return;
        }

        if ($index === 0) {
            $sector = $time;
        } elseif ($last !== null && $last['index'] === $index - 1) {
            $sector = $time - $last['time'];
        } else {
            return;   // joined mid-run or skipped a checkpoint: the sector is unknown
        }
        if ($sector <= 0) {
            return;
        }

        $best = $this->best();
        $own = $this->own($player->login);
        $record = ! isset($best[$index]) || $sector < $best[$index]['time'];
        $improved = ! isset($own[$index]) || $sector < $own[$index];
        if (! $improved) {
            return;
        }

        SectorRecord::updateOrCreate(
            ['map_id' => $map->id, 'player_id' => $player->id, 'sector' => $index],
            ['time' => $sector],
        );
        $players = state()->get('sectors:players', []);
        $players[$player->login] = true;
        state()->forever('sectors:players', $players);
        $before = $own[$index] ?? null;
        $own[$index] = $sector;
        ksort($own);
        state()->forever(self::OWN.$player->login, $own);

        if ($record) {
            $old = $best[$index]['time'] ?? null;
            $best[$index] = ['time' => $sector, 'login' => $player->login, 'nickname' => $player->nickname];
            ksort($best);
            state()->forever(self::BEST, $best);
            if (config('sectors.announce_records', false)) {
                \Server::query()->ChatSendServerMessage($player->nickname.' $z$29f'.($old === null ? 'claimed' : 'gained')
                    .' the record in sector '.$index.'. Time: '.format_score($sector).($old === null ? '' : ' (-'.format_score($old - $sector).')'));
            }
        }
        if (config('sectors.announce_own', true)) {
            \Server::query()->ChatSendServerMessageToLogin($before === null
                ? '> You set a record in sector '.$index.'. Time: '.format_score($sector)
                : '> You improved your record in sector '.$index.'. Time: '.format_score($sector).' (-'.format_score($before - $sector).')', $player->login);
        }
    }
}
