<?php

namespace App\Services;

use App\Enums\Satuan;
use Illuminate\Support\Facades\DB;

class NonWoodProductionStats
{
    public function forYear(int $year, ?int $cdkId = null): array
    {
        // Read raw units: enum casting would reject historical, unknown values.
        $rows = DB::table('hasil_hutan_bukan_kayu_details as d')
            ->join('hasil_hutan_bukan_kayu as h', 'd.hasil_hutan_bukan_kayu_id', '=', 'h.id')
            ->leftJoin('m_bukan_kayu as c', 'd.bukan_kayu_id', '=', 'c.id')
            ->where('h.year', $year)
            ->where('h.status', 'final')
            ->whereNull('h.deleted_at')
            ->when($cdkId !== null, fn ($q) => $q->where('h.cdk_id', $cdkId))
            ->selectRaw('h.forest_type, h.month, d.unit, d.bukan_kayu_id as commodity_id, c.name as commodity_name, SUM(d.annual_volume_realization) as total')
            ->groupBy('h.forest_type', 'h.month', 'd.unit', 'd.bukan_kayu_id', 'c.name')
            // Preserve original unknown units even with a case-insensitive MySQL collation.
            ->groupByRaw('HEX(d.unit)')
            ->get();

        return $this->aggregate($rows);
    }

    public function aggregate(iterable $rows): array
    {
        $result = [];
        $units = Satuan::cases();
        $order = array_flip(array_map(fn ($unit) => $unit->value, $units));

        foreach ($rows as $row) {
            $forest = strtolower(str_replace(' ', '_', $row->forest_type));
            $result[$forest] ??= ['bukan_kayu_by_unit' => [], 'bukan_kayu_unspecified' => []];
            $rawUnit = $row->unit;
            $unit = strtolower(trim($rawUnit ?? ''));
            $known = Satuan::tryFrom($unit);
            $value = (float) $row->total;
            $id = (int) $row->commodity_id;
            $name = $row->commodity_name ?? 'Komoditas tidak tersedia';

            if ($known === null || $known === Satuan::LAINNYA) {
                // Never combine ambiguous measurements across commodities or original units.
                $key = json_encode([$id, $rawUnit]);
                $result[$forest]['bukan_kayu_unspecified'][$key] ??= [
                    'id' => $id, 'name' => $name, 'unit' => $rawUnit, 'total' => 0,
                ];
                $result[$forest]['bukan_kayu_unspecified'][$key]['total'] += $value;
                continue;
            }

            $result[$forest]['bukan_kayu_by_unit'][$unit] ??= [
                'unit' => $unit, 'label' => $known->label(), 'total' => 0,
                'monthly' => array_fill(1, 12, 0), 'commodities' => [],
            ];
            $group = &$result[$forest]['bukan_kayu_by_unit'][$unit];
            $group['total'] += $value;
            $group['monthly'][(int) $row->month] += $value;
            $group['commodities'][$id] ??= ['id' => $id, 'name' => $name, 'total' => 0];
            $group['commodities'][$id]['total'] += $value;
            unset($group);
        }

        foreach ($result as &$forest) {
            uasort($forest['bukan_kayu_by_unit'], fn ($a, $b) => $order[$a['unit']] <=> $order[$b['unit']]);
            foreach ($forest['bukan_kayu_by_unit'] as &$group) {
                $group['commodities'] = array_values($group['commodities']);
                usort($group['commodities'], fn ($a, $b) => ($b['total'] <=> $a['total']) ?: strcmp($a['name'], $b['name']) ?: ($a['id'] <=> $b['id']));
            }
            unset($group);
            $forest['bukan_kayu_by_unit'] = array_values($forest['bukan_kayu_by_unit']);
            $forest['bukan_kayu_unspecified'] = array_values($forest['bukan_kayu_unspecified']);
            usort($forest['bukan_kayu_unspecified'], fn ($a, $b) => strcmp($a['name'], $b['name']) ?: strcmp($a['unit'] ?? '', $b['unit'] ?? ''));
        }
        unset($forest);

        return $result;
    }
}
