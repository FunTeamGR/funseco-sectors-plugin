<?php

/*
 * Widget defaults merged into config('manialinks'); config/manialinks.php of the app wins.
 * Secrecs / My Secrecs buttons.
 * The merge is shallow (top-level keys only): an app override of one of these keys
 * replaces its whole subtree, so it must carry every gamemode, not just the changed one.
 */
return [
    // Secrecs / My Secrecs buttons (sectors-plugin): top-left of the strip, bottom right of the screen.
    'sector_buttons' => [
        'gamemode' => array_fill_keys(['rounds', 'time_attack', 'team', 'laps', 'cup'], ['pos_x' => 29.7, 'pos_y' => -30.8]),
    ],
];
