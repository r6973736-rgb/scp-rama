<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GuruController extends Controller
{
    public function dashboard()
    {
        // Pengaman anti-bobol: Pastikan yang masuk adalah role Guru (id_role == 2) atau selain waka
        if (!session()->has('id_user') || session('id_role') == 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Guru.']);
        }

        return view('guru.dashboard');
    }
}