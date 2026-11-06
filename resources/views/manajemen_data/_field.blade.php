{{-- Satu input form. Param: $nama, $k (definisi kolom), $opsi, $awal (nilai awal), $idp (prefix id) --}}
@php
    $tipe = $k[1];
    $daftar = $k[3] ?? null;
    if (is_string($daftar)) { $daftar = $opsi[$daftar] ?? []; }
    $id = $idp . '_' . $nama;
@endphp
@if($tipe === 'check')
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="{{ $nama }}" id="{{ $id }}" value="1" {{ $awal ? 'checked' : '' }}>
        <label class="form-check-label" for="{{ $id }}">{{ $k[0] }}</label>
    </div>
@else
    <div class="mb-3">
        <label class="form-label" for="{{ $id }}">{{ $k[0] }}</label>
        @if($tipe === 'select')
            <select name="{{ $nama }}" id="{{ $id }}" class="form-select" {{ str_starts_with($k[2], 'required') ? 'required' : '' }}>
                <option value="">-- Pilih --</option>
                @foreach($daftar as $v => $teks)
                    <option value="{{ $v }}" {{ (string) $awal === (string) $v ? 'selected' : '' }}>{{ $teks }}</option>
                @endforeach
            </select>
        @else
            <input type="text" name="{{ $nama }}" id="{{ $id }}" class="form-control" value="{{ $awal }}"
                   {{ $tipe === 'datalist' ? 'list=dl_' . $id : '' }} {{ str_starts_with($k[2], 'required') ? 'required' : '' }}>
            @if($tipe === 'datalist')
                <datalist id="dl_{{ $id }}">@foreach($daftar as $v => $teks)<option value="{{ $v }}">@endforeach</datalist>
            @endif
        @endif
    </div>
@endif
