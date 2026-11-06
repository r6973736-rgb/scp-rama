<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\AlertJamSelesai;

class WakaController extends Controller
{
    public function dashboard()
    {
        // PENGAMAN ANTI-BOBOL: Cek apakah user yang login memiliki id_role == 4 (Waka Kurikulum)
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Waka Kurikulum.']);
        }

        $config = DB::table('system_configurations')->first();

        if (!$config) {
            DB::table('system_configurations')->insert([
                'active_cycle' => '1',
                'is_schedule_locked' => false,
                'start_date' => now(),
                'end_date' => now()->addWeeks(2),
            ]);
            $config = DB::table('system_configurations')->first();
        }

        $alertAwal = AlertJamSelesai::ambil();

        return view('waka.dashboard', compact('config', 'alertAwal'));
    }

    public function updateConfig(Request $request)
    {
        // PENGAMAN ANTI-BOBOL: Pastikan hanya Waka Kurikulum yang bisa menyimpan perubahan konfigurasi
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Anda tidak memiliki hak untuk mengubah konfigurasi.']);
        }

        $request->validate([
            'active_cycle' => 'required|in:1,2',
            'is_schedule_locked' => 'required|boolean',
        ]);

        DB::table('system_configurations')->where('id', 1)->update([
            'active_cycle' => $request->active_cycle,
            'is_schedule_locked' => $request->is_schedule_locked,
        ]);

        return back()->with('success', 'Konfigurasi siklus dan status jadwal berhasil diperbarui!');
    }

    /** Data alert (JSON) untuk polling otomatis di dashboard Waka. */
    public function alertData()
    {
        return response()->json(AlertJamSelesai::ambil())
            ->header('Cache-Control', 'no-store');
    }
}
