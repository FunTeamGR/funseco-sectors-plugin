<?php
namespace Funseco\Sectors;

use Funseco\Core\SharedKernel\PublishesPluginConfig;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Funseco\Core\SharedKernel\Settings\Setting;
use Funseco\Core\SharedKernel\Settings\SettingsRegistry;
use Funseco\Core\SharedKernel\Settings\SettingType;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\Services\ManiaLink\ManiaLinkService;
use Funseco\Core\SharedKernel\Events\MapDataPurgeRequested;
use Funseco\Core\SharedKernel\Events\MapStarted;
use Funseco\Core\SharedKernel\Events\PlayerCheckpointPassed;
use Funseco\Core\SharedKernel\Events\PlayerConnected;
use Funseco\Core\SharedKernel\Events\PlayerDisconnected;
use Funseco\Core\SharedKernel\Events\PlayerFinished;
use Funseco\Core\SharedKernel\Events\PlayerRetired;
use Funseco\Core\SharedKernel\Events\StartupEvent;
use Funseco\Core\SharedKernel\UI\WidgetRegistry;
use Funseco\Sectors\Commands\DelSecCommand;
use Funseco\Sectors\Commands\DelSecsCommand;
use Funseco\Sectors\Commands\MySecRecsCommand;
use Funseco\Sectors\Commands\SecRecsCommand;

class SectorsServiceProvider extends ServiceProvider
{
    use PublishesPluginConfig;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sectors.php', 'sectors');
        $this->mergeConfigDeep(__DIR__.'/../config/manialinks.php', 'manialinks');
        $this->publishPluginConfig(__DIR__.'/..');
        // All read lazily through config() where used: live.
        $this->app->make(SettingsRegistry::class)->declare('sectors', 'sectors', [
            Setting::bool('enabled', 'Sector records enabled'),
            Setting::bool('announce_records', 'Announce sector records to everybody'),
            Setting::bool('announce_own', 'Tell players their own sector records'),
            Setting::bool('buttons', 'Secrecs buttons'),
            Setting::list('modes', 'Tracked game modes (ids)', itemType: SettingType::Int),
        ]);
        $this->app->singleton(SectorRecords::class);
        $this->app->singleton(SectorButtons::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        app(ManiaLinkService::class)->addAction('sector_buttons');
        app(ManiaLinkService::class)->addActions(['sectors_all', 'sectors_own'], ButtonHandler::class);
        \Chat::register(SecRecsCommand::class);
        \Chat::register(MySecRecsCommand::class);
        \Chat::register(DelSecCommand::class);
        \Chat::register(DelSecsCommand::class);
        SectorButtons::register(app(WidgetRegistry::class));

        $sectors = fn () => app(SectorRecords::class);
        $buttons = fn () => app(SectorButtons::class);
        $load = function () use ($sectors, $buttons) {
            if ($map = CurrentMap::get()) {
                $sectors()->load($map);
            }
            $buttons()->showToAll();
        };
        Event::listen(StartupEvent::class, $load);
        Event::listen(MapStarted::class, $load);
        Event::listen(PlayerConnected::class, fn (PlayerConnected $e) => $buttons()->showTo($e->player));
        Event::listen(PlayerDisconnected::class, fn (PlayerDisconnected $e) => $sectors()->forgetRun($e->player->login));
        Event::listen(PlayerFinished::class, fn (PlayerFinished $e) => $sectors()->forgetRun($e->player->login));
        Event::listen(PlayerRetired::class, fn (PlayerRetired $e) => $sectors()->forgetRun($e->player->login));
        Event::listen(PlayerCheckpointPassed::class, fn (PlayerCheckpointPassed $e) => $sectors()->passed($e->player, $e->checkpointIndex, $e->time));
        // The rows go with the map (cascade); the in-memory copy is rebuilt.
        Event::listen(MapDataPurgeRequested::class, function (MapDataPurgeRequested $e) use ($load) {
            if (CurrentMap::get()?->id === $e->map->id) {
                \Funseco\Sectors\Infrastructure\SectorRecord::where('map_id', $e->map->id)->delete();
                $load();
            }
        });
    }
}
