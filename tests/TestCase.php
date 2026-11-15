<?php

namespace Funseco\Sectors\Tests;

use Funseco\Core\Testing\TestCase as CoreTestCase;
use Funseco\Sectors\SectorsServiceProvider;

abstract class TestCase extends CoreTestCase
{
    /** Dependencies first, then this plugin (isolated runs load only these). */
    protected function plugins(): array
    {
        return [
            SectorsServiceProvider::class,
        ];
    }
}
