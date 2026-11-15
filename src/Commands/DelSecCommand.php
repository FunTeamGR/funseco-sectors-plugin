<?php
namespace Funseco\Sectors\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\Commands\Guarded;
use Funseco\Core\SharedKernel\Enums\Role;
use Funseco\Sectors\SectorRecords;

/** /delsec <n> or <from>-<to>: delete the records of one sector or a range (operators and up). */
class DelSecCommand implements Command, Guarded, Described
{
    use DeclaresCommand;

    protected string $name = 'delsec';
    protected string $description = 'Deletes 1 record or a range of records on this challenge (both own and all secrecs)';
    protected string $usage = '<sector> | <from>-<to>';
    protected ?Role $role = Role::Operator;

    public function handle(Player $player, array $args): void
    {
        $param = trim(implode('', $args));
        if (!preg_match('/^(\d+)(?:-(\d+))?$/', $param, $m)) {
            $this->tell($player, '> Usage: /delsec 2; /delsec 3-5');

            return;
        }
        $from = (int) $m[1];
        $to = isset($m[2]) ? (int) $m[2] : $from;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $count = (int) CurrentMap::get()?->cp_count;   // 0: not known, any sector goes
        if ($count > 0 && $to >= $count) {
            $this->tell($player, isset($m[2]) ? '> Please choose a valid sector range.' : '> Please choose a valid sector.');

            return;
        }

        app(SectorRecords::class)->delete($from, $to);
        \Server::query()->ChatSendServerMessage($from === $to ? '> Sector '.$from.' deleted.' : '> Sectors '.$from.'-'.$to.' deleted.');
    }

    private function tell(Player $player, string $message): void
    {
        \Server::query()->ChatSendServerMessageToLogin($message, $player->login);
    }
}
