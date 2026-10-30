<?php

namespace Tests\Unit;

use Tests\TestCase;

class AddressStreetNormalizationTest extends TestCase
{
    public function test_street_is_uppercase_without_changing_other_characters(): void
    {
        $this->assertSame(
            'STREET 1',
            normalizeAddressStreet('Street 1')
        );
        $this->assertSame(
            'DECA HOMES, BLK2 LOT51',
            normalizeAddressStreet('Deca Homes, Blk2 Lot51')
        );
        $this->assertSame(
            'MAÑANA-STREET #2',
            normalizeAddressStreet('Mañana-Street #2')
        );
    }

    public function test_null_and_empty_streets_are_preserved(): void
    {
        $this->assertNull(normalizeAddressStreet(null));
        $this->assertSame('', normalizeAddressStreet(''));
    }
}
