<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScanResiShipment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataController extends Controller
{
    public function index()
    {
        $aggregates = ScanResiShipment::select([
                'sku',
                DB::raw('SUM(quantity) as total_quantity'),
            ])
            ->whereIn('resi_number', function ($q) {
                $q->select('resi_number')->from('scan_resi');
            })
            ->groupBy('sku')
            ->orderBy('sku')
            ->get();

        // Acuan utama: resi yang ada di table scan_resi
        $resiMaster = DB::table('scan_resi')->pluck('resi_number');
        $shipmentResi = ScanResiShipment::distinct()->pluck('resi_number');

        // Terintegrasi: resi yang ada di master dan juga punya data shipment
        $integrated = $resiMaster->intersect($shipmentResi)->values();
        // Tidak terintegrasi: resi di master yang belum punya data shipment
        $unintegrated = $resiMaster->diff($shipmentResi)->values();

        $integratedCount = $integrated->count();
        $unintegratedCount = $unintegrated->count();

        return view('admin.data.index', [
            'aggregates' => $aggregates,
            'integratedCount' => $integratedCount,
            'unintegrated' => $unintegrated,
            'unintegratedCount' => $unintegratedCount,
        ]);
    }

    public function export(): StreamedResponse
    {
        $filename = 'rekap_sku_'.now()->format('Ymd_His').'.csv';

        $aggregates = ScanResiShipment::select([
                'sku',
                DB::raw('SUM(quantity) as total_quantity'),
            ])
            ->whereIn('resi_number', function ($q) {
                $q->select('resi_number')->from('scan_resi');
            })
            ->groupBy('sku')
            ->orderBy('sku')
            ->get();

        $callback = function () use ($aggregates) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU', 'Jumlah Total']);
            foreach ($aggregates as $row) {
                fputcsv($handle, [$row->sku, $row->total_quantity]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportUnintegrated(): StreamedResponse
    {
        $filename = 'resi_tidak_terintegrasi_'.now()->format('Ymd_His').'.csv';

        $resiMaster = DB::table('scan_resi')->pluck('resi_number');
        $shipmentResi = ScanResiShipment::distinct()->pluck('resi_number');
        $unintegrated = $resiMaster->diff($shipmentResi)->values();

        $callback = function () use ($unintegrated) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Resi Tidak Terintegrasi']);
            foreach ($unintegrated as $resi) {
                fputcsv($handle, [$resi]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
