<?php
namespace Funseco\Sectors;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\ManiaLink\Facades\ManiaLink;
use Funseco\Core\SharedKernel\UI\Region;
use Funseco\Core\SharedKernel\UI\Theme\ThemeKit;
use Funseco\Core\SharedKernel\UI\WidgetRegistry;

/** The "Secrecs" and "My Secrecs" buttons; placed by manialinks.sector_buttons (pos_x / pos_y, top-left). */
class SectorButtons
{
    private const WIDTH = 19.3;
    private const HEIGHT = 2.5;

    public static function register(WidgetRegistry $registry): void
    {
        $registry->regionWidget('sector_buttons', Region::MidRight, 90, width: self::WIDTH, height: self::HEIGHT);
    }

    public function showToAll(): void
    {
        foreach (CurrentPlayers::all() as $player) {
            $this->showTo($player);
        }
    }

    public function showTo(Player $player): void
    {
        ManiaLink::displayLogin($player, $this->xml());
    }

    public function xml(): string
    {
        $id = ManiaLink::getAction('sector_buttons');
        $placement = config('sectors.buttons', true) && app(SectorRecords::class)->enabledHere()
            ? app(WidgetRegistry::class)->placement('sector_buttons') : null;
        if ($placement === null) {
            return '<manialink id="'.$id.'"></manialink>';
        }

        $kit = ThemeKit::current();

        return '<manialink id="'.$id.'"><format style="TextCardInfoSmall" textsize="1"/>'
            .'<frame posn="'.$placement->x.' '.$placement->y.' 1">'
            .$kit->surface('status_button', 9.55, self::HEIGHT, 'Bgs1InRace', 'NavButton', 'action="'.ManiaLink::getAction('sectors_all').'"')
            .'<frame posn="9.75 0 0">'.$kit->surface('status_button', 9.55, self::HEIGHT, 'Bgs1InRace', 'NavButton', 'action="'.ManiaLink::getAction('sectors_own').'"').'</frame>'
            .'<label posn="4.775 -1.25 1" sizen="9 2" halign="center" valign="center" text="$i$s$fffSecrecs"/>'
            .'<label posn="14.525 -1.25 1" sizen="9 2" halign="center" valign="center" text="$i$s$fffMy Secrecs"/>'
            .'</frame></manialink>';
    }
}
