<?php

namespace App\Imports;

use App\Models\Pegawai;
use App\Models\Bezetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Enums\Agama;
use App\Enums\StatusPegawai;
use App\Enums\StatusKedudukan;
use App\Enums\Pendidikan;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class PegawaiImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function rules(): array
    {
        return [
            'nip' => 'required|string',
            'nik' => 'nullable|string',
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir_yyyymmdd' => 'required|date_format:Y-m-d',
            'jenis_kelamin_lp' => 'required|in:L,P',
            'agama' => ['required', Rule::enum(Agama::class)],
            'pendidikan_terakhir' => ['required', Rule::enum(Pendidikan::class)],
            'status_pegawai' => ['required', Rule::enum(StatusPegawai::class)],
            'bup' => 'required|numeric|min:50|max:80',
            'status_kedudukan' => ['required', Rule::enum(StatusKedudukan::class)],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nip.required' => 'NIP wajib diisi.',
            'nama_lengkap.required' => 'Nama Lengkap wajib diisi.',
            'tempat_lahir.required' => 'Tempat Lahir wajib diisi.',
            'tanggal_lahir_yyyymmdd.required' => 'Tanggal Lahir wajib diisi.',
            'tanggal_lahir_yyyymmdd.date_format' => 'Tanggal Lahir harus format YYYY-MM-DD.',
            'jenis_kelamin_lp.required' => 'Jenis Kelamin wajib diisi.',
            'jenis_kelamin_lp.in' => 'Jenis Kelamin harus L atau P.',
            'agama.required' => 'Agama wajib diisi.',
            'agama.enum' => 'Agama tidak valid. Pilihan: ' . implode(', ', array_column(Agama::cases(), 'value')),
            'pendidikan_terakhir.required' => 'Pendidikan Terakhir wajib diisi.',
            'pendidikan_terakhir.enum' => 'Pendidikan tidak valid. Pilihan: ' . implode(', ', array_column(Pendidikan::cases(), 'value')),
            'status_pegawai.required' => 'Status Pegawai wajib diisi.',
            'status_pegawai.enum' => 'Status Pegawai tidak valid. Pilihan: ' . implode(', ', array_column(StatusPegawai::cases(), 'value')),
            'bup.required' => 'BUP wajib diisi.',
            'bup.numeric' => 'BUP harus berupa angka.',
            'status_kedudukan.required' => 'Status Kedudukan wajib diisi.',
            'status_kedudukan.enum' => 'Status Kedudukan tidak valid. Pilihan: ' . implode(', ', array_column(StatusKedudukan::cases(), 'value')),
        ];
    }

    public function model(array $row)
    {
        $nip = trim($row['nip'] ?? '');
        if (empty($nip))
            return null;

        // Skip if NIP already exists
        if (Pegawai::where('nip', $nip)->exists()) {
            return null;
        }

        // Lookup bezetting by nama_jabatan
        $bezettingId = null;
        $namaJabatan = trim($row['nama_jabatan_sesuai_data_jabatan'] ?? '');
        if (!empty($namaJabatan)) {
            $bezetting = Bezetting::whereRaw('LOWER(nama_jabatan) LIKE ?', ['%' . strtolower($namaJabatan) . '%'])->first();
            if ($bezetting) {
                $bezettingId = $bezetting->id;
            }
        }

        // Normalize nullable date fields
        $tmtCpns = !empty($row['tmt_cpns_yyyymmdd']) ? $row['tmt_cpns_yyyymmdd'] : null;
        $tmtPns = !empty($row['tmt_pns_yyyymmdd']) ? $row['tmt_pns_yyyymmdd'] : null;

        // Normalize nullable string fields
        $nik = !empty($row['nik']) ? trim($row['nik']) : null;
        $statusPernikahan = !empty($row['status_pernikahan_belum_kawinkawincerai_hidupcerai_mati']) ? trim($row['status_pernikahan_belum_kawinkawincerai_hidupcerai_mati']) : null;

        // Mapping & Nomalization
        $agama = trim($row['agama'] ?? $row['agama_islamkristenkatolickhindubuhakonghucu'] ?? '');
        $agamaMap = [
            'Kristen' => 'Kristen Protestan',
            'Budha' => 'Buddha',
        ];
        if (isset($agamaMap[$agama])) {
            $agama = $agamaMap[$agama];
        }

        $statusPegawai = trim($row['status_pegawai'] ?? $row['status_pegawai_pnscpnspppkptthonorer'] ?? '');
        $statusKedudukan = trim($row['status_kedudukan'] ?? $row['status_kedudukan_aktifpenisunmutasimeninggal'] ?? $row['status_kedudukan_aktifpenisunmutasimeninggal'] ?? '');
        $bup = (int) ($row['bup'] ?? $row['bup_angka_tahun_misal_60'] ?? 58);

        return new Pegawai([
            'nip' => $nip,
            'nama_lengkap' => trim($row['nama_lengkap']),
            'nik' => $nik,
            'tempat_lahir' => trim($row['tempat_lahir']),
            'tanggal_lahir' => $row['tanggal_lahir_yyyymmdd'],
            'jenis_kelamin' => trim($row['jenis_kelamin_lp']),
            'agama' => $agama,
            'pendidikan_terakhir' => trim($row['pendidikan_terakhir']),
            'status_pegawai' => $statusPegawai,
            'status_pernikahan' => $statusPernikahan,
            'alamat' => !empty($row['alamat']) ? trim($row['alamat']) : null,
            'tmt_cpns' => $tmtCpns,
            'tmt_pns' => $tmtPns,
            'unit_kerja' => !empty($row['unit_kerja']) ? Str::upper(trim($row['unit_kerja'])) : null,
            'skpd' => !empty($row['skpd']) ? Str::upper(trim($row['skpd'])) : null,
            'bezetting_id' => $bezettingId,
            'pangkat_golongan' => !empty($row['pangkat_golongan']) ? trim($row['pangkat_golongan']) : null,
            'bup' => $bup,
            'status_kedudukan' => $statusKedudukan,
            'status' => 'final',
            'created_by' => Auth::id(),
        ]);
    }
}
