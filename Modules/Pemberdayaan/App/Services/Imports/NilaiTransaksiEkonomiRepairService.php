<?php

namespace Modules\Pemberdayaan\App\Services\Imports;

use Illuminate\Support\Facades\DB;

class NilaiTransaksiEkonomiRepairService
{
    public function preview(?int $scopeCdkId): array
    {
        $plan = $this->plan($scopeCdkId, false);

        return $this->summary($plan);
    }

    public function repair(?int $scopeCdkId, int $actorId): array
    {
        return DB::transaction(function () use ($scopeCdkId, $actorId) {
            $plan = $this->plan($scopeCdkId, true);
            $now = now();

            foreach ($plan['backfill'] as $id => $cdkId) {
                DB::table('nilai_transaksi_ekonomi')->where('id', $id)->update([
                    'cdk_id' => $cdkId,
                    'updated_by' => $actorId,
                    'updated_at' => $now,
                ]);
            }

            $detailsMoved = 0;
            foreach ($plan['groups'] as $group) {
                $keepId = $group['ids'][0];
                $mergeIds = array_slice($group['ids'], 1);

                $detailsMoved += DB::table('nilai_transaksi_ekonomi_details')
                    ->whereIn('nilai_transaksi_ekonomi_id', $mergeIds)
                    ->update(['nilai_transaksi_ekonomi_id' => $keepId, 'updated_at' => $now]);

                $total = DB::table('nilai_transaksi_ekonomi_details')
                    ->where('nilai_transaksi_ekonomi_id', $keepId)
                    ->sum('nilai_transaksi');

                DB::table('nilai_transaksi_ekonomi')->where('id', $keepId)->update([
                    'total_nilai_transaksi' => $total,
                    'updated_by' => $actorId,
                    'updated_at' => $now,
                ]);

                DB::table('nilai_transaksi_ekonomi')->whereIn('id', $mergeIds)->update([
                    'deleted_at' => $now,
                    'deleted_by' => $actorId,
                    'updated_by' => $actorId,
                    'updated_at' => $now,
                ]);
            }

            return $this->summary($plan) + ['details_moved' => $detailsMoved];
        });
    }

    private function plan(?int $scopeCdkId, bool $lock): array
    {
        $query = DB::table('nilai_transaksi_ekonomi as nte')
            ->leftJoin('users as creator', 'nte.created_by', '=', 'creator.id')
            ->where('nte.status', 'draft')
            ->whereNull('nte.deleted_at')
            ->when($scopeCdkId !== null, function ($query) use ($scopeCdkId) {
                $query->where(function ($query) use ($scopeCdkId) {
                    $query->where('nte.cdk_id', $scopeCdkId)
                        ->orWhere(function ($query) use ($scopeCdkId) {
                            $query->whereNull('nte.cdk_id')
                                ->where('creator.cdk_id', $scopeCdkId);
                        });
                });
            })
            ->select([
                'nte.id', 'nte.cdk_id', 'nte.year', 'nte.month',
                'nte.nama_kth', 'nte.province_id', 'nte.regency_id',
                'nte.district_id', 'nte.village_id',
                'creator.cdk_id as creator_cdk_id',
            ])
            ->orderBy('nte.id');

        if ($lock) {
            $query->lockForUpdate();
        }

        $groups = [];
        $backfill = [];
        $missingCdk = 0;
        $incompleteKey = 0;

        foreach ($query->get() as $row) {
            $cdkId = $row->cdk_id ?? $row->creator_cdk_id;
            if ($cdkId === null) {
                $missingCdk++;
                continue;
            }
            $cdkId = (int) $cdkId;

            if ($row->cdk_id === null) {
                $backfill[$row->id] = $cdkId;
            }

            if (!$row->year || !$row->month || !$row->regency_id || !$row->district_id
                || !$row->village_id || trim((string) $row->nama_kth) === '') {
                $incompleteKey++;
                continue;
            }

            $key = NilaiTransaksiEkonomiGroupKey::make(
                $cdkId,
                (int) $row->year,
                (int) $row->month,
                $row->nama_kth,
                $row->province_id === null ? null : (int) $row->province_id,
                (int) $row->regency_id,
                (int) $row->district_id,
                (int) $row->village_id,
            );

            $groups[$key]['ids'][] = (int) $row->id;
        }

        return [
            'groups' => array_values(array_filter($groups, fn($group) => count($group['ids']) > 1)),
            'backfill' => $backfill,
            'missing_cdk' => $missingCdk,
            'incomplete_key' => $incompleteKey,
        ];
    }

    private function summary(array $plan): array
    {
        return [
            'merged_groups' => count($plan['groups']),
            'merged_records' => array_sum(array_map(fn($group) => count($group['ids']) - 1, $plan['groups'])),
            'cdk_filled' => count($plan['backfill']),
            'missing_cdk' => $plan['missing_cdk'],
            'incomplete_key' => $plan['incomplete_key'],
            'groups' => array_slice(array_map(fn($group) => [
                'keep_id' => $group['ids'][0],
                'merge_ids' => array_slice($group['ids'], 1),
            ], $plan['groups']), 0, 20),
        ];
    }
}
