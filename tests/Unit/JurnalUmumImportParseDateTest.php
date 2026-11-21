<?php

namespace Tests\Unit;

use App\Imports\JurnalUmumImport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JurnalUmumImportParseDateTest extends TestCase
{
    public static function validDates(): array
    {
        return [
            'excel serial int' => [46347, '2026-11-21'],
            'excel serial float' => [46347.0, '2026-11-21'],
            'excel serial string' => ['46347', '2026-11-21'],
            'dd/mm/yyyy' => ['21/11/2026', '2026-11-21'],
            'd/m/yyyy' => ['1/2/2026', '2026-02-01'],
            'dd-mm-yyyy' => ['21-11-2026', '2026-11-21'],
            'dd.mm.yyyy' => ['21.11.2026', '2026-11-21'],
            'yyyy-mm-dd' => ['2026-11-21', '2026-11-21'],
            'yyyy/mm/dd' => ['2026/11/21', '2026-11-21'],
            'with time' => ['2026-11-21 00:00:00', '2026-11-21'],
            'padded' => ['  21/11/2026 ', '2026-11-21'],
            'datetime object' => [new \DateTimeImmutable('2026-11-21'), '2026-11-21'],
        ];
    }

    #[DataProvider('validDates')]
    public function test_parses_valid_dates($input, string $expected): void
    {
        $this->assertSame($expected, JurnalUmumImport::parseDate($input));
    }

    public static function invalidDates(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'spaces' => ['   '],
            'text' => ['besok'],
            'impossible day' => ['31/02/2026'],
            'month 13' => ['2026-13-01'],
            'zero serial' => [0],
            'negative serial' => [-5],
            'mm/dd/yyyy us style' => ['11/21/2026'],
        ];
    }

    #[DataProvider('invalidDates')]
    public function test_rejects_invalid_dates($input): void
    {
        $this->assertNull(JurnalUmumImport::parseDate($input));
    }
}
