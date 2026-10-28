<?php

namespace Hasyirin\Address\Tests\Fixtures;

use Hasyirin\Address\Models\Address;

class EncryptedAddress extends Address
{
    protected function casts(): array
    {
        return [...parent::casts(), 'line_1' => 'encrypted'];
    }
}
