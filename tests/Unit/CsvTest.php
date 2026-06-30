<?php

namespace Tests\Unit;

use App\Support\Csv;
use PHPUnit\Framework\TestCase;

class CsvTest extends TestCase
{
    public function test_formula_leading_characters_are_neutralized(): void
    {
        $this->assertSame("'=cmd|'/C calc'!A1", Csv::neutralize("=cmd|'/C calc'!A1"));
        $this->assertSame("'+1234567", Csv::neutralize('+1234567'));
        $this->assertSame("'-2+3", Csv::neutralize('-2+3'));
        $this->assertSame("'@SUM(A1)", Csv::neutralize('@SUM(A1)'));
        $this->assertSame("'\tabbed", Csv::neutralize("\tabbed"));
    }

    public function test_safe_values_pass_through_unchanged(): void
    {
        $this->assertSame('Ahmed', Csv::neutralize('Ahmed'));
        $this->assertSame('مطعم', Csv::neutralize('مطعم'));
        $this->assertSame('ORD-20260630-0001', Csv::neutralize('ORD-20260630-0001'));
        $this->assertSame('', Csv::neutralize(''));
        $this->assertSame('123', Csv::neutralize(123));
    }
}
