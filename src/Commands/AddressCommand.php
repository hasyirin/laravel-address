<?php

namespace Hasyirin\Address\Commands;

use Illuminate\Console\Command;

class AddressCommand extends Command
{
    public $signature = 'laravel-address';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
