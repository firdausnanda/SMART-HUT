import React, { useState, useEffect } from 'react';
import { formatNumber } from './utils';
import { selectedUnit, topCommodities } from './nonWoodUtils';
import { NonWoodUnitCards, NonWoodUnspecified } from './NonWoodDetails';
import { ProductionPanel, ProductionMetric, ProductionTrend, CommodityRanking, ProductionEmpty, NON_WOOD_COLOR } from './ProductionUI';

const months = Array.from({ length: 12 }, (_, i) => i + 1);
const monthLabels = months.map(month => new Date(2024, month - 1, 1).toLocaleString('id-ID', { month: 'short' }));

const ProductionSlide = ({ source, stats, currentYear, commonOptions }) => {
  const production = stats?.bina_usaha?.[source.key] ?? {};
  const groups = production.bukan_kayu_by_unit ?? [];
  const [preferredUnit, setPreferredUnit] = useState('kg');
  const group = selectedUnit(groups, preferredUnit);
  useEffect(() => { setPreferredUnit(group?.unit ?? 'kg'); }, [group?.unit]);
  const woodTotal = Number(production.kayu_total ?? 0);
  const woodTarget = Number(production.kayu_target ?? 0);
  const achievement = woodTarget > 0 ? woodTotal / woodTarget * 100 : null;
  const woodCommodities = Object.entries(production.kayu_commodity ?? {}).map(([name, total]) => ({ name, total }));

  return <div className="w-full min-w-full shrink-0 px-1 sm:px-4">
    <div className="mx-auto max-w-7xl space-y-5">
      <ProductionPanel title="Hasil hutan kayu" code="HHK" subtitle={`Ringkasan produksi ${currentYear}`} color={source.color}>
        <div className="grid grid-cols-2 gap-5 border-b border-slate-100 bg-slate-50/60 px-5 py-5 sm:grid-cols-3 sm:px-6">
          <ProductionMetric label="Total realisasi" value={formatNumber(woodTotal)} unit="m³" color={source.color} />
          <ProductionMetric label="Target produksi" value={formatNumber(woodTarget)} unit="m³" />
          <div className="col-span-2 sm:col-span-1">
            <ProductionMetric label="Capaian target" value={achievement == null ? '—' : `${formatNumber(Number(achievement.toFixed(1)))}%`} note={achievement == null ? 'Target belum tersedia.' : undefined} />
            {achievement != null && <div className="mt-2 h-1.5 max-w-[180px] overflow-hidden rounded-full bg-slate-200" aria-hidden="true"><div className="h-full rounded-full" style={{ width: `${Math.min(Math.max(achievement, 0), 100)}%`, backgroundColor: source.color }} /></div>}
          </div>
        </div>
        <div className="grid gap-6 px-5 py-5 sm:px-6 lg:grid-cols-3">
          <div className="min-w-0 lg:col-span-2">
            <ProductionTrend labels={monthLabels} values={months.map(m => production.kayu_monthly?.[m] ?? 0)} target={woodTarget > 0 ? months.map(m => production.kayu_target_monthly?.[m] ?? 0) : undefined} unit="m³" color={source.color} commonOptions={commonOptions} />
          </div>
          <div className="border-t border-slate-100 pt-5 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
            <CommodityRanking items={woodCommodities} unit="m³" color={source.color} />
          </div>
        </div>
      </ProductionPanel>

      <ProductionPanel title="Hasil hutan bukan kayu" code="HHBK" subtitle="Pilih satuan untuk melihat tren dan komoditasnya" color={NON_WOOD_COLOR}>
        {!!groups.length && <div className="border-b border-slate-100 bg-amber-50/30 px-5 py-4 sm:px-6">
          <NonWoodUnitCards groups={groups} value={group?.unit} onChange={setPreferredUnit} />
        </div>}
        {group ? <div className="grid gap-6 px-5 py-5 sm:px-6 lg:grid-cols-3">
          <div className="min-w-0 lg:col-span-2">
            <ProductionTrend labels={monthLabels} values={months.map(m => group.monthly?.[m] ?? 0)} unit={group.unit} color={NON_WOOD_COLOR} commonOptions={commonOptions} />
          </div>
          <div className="border-t border-slate-100 pt-5 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
            <CommodityRanking items={topCommodities(group)} unit={group.unit} color={NON_WOOD_COLOR} />
          </div>
        </div> : <ProductionEmpty>{production.bukan_kayu_unspecified?.length ? 'Belum ada produksi dengan satuan spesifik.' : undefined}</ProductionEmpty>}
        {!!production.bukan_kayu_unspecified?.length && <footer className="border-t border-slate-100 px-5 py-3 sm:px-6">
          <NonWoodUnspecified rows={production.bukan_kayu_unspecified} />
        </footer>}
      </ProductionPanel>
    </div>
  </div>;
};

export default React.memo(ProductionSlide);
