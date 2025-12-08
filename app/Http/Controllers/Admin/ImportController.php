<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScanResi;
use App\Models\ScanResiInstan;
use App\Models\ScanResiShipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function index()
    {
        return view('admin.import.index');
    }

    public function storeInstan(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return response()->json(['message' => 'Tidak dapat membaca file'], 422);
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return response()->json(['message' => 'File kosong'], 422);
        }

        $delimiter = $this->detectDelimiter($firstLine);
        $headers = $this->normalizeHeaders(str_getcsv($firstLine, $delimiter));
        $orderIndex = $this->findOrderIndex($headers);

        if ($orderIndex === null) {
            fclose($handle);
            return response()->json([
                'message' => 'Header tidak valid. Gunakan salah satu: order_id, id_pesanan, nomor_pesanan, no_pesanan, order_number',
            ], 422);
        }

        $totalRows = 0;
        $duplicatesInFile = 0;
        $skippedEmpty = 0;
        $uniqueOrder = [];
        $seenInFile = [];
        $duplicateList = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $totalRows++;

            if (!array_key_exists($orderIndex, $row)) {
                $skippedEmpty++;
                continue;
            }

            $orderValue = (string) ($row[$orderIndex] ?? '');
            $normalizedOrder = $this->normalizeOrderId($orderValue);
            if ($normalizedOrder === '') {
                $skippedEmpty++;
                continue;
            }

            if (isset($seenInFile[$normalizedOrder])) {
                $duplicatesInFile++;
                $duplicateList[] = [
                    'order_id' => $normalizedOrder,
                    'reason' => 'Duplikat di file',
                ];
                continue;
            }

            $seenInFile[$normalizedOrder] = true;
            $uniqueOrder[] = $normalizedOrder;
        }

        fclose($handle);

        if (empty($uniqueOrder)) {
            return response()->json([
                'message' => 'Tidak ada data pesanan yang valid untuk diimpor',
                'summary' => [
                    'total_rows' => $totalRows,
                    'inserted' => 0,
                    'duplicate_in_db' => 0,
                    'duplicate_in_file' => $duplicatesInFile,
                    'skipped_empty' => $skippedEmpty,
                    'duplicates' => $duplicateList,
                ],
            ], 422);
        }

        $existingOrder = [];
        foreach (array_chunk($uniqueOrder, 500) as $chunk) {
            $existing = ScanResiInstan::whereIn('order_id', $chunk)->pluck('order_id')->all();
            $existingOrder = array_merge($existingOrder, $existing);
        }

        $existingOrder = array_unique($existingOrder);
        foreach ($existingOrder as $orderId) {
            $duplicateList[] = [
                'order_id' => $orderId,
                'reason' => 'Sudah ada di database',
            ];
        }

        $insertPayload = [];
        $now = now();
        $sourceName = $file->getClientOriginalName();
        foreach ($uniqueOrder as $orderId) {
            if (in_array($orderId, $existingOrder, true)) {
                continue;
            }
            $insertPayload[] = [
                'order_id' => $orderId,
                'source_name' => $sourceName,
                'scanned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $inserted = 0;
        DB::beginTransaction();
        try {
            foreach (array_chunk($insertPayload, 500) as $chunk) {
                $inserted += count($chunk);
                ScanResiInstan::insert($chunk);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mengimpor data',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
                'message' => 'Import selesai',
                'summary' => [
                    'total_rows' => $totalRows,
                    'inserted' => $inserted,
                    'duplicate_in_db' => count($existingOrder),
                    'duplicate_in_file' => $duplicatesInFile,
                    'skipped_empty' => $skippedEmpty,
                    'duplicates' => $duplicateList,
                ],
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return response()->json(['message' => 'Tidak dapat membaca file'], 422);
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return response()->json(['message' => 'File kosong'], 422);
        }

        $delimiter = $this->detectDelimiter($firstLine);
        $headers = $this->normalizeHeaders(str_getcsv($firstLine, $delimiter));
        $resiIndex = $this->findResiIndex($headers);

        if ($resiIndex === null) {
            fclose($handle);
            return response()->json([
                'message' => 'Header tidak valid. Gunakan salah satu: resi_number, resi, nomor_resi, no_resi, tracking_number',
            ], 422);
        }

        $totalRows = 0;
        $duplicatesInFile = 0;
        $skippedEmpty = 0;
        $uniqueResi = [];
        $seenInFile = [];
        $duplicateList = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $totalRows++;

            if (!array_key_exists($resiIndex, $row)) {
                $skippedEmpty++;
                continue;
            }

            $resiNumber = (string) ($row[$resiIndex] ?? '');
            $normalizedResi = $this->normalizeResiValue($resiNumber);
            if ($normalizedResi === '') {
                $skippedEmpty++;
                continue;
            }

            if (isset($seenInFile[$normalizedResi])) {
                $duplicatesInFile++;
                $duplicateList[] = [
                    'resi_number' => $normalizedResi,
                    'reason' => 'Duplikat di file',
                ];
                continue;
            }

            $seenInFile[$normalizedResi] = true;
            $uniqueResi[] = $normalizedResi;
        }

        fclose($handle);

        if (empty($uniqueResi)) {
            return response()->json([
                'message' => 'Tidak ada data resi yang valid untuk diimpor',
                'summary' => [
                    'total_rows' => $totalRows,
                    'inserted' => 0,
                    'duplicate_in_db' => 0,
                    'duplicate_in_file' => $duplicatesInFile,
                    'skipped_empty' => $skippedEmpty,
                    'duplicates' => $duplicateList,
                ],
            ], 422);
        }

        $existingResi = [];
        foreach (array_chunk($uniqueResi, 500) as $chunk) {
            $existing = ScanResi::whereIn('resi_number', $chunk)->pluck('resi_number')->all();
            $existingResi = array_merge($existingResi, $existing);
        }

        $existingResi = array_unique($existingResi);
        foreach ($existingResi as $resi) {
            $duplicateList[] = [
                'resi_number' => $resi,
                'reason' => 'Sudah ada di database',
            ];
        }

        $insertPayload = [];
        $now = now();
        $sourceName = $file->getClientOriginalName();
        foreach ($uniqueResi as $resi) {
            if (in_array($resi, $existingResi, true)) {
                continue;
            }
            $insertPayload[] = [
                'resi_number' => $resi,
                'source_name' => $sourceName,
                'scanned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $inserted = 0;
        DB::beginTransaction();
        try {
            foreach (array_chunk($insertPayload, 500) as $chunk) {
                $inserted += count($chunk);
                ScanResi::insert($chunk);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mengimpor data',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Import selesai',
            'summary' => [
                'total_rows' => $totalRows,
                'inserted' => $inserted,
                'duplicate_in_db' => count($existingResi),
                'duplicate_in_file' => $duplicatesInFile,
                'skipped_empty' => $skippedEmpty,
                'duplicates' => $duplicateList,
            ],
        ]);
    }

    protected function detectDelimiter(string $sample): string
    {
        $commaCount = substr_count($sample, ',');
        $semicolonCount = substr_count($sample, ';');
        if ($commaCount === $semicolonCount) {
            return ';';
        }
        return $semicolonCount > $commaCount ? ';' : ',';
    }

    protected function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $clean = ltrim((string) $header, "\xEF\xBB\xBF");
            $normalized = preg_replace('/[^a-z0-9]+/i', '_', strtolower(trim($clean)));
            return trim($normalized, '_');
        }, $headers);
    }

    protected function findResiIndex(array $headers): ?int
    {
        $aliases = ['resi_number', 'resi', 'nomor_resi', 'no_resi', 'tracking_number'];

        foreach ($headers as $index => $header) {
            if (in_array($header, $aliases, true)) {
                return $index;
            }
        }

        return null;
    }

    protected function normalizeResiValue(string $value): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '';
        }

        $clean = ltrim($clean, "\xEF\xBB\xBF");

        if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)[eE][+-]?[0-9]+$/', $clean)) {
            $clean = $this->expandScientific($clean);
        }

        // Remove spaces/tabs that might slip from copy-paste
        $clean = preg_replace('/\s+/', '', $clean);

        return Str::upper($clean);
    }

    protected function expandScientific(string $number): string
    {
        if (!preg_match('/^([0-9]+(?:[\.,][0-9]+)?)[eE]([+-]?[0-9]+)$/', $number, $m)) {
            return $number;
        }

        $mantissa = str_replace(',', '.', $m[1]);
        $exponent = (int) $m[2];

        $mantissa = ltrim($mantissa, '+');
        [$intPart, $decPart] = array_pad(explode('.', $mantissa, 2), 2, '');
        $digits = $intPart.$decPart;
        $decLength = strlen($decPart);

        if ($exponent >= 0) {
            if ($exponent >= $decLength) {
                $zeros = str_repeat('0', $exponent - $decLength);
                $result = $digits.$zeros;
            } else {
                $pos = strlen($intPart) + $exponent;
                $result = substr($digits, 0, $pos).substr($digits, $pos);
            }
        } else {
            $zeros = str_repeat('0', abs($exponent) - 1);
            $result = '0'.$zeros.$digits;
        }

        $result = ltrim($result, '0');
        return $result === '' ? '0' : $result;
    }

    public function destroyAll()
    {
        $deleted = ScanResi::count();
        try {
            DB::transaction(function () {
                ScanResi::query()->delete();
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Semua data resi telah dihapus',
            'deleted' => $deleted,
        ]);
    }

    public function destroyInstan()
    {
        $deleted = ScanResiInstan::count();
        try {
            DB::transaction(function () {
                ScanResiInstan::query()->delete();
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Semua data resi instan telah dihapus',
            'deleted' => $deleted,
        ]);
    }

    public function destroyShipments()
    {
        $deleted = ScanResiShipment::count();
        try {
            DB::transaction(function () {
                ScanResiShipment::query()->delete();
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal menghapus data kirim',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Semua data kirim resi telah dihapus',
            'deleted' => $deleted,
        ]);
    }

    public function storeShipments(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return response()->json(['message' => 'Tidak dapat membaca file'], 422);
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return response()->json(['message' => 'File kosong'], 422);
        }

        $delimiter = $this->detectDelimiter($firstLine);
        $headers = $this->normalizeHeaders(str_getcsv($firstLine, $delimiter));
        [$resiIdx, $orderIdx, $skuIdx, $qtyIdx] = $this->findShipmentIndexes($headers);

        if ($resiIdx === null || $orderIdx === null || $skuIdx === null || $qtyIdx === null) {
            fclose($handle);
            return response()->json([
                'message' => 'Header tidak valid. Kolom wajib: resi, id_pesanan, sku, jumlah',
            ], 422);
        }

        $totalRows = 0;
        $skippedEmpty = 0;
        $rows = [];
        $skippedEntries = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $totalRows++;

            $rawResi = (string) ($row[$resiIdx] ?? '');
            $rawOrder = (string) ($row[$orderIdx] ?? '');
            $rawSku = (string) ($row[$skuIdx] ?? '');
            $rawQty = (string) ($row[$qtyIdx] ?? '');

            $resi = $this->normalizeResiValue($rawResi);
            $orderId = $this->normalizeOrderId($rawOrder);
            $sku = $this->normalizeSku($rawSku);
            $qty = $this->normalizeQuantity($rawQty);

            if ($resi === '' || $orderId === '' || $sku === '' || $qty <= 0) {
                $skippedEmpty++;
                $skippedEntries[] = [
                    'resi_number' => $resi ?: ($rawResi ?: '-'),
                    'order_id' => $orderId ?: ($rawOrder ?: '-'),
                    'sku' => $sku ?: ($rawSku ?: '-'),
                    'quantity' => $qty > 0 ? $qty : ($rawQty ?: '0'),
                ];
                continue;
            }

            $rows[] = [
                'resi_number' => $resi,
                'order_id' => $orderId,
                'sku' => $sku,
                'quantity' => $qty,
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            return response()->json([
                'message' => 'Tidak ada data valid untuk diimpor',
                'summary' => [
                    'total_rows' => $totalRows,
                    'inserted' => 0,
                    'updated' => 0,
                    'duplicate_in_db' => 0,
                    'duplicate_in_file' => 0,
                    'skipped_empty' => $skippedEmpty,
                    'duplicates' => [],
                    'skipped_entries' => $skippedEntries,
                ],
            ], 422);
        }

        $now = now();
        $sourceName = $file->getClientOriginalName();
        $payload = array_map(function ($row) use ($now, $sourceName) {
            return [
                'resi_number' => $row['resi_number'],
                'order_id' => $row['order_id'],
                'sku' => $row['sku'],
                'quantity' => $row['quantity'],
                'source_name' => $sourceName,
                'scanned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $rows);

        $inserted = 0;
        $updated = 0;
        try {
            DB::transaction(function () use ($payload, &$inserted) {
                foreach (array_chunk($payload, 400) as $chunk) {
                    ScanResiShipment::insert($chunk);
                    $inserted += count($chunk);
                }
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal mengimpor data kirim',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Import data kirim selesai',
            'summary' => [
                'total_rows' => $totalRows,
                'inserted' => $inserted,
                'updated' => $updated,
                'duplicate_in_db' => 0,
                'duplicate_in_file' => 0,
                'skipped_empty' => $skippedEmpty,
                'duplicates' => [],
                'skipped_entries' => $skippedEntries,
            ],
        ]);
    }

    protected function findShipmentIndexes(array $headers): array
    {
        $resiAliases = ['resi_number', 'resi', 'nomor_resi', 'no_resi', 'tracking_number'];
        $orderAliases = ['order_id', 'id_pesanan', 'pesanan_id', 'nomor_pesanan', 'no_pesanan', 'orderid', 'order_number'];
        $skuAliases = ['sku', 'kode_barang', 'product_sku', 'kode_produk'];
        $qtyAliases = ['qty', 'quantity', 'jumlah', 'qty_ordered'];

        $findIndex = function (array $aliases) use ($headers): ?int {
            foreach ($headers as $index => $header) {
                if (in_array($header, $aliases, true)) {
                    return $index;
                }
            }
            return null;
        };

        return [
            $findIndex($resiAliases),
            $findIndex($orderAliases),
            $findIndex($skuAliases),
            $findIndex($qtyAliases),
        ];
    }

    protected function normalizeOrderId(string $value): string
    {
        $clean = trim($value);
        $clean = preg_replace('/\\s+/', '', $clean);
        return Str::upper($clean);
    }

    protected function findOrderIndex(array $headers): ?int
    {
        $aliases = ['order_id', 'id_pesanan', 'pesanan_id', 'nomor_pesanan', 'no_pesanan', 'order_number', 'orderid'];
        foreach ($headers as $index => $header) {
            if (in_array($header, $aliases, true)) {
                return $index;
            }
        }
        return null;
    }

    protected function normalizeSku(string $value): string
    {
        $clean = trim($value);
        $clean = preg_replace('/\\s+/', '', $clean);
        return Str::upper($clean);
    }

    protected function normalizeQuantity(string $value): int
    {
        $clean = trim($value);
        $clean = str_replace([' ', "\t", "\r", "\n"], '', $clean);
        $clean = str_replace(',', '.', $clean);
        $num = (float) $clean;
        $int = (int) round($num);
        return $int > 0 ? $int : 0;
    }
}
