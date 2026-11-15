<?php
namespace Funseco\Sectors\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\Commands\Guarded;
use Funseco\Core\SharedKernel\Enums\Role;
use Funseco\Sectors\SectorRecords;

/** /delsecs: delete every sector record of this map (operators and up). */
class DelSecsCommand implements Command, Guarded, Described
{
    use DeclaresCommand;

    protected string $name = 'delsecs';
    protected string $description = 'Deletes all records on this challenge (both own and all secrecs)';
    protected ?Role $role = Role::Operator;

    public function handle(Player $player, array $args): void
    {
        app(SectorRecords::class)->delete();
        \Server::query()->ChatSendServerMessageToLogin('> All SecRecs deleted !', $player->login);
    }
}
