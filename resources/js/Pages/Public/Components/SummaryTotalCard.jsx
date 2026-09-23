import React from 'react';
import { formatNumber } from './utils';

/** Summary for the narrow left column of public dashboard slides. */
const SummaryTotalCard = ({ title, total, unit, color, year, prefix, secondary, progress }) => {
  const hasProgress = secondary?.value != null && Number(secondary.value) > 0 && Number.isFinite(progress);
  const progressWidth = hasProgress ? Math.min(Math.max(progress, 0), 100) : 0;

  return <section className="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div className="h-1" style={{ backgroundColor: color }} aria-hidden="true" />
    <div className="px-5 pb-5 pt-5 sm:px-6">
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0">
          <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Ringkasan</p>
          <h3 className="mt-1 text-sm font-semibold leading-snug text-slate-800">{title}</h3>
        </div>
        {year && <span className="shrink-0 rounded-md bg-slate-100 px-2 py-1 text-xs font-medium tabular-nums text-slate-600">{year}</span>}
      </div>

      <div className="mt-7 min-w-0">
        <p className="text-xs text-slate-500">Total tercatat</p>
        <div className="mt-1 flex min-w-0 flex-wrap items-baseline gap-x-1.5">
          {prefix && <span className="text-base font-semibold text-slate-500">{prefix}</span>}
          <strong className="min-w-0 break-all text-[clamp(1.45rem,2.2vw,2.25rem)] font-bold leading-tight tracking-tight tabular-nums" style={{ color }}>
            {formatNumber(total ?? 0)}
          </strong>
        </div>
        {unit && <p className="mt-1 text-sm font-medium text-slate-600">{unit}</p>}
      </div>

      {secondary && <div className="mt-6 border-t border-slate-100 pt-5">
        <p className="text-xs text-slate-500">{secondary.label}</p>
        <p className="mt-1 flex min-w-0 flex-wrap items-baseline gap-x-1.5 break-all text-lg font-semibold tabular-nums text-slate-800">
          {secondary.prefix && <span className="text-sm font-medium text-slate-500">{secondary.prefix}</span>}
          {formatNumber(secondary.value ?? 0)}
          {secondary.unit && <span className="text-xs font-medium text-slate-500">{secondary.unit}</span>}
        </p>
        {hasProgress && <div className="mt-4">
          <div className="mb-2 flex items-baseline justify-between gap-2 text-xs">
            <span className="text-slate-500">Capaian target</span>
            <span className="font-semibold tabular-nums text-slate-800">{formatNumber(Number(progress.toFixed(1)))}%</span>
          </div>
          <div className="h-1.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="Capaian target" aria-valuemin="0" aria-valuemax="100" aria-valuenow={Math.round(progressWidth)}>
            <div className="h-full rounded-full transition-[width] duration-500" style={{ width: `${progressWidth}%`, backgroundColor: color }} />
          </div>
        </div>}
      </div>}
    </div>
  </section>;
};

export default React.memo(SummaryTotalCard);
