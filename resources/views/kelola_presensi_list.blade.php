@forelse($entries as $entry)
    @php
        $title = $entry['NamaCust'] ?? $entry['NAMA'] ?? $entry['NAMASISWA'] ?? 'Siswa';
        $unitVal = $entry['UNIT'] ?? $entry['Unit'] ?? null;
        $subtitle = $unitVal ? 'Unit: ' . $unitVal : null;
        $tanggalItem = $entry['TRXDATE'] ?? $entry['TANGGAL'] ?? $entry['DATE'] ?? $tanggal;
        $nisVal = $entry['NIS'] ?? $entry['NOKARTU'] ?? '';
        $searchText = strtolower($title . ' ' . $nisVal);
        $rowId = $entry['IDPRESENSI'] ?? $entry['ID'] ?? $entry['id'] ?? $entry['ID_PRESENSI'] ?? $entry['ID_TRX'] ?? '';
        if ($rowId === '') {
            $nokartu = $entry['NOKARTU'] ?? '';
            if ($nokartu !== '' && $tanggalItem !== '') {
                $rowId = $nokartu . '|' . $tanggalItem;
            } elseif ($title !== '' && $tanggalItem !== '') {
                $rowId = $title . '|' . $tanggalItem;
            }
        }
    @endphp
    <div class="card card-student" data-search="{{ $searchText }}">
        <div class="card-header">
            <div>
                <div class="card-title">{{ $title }}</div>
                @if($subtitle)<div class="card-sub">{{ $subtitle }}</div>@endif
            </div>
            <div class="chip-date">{{ $tanggalItem }}</div>
        </div>
        @php
            $sholatRows = [];
            for ($i = 1; $i <= 5; $i++) {
                $jadwalKey = 'JADWAL_' . $i;
                $jamKey = 'JAM_' . $i;
                $userKey = 'USER_' . $i;
                $statusVal = $entry[$jadwalKey] ?? null;
                $jamVal = $entry[$jamKey] ?? null;
                $userVal = $entry[$userKey] ?? null;
                if ($statusVal !== null) {
                    $sholatRows[] = [
                        'index' => $i,
                        'status' => $statusVal,
                        'jam' => $jamVal,
                        'user' => $userVal,
                    ];
                }
            }
        @endphp
        @if(count($sholatRows))
            <div class="sholat-list">
                @foreach($sholatRows as $row)
                    @php
                        $s = strtoupper((string) $row['status']);
                        if (in_array($s, ['SHOLAT', 'HAID'])) {
                            $cls = 'sholat-item sholat-sholat';
                        } elseif ($s === 'IZIN' || $s === 'TIDAK HADIR') {
                            $cls = 'sholat-item sholat-izin';
                        } elseif ($s === 'ALPA') {
                            $cls = 'sholat-item sholat-alpa';
                        } elseif ($s === 'SAKIT') {
                            $cls = 'sholat-item sholat-sakit';
                        } elseif ($s === 'BELUM PRESENSI') {
                            $cls = 'sholat-item sholat-belum';
                        } else {
                            $cls = 'sholat-item sholat-sholat';
                        }
                        $subLine = $row['jam'] ? trim($row['jam'] . ' ' . ($row['status'] ?? '') . ' ' . ($row['user'] ?? '')) : ($row['status'] . ($row['user'] ? ' ' . $row['user'] : ''));
                        $canEdit = !empty($rowId);
                    @endphp
                    <div class="sholat-row">
                        <div class="{{ $cls }}">
                            <div class="sholat-label">Sholat {{ $row['index'] }}</div>
                            <div class="sholat-sub">{{ $subLine ?: '-' }}</div>
                        </div>
                        @if($canEdit)
                            <button type="button" class="btn-edit btn-edit-presensi" title="Edit" data-id="{{ $rowId }}" data-session="{{ $row['index'] }}" data-name="{{ $title }}" data-unit="{{ $subtitle ?? '' }}">
                                <i class="fas fa-pen"></i>
                            </button>
                        @else
                            <button type="button" class="btn-edit" disabled title="ID tidak tersedia"><i class="fas fa-pen"></i></button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@empty
    <div class="empty"><i class="fas fa-clipboard-list"></i>Tidak ada data presensi untuk tanggal ini.</div>
@endforelse
