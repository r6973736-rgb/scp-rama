<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Jam pelajaran per hari: hari => [[mulai, selesai], ...] (urutan = jam ke-1, 2, ...) */
    private function data(): array
    {
        return [
            'Senin' => [['08:00', '08:35'], ['08:35', '09:10'], ['09:10', '09:45'], ['10:00', '10:35'], ['10:35', '11:10'], ['11:10', '11:45'], ['12:45', '13:25'], ['13:25', '14:00'], ['14:00', '14:35'], ['14:35', '15:10']],
            'Selasa' => [['07:30', '08:10'], ['08:10', '08:50'], ['08:50', '09:30'], ['09:45', '10:25'], ['10:25', '11:05'], ['11:05', '11:45'], ['12:45', '13:25'], ['13:25', '14:00'], ['14:00', '14:35'], ['14:45', '15:10']],
            'Rabu' => [['07:15', '07:55'], ['07:55', '08:35'], ['08:35', '09:15'], ['09:30', '10:10'], ['10:10', '10:50'], ['10:50', '11:30'], ['12:30', '13:10'], ['13:10', '13:50'], ['13:50', '14:30'], ['14:30', '15:10']],
            'Kamis' => [['07:15', '07:55'], ['07:55', '08:35'], ['08:35', '09:15'], ['09:30', '10:10'], ['10:10', '10:50'], ['10:50', '11:30'], ['12:30', '13:10'], ['13:10', '13:50'], ['13:50', '14:30'], ['14:30', '15:10']],
            'Jumat' => [['07:15', '07:55'], ['07:55', '08:35'], ['08:35', '09:15'], ['09:30', '10:10'], ['10:10', '10:50'], ['10:50', '11:25'], ['12:40', '13:15'], ['13:15', '13:50']],
        ];
    }

    public function up(): void
    {
        if (!Schema::hasTable('sesi_jam')) {
            Schema::create('sesi_jam', function (Blueprint $table) {
                $table->increments('id_sesi');
                $table->string('hari', 10);
                $table->unsignedTinyInteger('sesi_ke');
                $table->time('jam_mulai');
                $table->time('jam_selesai');
                $table->unique(['hari', 'sesi_ke'], 'sesi_jam_hari_sesi_unique');
            });
        }

        if (DB::table('sesi_jam')->count() === 0) {
            foreach ($this->data() as $hari => $daftar) {
                foreach ($daftar as $i => [$mulai, $selesai]) {
                    DB::table('sesi_jam')->insert([
                        'hari' => $hari, 'sesi_ke' => $i + 1,
                        'jam_mulai' => $mulai . ':00', 'jam_selesai' => $selesai . ':00',
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_jam');
    }
};
