@extends('pdf.layout')

@section('content')
    <div class="WordSection1 pdf-container">
        <div class="pdf-header kop-wrap">
            <table class="kop-table">
                <tr>
                    <td style="width: 52pt;">
                        <div class="kop-logo">LOGO 1</div>
                    </td>
                    <td class="kop-center">
                        <div class="kop-line-1">PEMERINTAH PROVINSI JAWA TENGAH</div>
                        <div class="kop-line-2">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
                        <div class="kop-school">SEKOLAH MENENGAH KEJURUAN NEGERI KEBASEN</div>
                        <div class="kop-subline">
                            Jalan Raya Bentul, Kebasen, Banyumas Kode Pos 53172
                            Telepon 0281-6511068 Faksimile 0281-6847525
                            Laman www.smknkebasen.sch.id
                            Pos-el smknkebasen@gmail.com
                        </div>
                    </td>
                    <td style="width: 52pt; text-align: right;">
                        <div class="kop-logo">LOGO 2</div>
                    </td>
                </tr>
            </table>
        </div>

        <h1 class="pdf-title">Laporan Aspirasi</h1>
        <p class="pdf-meta">Diunduh: {{ $downloadedAt ?? '-' }}</p>

        <table class="pdf-table">
            <thead>
                <tr>
                    <th style="width: 30pt;">No</th>
                    <th>Nama</th>
                    <th style="width: 70pt;">NIS</th>
                    <th>Tujuan</th>
                    <th>Kategori</th>
                    <th style="width: 90pt;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records ?? [] as $index => $record)
                    @php
                        $isAnonymous = (bool) ($record->is_anonymous ?? false);
                        $name = $isAnonymous ? 'Anonim' : ($record->user?->name ?? '-');
                        $nis = $isAnonymous ? 'Anonim' : ($record->user?->nis ?? '-');
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $name }}</td>
                        <td>{{ $nis }}</td>
                        <td>{{ $record->tujuan?->name ?? '-' }}</td>
                        <td>{{ $record->kategori?->name ?? '-' }}</td>
                        <td>{{ $record->status ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
