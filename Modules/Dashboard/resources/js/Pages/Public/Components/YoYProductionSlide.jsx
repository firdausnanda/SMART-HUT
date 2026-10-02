import React, { useState, useEffect } from 'react';
import { formatNumber } from './utils';
import { availableUnits, selectedUnit, compareProduction, totalForUnit } from './nonWoodUtils';
import { NonWoodUnitSelect, NonWoodUnspecified } from './NonWoodDetails';
import { ProductionPanel, ProductionMetric, ProductionTrend, ProductionEmpty, NON_WOOD_COLOR } from './ProductionUI';

function YearComparison({ current, previous, years, unit, color }) {
  const comparison = compareProduction(current, previous);
  return <div className="grid grid-cols-2 gap-5 border-b border-slate-100 bg-slate-50/60 px-5 py-5 sm:grid-cols-3 sm:px-6">
    <ProductionMetric label={`Realisasi ${years[0]}`} value={current == null ? '—' : formatNumber(current)} unit={current == null ? undefined : unit} color={color} note={current == null ? 'Data tidak tersedia' : undefined} />
    <ProductionMetric label={`Realisasi ${years[1] ?? 'sebelumnya'}`} value={previous == null ? '—' : formatNumber(previous)} unit={previous == null ? undefined : unit} note={previous == null ? 'Data tidak tersedia' : undefined} />
    <div className="col-span-2 sm:col-span-1">
      <ProductionMetric label="Perubahan tahunan" value={comparison.growth == null ? '—' : `${comparison.growth > 0 ? '+' : ''}${formatNumber(Number(comparison.growth.toFixed(1)))}%`} note={comparison.reason} color={comparison.growth == null || comparison.growth === 0 ? undefined : comparison.growth > 0 ? '#047857' : '#be123c'} />
    </div>
  </div>;
}

const YoYProductionSlide = ({ source, years, stats, commonOptions }) => {
  const chronologicalYears = [...years].reverse();
  const productionFor = year => stats?.[year]?.bina_usaha?.[source.key];
  const groups = availableUnits(years.flatMap(year => productionFor(year)?.bukan_kayu_by_unit ?? []));
  const [preferredUnit, setPreferredUnit] = useState('kg');
  const group = selectedUnit(groups, preferredUnit);
  useEffect(() => { setPreferredUnit(group?.unit ?? 'kg'); }, [group?.unit]);
  const totalFor = year => totalForUnit(productionFor(year), group?.unit);
  const woodFor = year => productionFor(year)?.kayu_total ?? null;
  const unspecified = years.flatMap(year => (productionFor(year)?.bukan_kayu_unspecified ?? []).map(row => ({ ...row, year })));
  const labels = chronologicalYears.map(String);
  const period = `${chronologicalYears[0]}–${years[0]}`;

  return <div className="w-full min-w-full shrink-0 px-1 sm:px-4">
    <div className="mx-auto grid max-w-7xl items-start gap-5 xl:grid-cols-2">
      <ProductionPanel title="Hasil hutan kayu" code="HHK" subtitle={`Perbandingan produksi · ${period}`} color={source.color}>
        <YearComparison current={woodFor(years[0])} previous={woodFor(years[1])} years={years} unit="m³" color={source.color} />
        <div className="px-5 py-5 sm:px-6">
          <ProductionTrend labels={labels} values={chronologicalYears.map(woodFor)} unit="m³" color={source.color} commonOptions={commonOptions} title="Tren tahunan" />
        </div>
      </ProductionPanel>
      <ProductionPanel title="Hasil hutan bukan kayu" code="HHBK" subtitle={`Perbandingan produksi · ${period}`} color={NON_WOOD_COLOR}
        actions={group && <NonWoodUnitSelect groups={groups} value={group.unit} onChange={setPreferredUnit} />}>
        {group ? <>
          <YearComparison current={totalFor(years[0])} previous={totalFor(years[1])} years={years} unit={group.unit} color={NON_WOOD_COLOR} />
          <div className="px-5 py-5 sm:px-6">
            <ProductionTrend labels={labels} values={chronologicalYears.map(totalFor)} unit={group.unit} color={NON_WOOD_COLOR} commonOptions={commonOptions} title="Tren tahunan" />
          </div>
        </> : <ProductionEmpty>{unspecified.length ? 'Belum ada produksi dengan satuan spesifik.' : undefined}</ProductionEmpty>}
        <footer className="border-t border-slate-100 px-5 py-3 sm:px-6">
          <p className="text-xs leading-relaxed text-slate-500">Tahun tanpa catatan ditampilkan sebagai celah pada grafik.</p>
          <NonWoodUnspecified rows={unspecified} />
        </footer>
      </ProductionPanel>
    </div>
  </div>;
};

export default React.memo(YoYProductionSlide);
