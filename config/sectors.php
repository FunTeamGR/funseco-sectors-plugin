<?php

// Sector records (XAseco plugin.bestsecs): the best time of every sector (checkpoint to checkpoint) of a map.
return [
    'enabled' => true,
    // Tell everybody when a sector record is taken (off) / tell only the player when they set or improve their own.
    'announce_records' => false,
    'announce_own' => true,
    // The Secrecs / My Secrecs buttons; game modes (ServerInfo::getGameMode ids) where sectors are tracked.
    'buttons' => true,
    'modes' => [0, 1, 2, 3, 5],   // rounds, time attack, team, laps, cup (not stunts)
];
