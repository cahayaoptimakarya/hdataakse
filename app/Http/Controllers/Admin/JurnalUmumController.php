<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\JurnalUmumImport;
use App\Models\JurnalUmum;
use App\Support\Audit;
use App\Support\JurnalRowHash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Tambah, ubah, dan hapus satu transaksi jurnal secara manual. */
class JurnalUmumController extends Controller
{
    public function show(JurnalUmum $jurnal)
    {
        return response()->json([
            'id' => $jurnal->id,
            'tanggal' => $jurnal->tanggal ? substr((string) $jurnal->tanggal, 0, 10) : null,
            'keterangan' => $jurnal->keterangan,
            'sub_divisi_id' => $jurnal->sub_divisi_id,
            'sub_akun_biaya_id' => $jurnal->sub_akun_biaya_id,
            'debet' => (float) $jurnal->debet,
            'kredit' => (float) $jurnal->kredit,
            'import_batch_id' => $jurnal->import_batch_id,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $jurnal = JurnalUmum::create($data);
        Audit::log('tambah', 'Jurnal', $jurnal->id, 'Transaksi jurnal ditambahkan: '.$this->label($jurnal), $this->values($jurnal));

        return response()->json(['message' => 'Transaksi ditambahkan.', 'id' => $jurnal->id]);
    }

    public function update(Request $request, JurnalUmum $jurnal)
    {
        $data = $this->validated($request);
        $before = $this->values($jurnal);
        $jurnal->update($data);
        $after = $this->values($jurnal->fresh());
        $changes = [];
        foreach ($after as $k => $v) {
            if (($before[$k] ?? null) != $v) {
                $changes[$k] = [$before[$k] ?? null, $v];
            }
        }
        if ($changes) {
            Audit::log('ubah', 'Jurnal', $jurnal->id, 'Transaksi jurnal diubah: '.$this->label($jurnal), $changes);
        }

        return response()->json(['message' => 'Transaksi diperbarui.']);
    }

    public function destroy(JurnalUmum $jurnal)
    {
        $values = $this->values($jurnal);
        $label = $this->label($jurnal);
        $jurnal->delete();
        Audit::log('hapus', 'Jurnal', $jurnal->id, 'Transaksi jurnal dihapus: '.$label, $values);

        return response()->json(['message' => 'Transaksi dihapus.']);
    }

    private function validated(Request $request): array
    {
        // Terima angka berformat Rupiah ("1.500.000").
        foreach (['debet', 'kredit'] as $f) {
            $raw = $request->input($f);
            $request->merge([$f => ($raw === null || $raw === '') ? 0 : (JurnalUmumImport::parseAmount($raw) ?? $raw)]);
        }
        $v = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'keterangan' => ['required', 'string', 'max:255'],
            'sub_divisi_id' => ['required', 'integer', 'exists:sub_divisions,id'],
            'sub_akun_biaya_id' => ['required', 'integer', 'exists:sub_akun_biaya,id'],
            'debet' => ['required', 'numeric', 'min:0'],
            'kredit' => ['required', 'numeric', 'min:0'],
        ], [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'keterangan.required' => 'Keterangan wajib diisi.',
            'sub_divisi_id.required' => 'Pilih toko.',
            'sub_akun_biaya_id.required' => 'Pilih kategori.',
            'debet.numeric' => 'Debet harus berupa angka.',
            'kredit.numeric' => 'Kredit harus berupa angka.',
        ]);
        if ((float) $v['debet'] == 0 && (float) $v['kredit'] == 0) {
            abort(response()->json(['message' => 'Isi debet atau kredit.', 'errors' => ['kredit' => ['Isi debet atau kredit.']]], 422));
        }
        $v['keterangan'] = trim($v['keterangan']);
        $v['row_hash'] = JurnalRowHash::make($v['tanggal'], $v['sub_divisi_id'], $v['sub_akun_biaya_id'], $v['debet'], $v['kredit'], $v['keterangan']);

        return $v;
    }

    private function values(JurnalUmum $j): array
    {
        $toko = DB::table('sub_divisions')->where('id', $j->sub_divisi_id)->value('name');
        $kategori = DB::table('sub_akun_biaya')->where('id', $j->sub_akun_biaya_id)->value('name');

        return [
            'tanggal' => $j->tanggal ? substr((string) $j->tanggal, 0, 10) : null,
            'keterangan' => $j->keterangan,
            'toko' => $toko,
            'kategori' => $kategori,
            'debet' => (float) $j->debet,
            'kredit' => (float) $j->kredit,
        ];
    }

    private function label(JurnalUmum $j): string
    {
        return ($j->tanggal ? substr((string) $j->tanggal, 0, 10).' · ' : '').mb_strimwidth((string) $j->keterangan, 0, 60, '…');
    }
}
