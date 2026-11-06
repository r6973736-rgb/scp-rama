<?php

namespace App\Support;

use DOMDocument;
use RuntimeException;
use ZipArchive;

/**
 * Pembaca & penulis berkas Excel (.xlsx) sederhana.
 * Hanya butuh ekstensi PHP "zip" (bawaan XAMPP/Laragon), tanpa paket Composer.
 *
 * - tulis(): membuat berkas .xlsx; kolom bisa diformat Teks supaya NIP 18 digit
 *            dan angka 0 di depan NISN tidak dirusak Excel.
 * - baca():  membaca sheet pertama; nilai dibaca persis seperti tersimpan di berkas
 *            (tanpa dibulatkan menjadi float).
 */
class XlsxSederhana
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const NS_REL  = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const MAKS_BARIS = 10000;
    private const MAKS_UKURAN_XML = 30 * 1024 * 1024; // batas hasil ekstrak per berkas XML

    public static function tersedia(): bool
    {
        return class_exists(ZipArchive::class);
    }

    // ======================================================================
    // TULIS
    // ======================================================================

    /**
     * @param array $lembar daftar sheet, tiap sheet:
     *   'nama'    => nama sheet
     *   'baris'   => array baris (tiap baris array sel); baris pertama = judul bila 'header' true
     *   'header'  => true bila baris pertama dicetak tebal berlatar biru muda
     *   'teks'    => true bila seluruh kolom diformat Teks
     *   'lebar'   => lebar kolom (default 24)
     *   'bekukan' => true untuk membekukan baris pertama
     */
    public static function tulis(array $lembar): string
    {
        if (!self::tersedia()) {
            throw new RuntimeException('Ekstensi PHP zip belum aktif.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat berkas sementara.');
        }

        $jumlah = count($lembar);

        // [Content_Types].xml harus berada paling awal
        $tipe = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $jumlah; $i++) {
            $tipe .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $tipe .= '</Types>';
        $zip->addFromString('[Content_Types].xml', $tipe);

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="' . self::NS_REL . '/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        // workbook + relasinya
        $daftarSheet = '';
        $rel = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach (array_values($lembar) as $i => $l) {
            $n = $i + 1;
            $daftarSheet .= '<sheet name="' . self::esc(self::namaSheet($l['nama'] ?? 'Sheet' . $n)) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $rel .= '<Relationship Id="rId' . $n . '" Type="' . self::NS_REL . '/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
        }
        $rel .= '<Relationship Id="rId' . ($jumlah + 1) . '" Type="' . self::NS_REL . '/styles" Target="styles.xml"/></Relationships>';

        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="' . self::NS_MAIN . '" xmlns:r="' . self::NS_REL . '"><sheets>' . $daftarSheet . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rel);
        $zip->addFromString('xl/styles.xml', self::xmlGaya());

        foreach (array_values($lembar) as $i => $l) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', self::xmlLembar($l));
        }

        $zip->close();
        $isi = file_get_contents($tmp);
        @unlink($tmp);

        return $isi;
    }

    /** Gaya: 0 = biasa, 1 = Teks (format @), 2 = judul (tebal, latar biru muda, Teks). */
    private static function xmlGaya(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="' . self::NS_MAIN . '">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDDEBF7"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="49" fontId="1" fillId="2" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private static function xmlLembar(array $l): string
    {
        $baris = array_values($l['baris'] ?? []);
        $lebar = $l['lebar'] ?? 24;
        $teks  = !empty($l['teks']);

        $jumlahKolom = 1;
        foreach ($baris as $sel) {
            $jumlahKolom = max($jumlahKolom, count($sel));
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="' . self::NS_MAIN . '">';
        if (!empty($l['bekukan'])) {
            $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        }
        $xml .= '<cols><col min="1" max="' . $jumlahKolom . '" width="' . $lebar . '" customWidth="1"' . ($teks ? ' style="1"' : '') . '/></cols>';
        $xml .= '<sheetData>';
        foreach ($baris as $i => $sel) {
            $r = $i + 1;
            $xml .= '<row r="' . $r . '">';
            foreach (array_values($sel) as $j => $nilai) {
                if ($nilai === null || $nilai === '') {
                    continue; // sel kosong ikut gaya kolom
                }
                $gaya = ($i === 0 && !empty($l['header'])) ? 2 : ($teks ? 1 : 0);
                $xml .= '<c r="' . self::huruf($j) . $r . '" s="' . $gaya . '" t="inlineStr"><is><t xml:space="preserve">'
                    . self::esc((string) $nilai) . '</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }

    private static function esc(string $teks): string
    {
        // buang karakter kontrol yang tidak sah di XML, lalu escape
        $teks = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $teks);

        return htmlspecialchars($teks, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Nama sheet Excel: maks 31 karakter, tanpa : \ / ? * [ ] */
    private static function namaSheet(string $nama): string
    {
        $nama = trim(str_replace([':', '\\', '/', '?', '*', '[', ']'], ' ', $nama));

        return mb_substr($nama !== '' ? $nama : 'Sheet', 0, 31);
    }

    /** 0 => A, 25 => Z, 26 => AA */
    private static function huruf(int $indeks): string
    {
        $huruf = '';
        $indeks++;
        while ($indeks > 0) {
            $sisa = ($indeks - 1) % 26;
            $huruf = chr(65 + $sisa) . $huruf;
            $indeks = intdiv($indeks - 1, 26);
        }

        return $huruf;
    }

    // ======================================================================
    // BACA
    // ======================================================================

    /** Baca sheet pertama menjadi array baris (daftar sel string). Baris kosong tetap ada agar nomor baris cocok. */
    public static function baca(string $jalur): array
    {
        if (!self::tersedia()) {
            throw new RuntimeException('Ekstensi PHP zip belum aktif.');
        }

        $zip = new ZipArchive();
        if ($zip->open($jalur) !== true) {
            throw new RuntimeException('Berkas bukan Excel (.xlsx) yang valid.');
        }

        try {
            // Teks bersama (shared strings)
            $bersama = [];
            $xmlBersama = self::ambil($zip, 'xl/sharedStrings.xml');
            if ($xmlBersama !== null) {
                $dom = self::muatXml($xmlBersama);
                foreach ($dom->getElementsByTagName('si') as $si) {
                    $teks = '';
                    foreach ($si->childNodes as $anak) {
                        if ($anak->localName === 't') {
                            $teks .= $anak->textContent;
                        } elseif ($anak->localName === 'r') {
                            foreach ($anak->childNodes as $t) {
                                if ($t->localName === 't') {
                                    $teks .= $t->textContent;
                                }
                            }
                        }
                    }
                    $bersama[] = $teks;
                }
            }

            $xmlLembar = self::ambil($zip, self::jalurSheetPertama($zip));
            if ($xmlLembar === null) {
                throw new RuntimeException('Sheet pertama tidak ditemukan di berkas.');
            }
        } finally {
            $zip->close();
        }

        return self::uraiLembar(self::muatXml($xmlLembar), $bersama);
    }

    private static function uraiLembar(DOMDocument $dom, array $bersama): array
    {
        $data = $dom->getElementsByTagName('sheetData')->item(0);
        if (!$data) {
            return [];
        }

        $peta = [];
        $maksBaris = 0;
        $urutBaris = 0;

        foreach ($data->childNodes as $baris) {
            if ($baris->localName !== 'row') {
                continue;
            }
            $urutBaris++;
            $noBaris = (int) $baris->getAttribute('r') ?: $urutBaris;
            if ($noBaris > self::MAKS_BARIS + 1) {
                throw new RuntimeException('Terlalu banyak baris (maksimal ' . number_format(self::MAKS_BARIS, 0, ',', '.') . ' baris data).');
            }

            $urutKolom = -1;
            foreach ($baris->childNodes as $sel) {
                if ($sel->localName !== 'c') {
                    continue;
                }
                $urutKolom++;
                $ref = $sel->getAttribute('r');
                $kolom = $urutKolom;
                if ($ref !== '' && preg_match('/^([A-Z]+)/', $ref, $m)) {
                    $kolom = self::indeksKolom($m[1]);
                    $urutKolom = $kolom;
                }

                $tipe = $sel->getAttribute('t');
                $v = '';
                $teksInline = '';
                foreach ($sel->childNodes as $anak) {
                    if ($anak->localName === 'v') {
                        $v = $anak->textContent;
                    } elseif ($anak->localName === 'is') {
                        foreach ($anak->childNodes as $x) {
                            if ($x->localName === 't') {
                                $teksInline .= $x->textContent;
                            } elseif ($x->localName === 'r') {
                                foreach ($x->childNodes as $y) {
                                    if ($y->localName === 't') {
                                        $teksInline .= $y->textContent;
                                    }
                                }
                            }
                        }
                    }
                }

                $nilai = match ($tipe) {
                    's'         => $bersama[(int) $v] ?? '',
                    'inlineStr' => $teksInline,
                    'e'         => '',
                    default     => $v, // angka & teks rumus dibaca apa adanya (tanpa dibulatkan)
                };

                $peta[$noBaris][$kolom] = (string) $nilai;
                $maksBaris = max($maksBaris, $noBaris);
            }
            $maksBaris = max($maksBaris, $noBaris);
        }

        // Susun menjadi larik rapat: indeks 0 = baris 1, dst.
        $hasil = [];
        for ($r = 1; $r <= $maksBaris; $r++) {
            $sel = $peta[$r] ?? [];
            if (!$sel) {
                $hasil[] = [];
                continue;
            }
            $maksKolom = max(array_keys($sel));
            $rapat = array_fill(0, $maksKolom + 1, '');
            foreach ($sel as $k => $nilai) {
                $rapat[$k] = $nilai;
            }
            $hasil[] = $rapat;
        }

        return $hasil;
    }

    /** Lokasi file sheet pertama menurut urutan di workbook.xml. */
    private static function jalurSheetPertama(ZipArchive $zip): string
    {
        $bawaan = 'xl/worksheets/sheet1.xml';

        $xmlBuku = self::ambil($zip, 'xl/workbook.xml');
        $xmlRel  = self::ambil($zip, 'xl/_rels/workbook.xml.rels');
        if ($xmlBuku === null || $xmlRel === null) {
            return $bawaan;
        }

        $sheet = self::muatXml($xmlBuku)->getElementsByTagName('sheet')->item(0);
        if (!$sheet) {
            return $bawaan;
        }
        $rid = $sheet->getAttributeNS(self::NS_REL, 'id');

        foreach (self::muatXml($xmlRel)->getElementsByTagName('Relationship') as $rel) {
            if ($rel->getAttribute('Id') === $rid) {
                $target = $rel->getAttribute('Target');

                return str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . $target;
            }
        }

        return $bawaan;
    }

    private static function ambil(ZipArchive $zip, string $nama): ?string
    {
        $info = $zip->statName($nama);
        if ($info === false) {
            return null;
        }
        if ($info['size'] > self::MAKS_UKURAN_XML) {
            throw new RuntimeException('Isi berkas terlalu besar.');
        }
        $isi = $zip->getFromName($nama);

        return $isi === false ? null : $isi;
    }

    private static function muatXml(string $xml): DOMDocument
    {
        $dom = new DOMDocument();
        $lama = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($lama);
        if (!$ok) {
            throw new RuntimeException('Isi berkas Excel rusak atau tidak dapat dibaca.');
        }

        return $dom;
    }

    /** A => 0, Z => 25, AA => 26 */
    private static function indeksKolom(string $huruf): int
    {
        $n = 0;
        foreach (str_split($huruf) as $c) {
            $n = $n * 26 + (ord($c) - 64);
        }

        return $n - 1;
    }
}
