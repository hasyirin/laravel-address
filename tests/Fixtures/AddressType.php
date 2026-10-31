<?php

namespace Hasyirin\Address\Tests\Fixtures;

enum AddressType: string
{
    case Billing = 'billing';
    case Shipping = 'shipping';
}
