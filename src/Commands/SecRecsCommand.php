<?php
namespace Funseco\Sectors\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\UI\Window;
use Funseco\Sectors\SectorRecords;

/** /secrecs: the record of every sector of this map. */
class SecRecsCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'secrecs';
    protected string $description = 'Shows Sector Records';

    public function handle(Player $player, array $args): void
    {
        $rows = [];
        $total = 0;
        foreach (app(SectorRecords::class)->best() as $sector => $record) {
            $total += $record['time'];
            $rows[] = ['{#highlite}Sec'.str_pad((string) $sector, 2, '0', STR_PAD_LEFT).':', '{#highlite}'.format_score($record['time']), '{#black}'.$record['nickname']];
        }
        if ($rows) {
            $rows[] = ['', '', ''];
            $rows[] = ['{#highlite}Total Time:', '{#highlite}'.format_score($total), ''];
        }

        Window::show($player, 'Sector Records on this Map:', $rows, [0.3, 0.3, 0.65], ['Icons64x64_1', 'TrackInfo']);
    }
}
