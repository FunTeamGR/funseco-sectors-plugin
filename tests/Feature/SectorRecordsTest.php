<?php
namespace Funseco\Sectors\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Funseco\Core\Testing\FakesServer;
use Funseco\Sectors\Tests\TestCase;
use Funseco\Core\Models\Map;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\SharedKernel\Events\PlayerCheckpointPassed;
use Funseco\Sectors\Infrastructure\SectorRecord;
use Funseco\Sectors\SectorRecords;

class SectorRecordsTest extends TestCase
{
    use FakesServer;
    use RefreshDatabase;

    private Player $alice;
    private Player $bob;
    private Map $map;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeServer();
        state()->flush();
        CurrentPlayers::clear();
        $this->alice = Player::factory()->create(['login' => 'alice', 'nickname' => 'Alice']);
        $this->bob = Player::factory()->create(['login' => 'bob', 'nickname' => 'Bob']);
        $this->map = Map::factory()->create(['cp_count' => 3]);
        CurrentMap::set($this->map);
        app(SectorRecords::class)->load($this->map);
    }

    private function cp(Player $p, int $index, int $time): void
    {
        event(new PlayerCheckpointPassed($p, $index, $time, 0));
    }

    public function test_sector_is_the_time_since_the_previous_checkpoint(): void
    {
        $this->cp($this->alice, 0, 10000);
        $this->cp($this->alice, 1, 25000);

        $this->assertSame([0 => 10000, 1 => 15000], app(SectorRecords::class)->own('alice'));
        $this->assertSame(15000, app(SectorRecords::class)->best()[1]['time']);
        $this->assertSame(2, SectorRecord::count());
    }

    public function test_a_faster_player_takes_the_record_and_only_they_are_told(): void
    {
        $this->cp($this->alice, 0, 10000);
        $this->cp($this->bob, 0, 9000);

        $best = app(SectorRecords::class)->best();
        $this->assertSame('bob', $best[0]['login']);
        $this->assertSame(10000, app(SectorRecords::class)->own('alice')[0]);
        $this->assertCount(0, $this->messages('ChatSendServerMessage'));
        $this->assertCount(2, $this->messages('ChatSendServerMessageToLogin'));
        $this->assertStringContainsString('You set a record in sector 0', $this->messages('ChatSendServerMessageToLogin')[1]);
    }

    public function test_a_slower_time_changes_nothing(): void
    {
        $this->cp($this->alice, 0, 10000);
        $this->cp($this->alice, 0, 12000);

        $this->assertSame(10000, SectorRecord::first()->time);
        $this->assertCount(1, $this->messages('ChatSendServerMessageToLogin'));
    }

    public function test_joining_mid_run_or_after_a_finish_skips_the_unknown_sector(): void
    {
        $this->cp($this->alice, 2, 40000);
        $this->assertSame(0, SectorRecord::count());

        $this->cp($this->alice, 0, 10000);
        app(SectorRecords::class)->forgetRun('alice');
        $this->cp($this->alice, 1, 50000);
        $this->assertSame(1, SectorRecord::count());
    }

    public function test_records_survive_a_map_reload(): void
    {
        $this->cp($this->alice, 0, 10000);
        state()->flush();
        app(SectorRecords::class)->load($this->map);

        $this->assertSame('alice', app(SectorRecords::class)->best()[0]['login']);
        $this->assertSame([0 => 10000], app(SectorRecords::class)->own('alice'));
    }

    public function test_secrecs_window_lists_each_sector(): void
    {
        $this->cp($this->alice, 0, 10000);
        \Funseco\Core\Services\Chat\Facades\Chat::run($this->alice, 'secrecs');

        $window = state()->get('window:last:alice');
        $this->assertSame('Sector Records on this Map:', $window['header']);
        $this->assertSame('Sec00:', strip_tags(str_replace('{#highlite}', '', $window['pages'][0][0][0])));
    }

    public function test_delsec_removes_a_range_and_needs_an_operator(): void
    {
        foreach ([0 => 1000, 1 => 2000, 2 => 3000] as $i => $t) {
            $this->cp($this->alice, $i, $t * ($i + 1));
        }
        $this->assertSame(3, SectorRecord::count());

        Chat::run($this->alice, 'delsec', ['0-1']);
        $this->assertSame(3, SectorRecord::count(), 'a plain player may not');

        $this->alice->update(['role' => 'operator']);
        Chat::run($this->alice->fresh(), 'delsec', ['1-0']);
        $this->assertSame([2], SectorRecord::pluck('sector')->all());
        $this->assertSame([2], array_keys(app(SectorRecords::class)->best()));

        Chat::run($this->alice->fresh(), 'delsec', ['9']);
        $this->assertSame(1, SectorRecord::count(), 'beyond the checkpoints');

        Chat::run($this->alice->fresh(), 'delsecs');
        $this->assertSame(0, SectorRecord::count());
    }
}
