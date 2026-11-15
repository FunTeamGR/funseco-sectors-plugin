<?php
namespace Funseco\Sectors\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\UI\Window;
use Funseco\Sectors\SectorRecords;

/** /mysecrecs: the player's own best time of every sector, with the gap to the sector record. */
class MySecRecsCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'mysecrecs';
    protected string $description = 'Shows own Sector Records';

    public function handle(Player $player, array $args): void
    {
        $sectors = app(SectorRecords::class);
        $best = $sectors->best();
        $rows = [];
        $total = 0;
        foreach ($sectors->own($player->login) as $sector => $time) {
            $total += $time;
            $diff = $time - ($best[$sector]['time'] ?? $time);
            $rows[] = [
                '{#highlite}Sec'.str_pad((string) $sector, 2, '0', STR_PAD_LEFT).':',
                '{#highlite}'.format_score($time),
                $diff > 0 ? '{#black}+'.format_score($diff).' to '.$best[$sector]['nickname'] : '{#black}Record',
            ];
        }
        if ($rows) {
            $rows[] = ['', '', ''];
            $rows[] = ['{#highlite}Total Time:', '{#highlite}'.format_score($total), ''];
        }

        Window::show($player, 'Your own Sector Records on this Map:', $rows, [0.3, 0.3, 0.65], ['Icons64x64_1', 'TrackInfo']);
    }
}
