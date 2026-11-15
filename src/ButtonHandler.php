<?php
namespace Funseco\Sectors;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;

/** A button is the same as typing the command. */
class ButtonHandler
{
    public function handle(Player $player, string $action): void
    {
        Chat::run($player, $action === 'sectors_own' ? 'mysecrecs' : 'secrecs');
    }
}
