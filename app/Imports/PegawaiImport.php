<?php

namespace App\Imports;

use App\Models\Pegawai;
use App\Models\Bezetting;
use Illuminate\Support\Facades\Auth;
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
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir_yyyymmdd' => 'required|date_format:Y-m-d',
            'jenis_kelamin_lp' => 'required|in:L,P',
            'agama_islamkristenkatolickhindubuhakonghucu' => 'required|in:Islam,Kristen,Katolik,Hindu,Budha,Konghucu',
            'pendidikan_terakhir' => 'required|string|max:255',
            'status_pegawai_pnscpnspppkptthonorer' => 'required|in:PNS,CPNS,PPPK,PTT,Honorer',
            'bup_angka_tahun_misal_60' => 'required|numeric|min:50|max:70',
            'status_kedudukan_aktifpenisunmutasimeninggal' => 'required|in:Aktif,Pensiun,Mutasi,Meninggal',
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
            'agama_islamkristenkatolickhindubuhakonghucu.required' => 'Agama wajib diisi.',
            'agama_islamkristenkatolickhindubuhakonghucu.in' => 'Agama tidak valid. Pilihan: Islam, Kristen, Katolik, Hindu, Budha, Konghucu.',
            'pendidikan_terakhir.required' => 'Pendidikan Terakhir wajib diisi.',
            'status_pegawai_pnscpnspppkptthonorer.required' => 'Status Pegawai wajib diisi.',
            'status_pegawai_pnscpnspppkptthonorer.in' => 'Status Pegawai tidak valid. Pilihan: PNS, CPNS, PPPK, PTT, Honorer.',
            'bup_angka_tahun_misal_60.required' => 'BUP wajib diisi.',
            'bup_angka_tahun_misal_60.numeric' => 'BUP harus berupa angka.',
            'status_kedudukan_aktifpenisunmutasimeninggal.required' => 'Status Kedudukan wajib diisi.',
            'status_kedudukan_aktifpenisunmutasimeninggal.in' => 'Status Kedudukan tidak valid. Pilihan: Aktif, Pensiun, Mutasi, Meninggal.',
        ];
    }

    public function model(array $row)
    {
        $nip = trim($row['nip'] ?? '');
        if (empty($nip)) return null;

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

        return new Pegawai([
            'nip'                => $nip,
            'nama_lengkap'       => trim($row['nama_lengkap']),
            'nik'                => $nik,
            'tempat_lahir'       => trim($row['tempat_lahir']),
            'tanggal_lahir'      => $row['tanggal_lahir_yyyymmdd'],
            'jenis_kelamin'      => trim($row['jenis_kelamin_lp']),
            'agama'              => trim($row['agama_islamkristenkatolickhindubuhakonghucu']),
            'pendidikan_terakhir'=> trim($row['pendidikan_terakhir']),
            'status_pegawai'     => trim($row['status_pegawai_pnscpnspppkptthonorer']),
            'status_pernikahan'  => $statusPernikahan,
            'alamat'             => !empty($row['alamat']) ? trim($row['alamat']) : null,
            'tmt_cpns'           => $tmtCpns,
            'tmt_pns'            => $tmtPns,
            'unit_kerja'         => !empty($row['unit_kerja']) ? trim($row['unit_kerja']) : null,
            'skpd'               => !empty($row['skpd']) ? trim($row['skpd']) : null,
            'bezetting_id'       => $bezettingId,
            'pangkat_golongan'   => !empty($row['pangkat_golongan']) ? trim($row['pangkat_golongan']) : null,
            'bup'                => (int) $row['bup_angka_tahun_misal_60'],
            'status_kedudukan'   => trim($row['status_kedudukan_aktifpenisunmutasimeninggal']),
            'status'             => 'draft',
            'created_by'         => Auth::id(),
        ]);
    }
}
