<?php

namespace Modules\Penanaman\App\Services\Imports;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PenanamanImportValidator
{
    private array $lookupCache = [];

    public function __construct(private ?string $allowedRegencyId = null)
    {
    }

    public function __invoke(array $row, int $rowNumber): bool|array
    {
        return $this->parse($row)['errors'] ?: true;
    }

    public function resolve(array $row): array
    {
        $result = $this->parse($row);
        if ($result['errors']) {
            throw new InvalidArgumentException(implode(' ', $result['errors']));
        }

        return $result['data'];
    }

    private function parse(array $row): array
    {
        $errors = [];
        $text = fn ($key) => trim((string) ($row[$key] ?? ''));
        $yearText = $text('tahun');
        $monthText = $text('bulan_angka');
        $areaText = str_replace(',', '.', $text('realisasi_ha'));
        $locationName = $text('nama_lokasi');
        $coordinates = $text('koordinat');
        $regencyName = $text('nama_kabupaten');
        $districtName = $text('nama_kecamatan');
        $villageName = $text('nama_desa');
        $fundSourceName = $text('sumber_dana');

        $year = filter_var($yearText, FILTER_VALIDATE_INT);
        if ($year === false || $year < 2000 || $year > 2100) $errors[] = 'Tahun harus berupa angka 2000–2100.';

        $month = filter_var($monthText, FILTER_VALIDATE_INT);
        if ($month === false || $month < 1 || $month > 12) $errors[] = 'Bulan harus berupa angka 1–12.';

        if ($locationName === '' || strlen($locationName) > 255) $errors[] = 'Nama lokasi wajib diisi (maksimal 255 karakter).';
        if (strlen($coordinates) > 100) $errors[] = 'Koordinat maksimal 100 karakter.';
        if ($areaText === '' || !is_numeric($areaText) || (float) $areaText < 0 || (float) $areaText > 9999999999999.99) {
            $errors[] = 'Realisasi luas harus berupa angka 0 atau lebih.';
        }

        $regency = $regencyName !== ''
            ? $this->lookup('m_regencies', ['province_id' => 35], $regencyName)
            : null;
        if (!$regency) {
            $errors[] = 'Kabupaten/kota tidak ditemukan di Jawa Timur.';
        } elseif ($this->allowedRegencyId !== null && (string) $regency->id !== $this->allowedRegencyId) {
            $errors[] = 'Kabupaten/kota berada di luar wilayah akun.';
        }

        $district = $regency && $districtName !== ''
            ? $this->lookup('m_districts', ['regency_id' => $regency->id], $districtName)
            : null;
        if (!$district) $errors[] = 'Kecamatan tidak ditemukan pada kabupaten/kota terpilih.';

        $village = $district && $villageName !== ''
            ? $this->lookup('m_villages', ['district_id' => $district->id], $villageName)
            : null;
        if (!$village) $errors[] = 'Desa/kelurahan tidak ditemukan pada kecamatan terpilih.';

        $fundSource = $fundSourceName !== ''
            ? $this->lookup('m_sumber_dana', [], $fundSourceName)
            : null;
        if (!$fundSource) $errors[] = 'Sumber dana tidak ditemukan di master.';

        return [
            'errors' => $errors,
            'data' => $errors ? [] : [
                'year' => $year,
                'month' => $month,
                'regency_id' => (string) $regency->id,
                'district_id' => (string) $district->id,
                'village_id' => (string) $village->id,
                'location_name' => $locationName,
                'coordinates' => $coordinates ?: null,
                'realization_ha' => (float) $areaText,
                'fund_source' => $fundSource->name,
            ],
        ];
    }

    private function lookup(string $table, array $scope, string $name): ?object
    {
        $cacheKey = $table . '|' . json_encode($scope) . '|' . strtolower($name);
        if (!array_key_exists($cacheKey, $this->lookupCache)) {
            $this->lookupCache[$cacheKey] = DB::table($table)
                ->where($scope)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->first(['id', 'name']);
        }

        return $this->lookupCache[$cacheKey];
    }
}
