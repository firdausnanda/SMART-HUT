import React from 'react';
import { formatNumber } from './utils';

const YoYSummaryCard = ({ title, currentTotal, prevTotal, unit, color, currentYear, prevYear }) => {
  const current = Number(currentTotal ?? 0);
  const previous = Number(prevTotal ?? 0);
  const growth = previous > 0 ? ((current - previous) / previous) * 100 : null;
  const growthColor = growth == null || growth === 0 ? '#475569' : growth > 0 ? '#047857' : '#be123c';
  const isCurrency = unit === 'Rp';

  return <section className="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div className="h-1" style={{ backgroundColor: color }} aria-hidden="true" />
    <div className="px-5 pb-5 pt-5 sm:px-6">
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0">
          <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Perbandingan tahunan</p>
          <h3 className="mt-1 text-sm font-semibold leading-snug text-slate-800">{title}</h3>
        </div>
        <span className="shrink-0 rounded-md bg-slate-100 px-2 py-1 text-xs font-medium tabular-nums text-slate-600">{currentYear}</span>
      </div>

      <div className="mt-7 min-w-0">
        <p className="text-xs text-slate-500">Realisasi {currentYear}</p>
        <div className="mt-1 flex min-w-0 flex-wrap items-baseline gap-x-1.5">
          {isCurrency && <span className="text-base font-semibold text-slate-500">Rp</span>}
          <strong className="min-w-0 break-all text-[clamp(1.45rem,2.2vw,2.25rem)] font-bold leading-tight tracking-tight tabular-nums" style={{ color }}>
            {formatNumber(current)}
          </strong>
        </div>
        {!isCurrency && <p className="mt-1 text-sm font-medium text-slate-600">{unit}</p>}
      </div>

      <div className="mt-6 border-t border-slate-100 pt-5">
        <p className="text-xs text-slate-500">Tahun sebelumnya ({prevYear})</p>
        <p className="mt-1 flex min-w-0 flex-wrap items-baseline gap-x-1.5 break-all text-lg font-semibold tabular-nums text-slate-800">
          {isCurrency && <span className="text-sm font-medium text-slate-500">Rp</span>}
          {formatNumber(previous)}
          {!isCurrency && <span className="text-xs font-medium text-slate-500">{unit}</span>}
        </p>

        <div className="mt-5 flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1 border-t border-slate-100 pt-4">
          <span className="text-xs text-slate-500">Perubahan tahunan</span>
          <strong className="text-lg font-semibold tabular-nums" style={{ color: growthColor }}>
            {growth == null ? '—' : `${growth > 0 ? '+' : ''}${formatNumber(Number(growth.toFixed(1)))}%`}
          </strong>
        </div>
        {growth == null && <p className="mt-1 text-xs text-slate-500">Pembanding tahun sebelumnya nol.</p>}
      </div>
    </div>
  </section>;
};

export default React.memo(YoYSummaryCard);
