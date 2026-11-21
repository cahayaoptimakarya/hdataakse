<?php

namespace Tests\Unit;

use App\Imports\JurnalUmumImport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JurnalUmumImportParseAmountTest extends TestCase
{
    public static function amounts(): array
    {
        return [
            'null' => [null, 0.0],
            'empty' => ['', 0.0],
            'int cell' => [1500000, 1500000.0],
            'float cell' => [1500.5, 1500.5],
            'plain string' => ['1500000', 1500000.0],
            'titik ribuan' => ['1.500.000', 1500000.0],
            'titik ribuan satu grup' => ['1.500', 1500.0],
            'titik ribuan dengan spasi' => [' 2.500.000 ', 2500000.0],
            'titik ribuan + koma desimal' => ['1.500.000,50', 1500000.5],
            'koma ribuan + titik desimal' => ['1,500,000.50', 1500000.5],
            'koma ribuan' => ['1,500,000', 1500000.0],
            'koma desimal' => ['1500,5', 1500.5],
            'titik desimal' => ['1500.5', 1500.5],
            'titik desimal dua digit' => ['12.50', 12.5],
            'invalid text' => ['abc', null],
            'titik grup salah' => ['1.50.000', null],
        ];
    }

    #[DataProvider('amounts')]
    public function test_parse_amount($input, ?float $expected): void
    {
        $this->assertSame($expected, JurnalUmumImport::parseAmount($input));
    }
}
