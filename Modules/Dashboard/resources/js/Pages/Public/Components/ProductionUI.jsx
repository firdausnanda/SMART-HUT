import React from 'react';
import { Line } from 'react-chartjs-2';
import { formatNumber } from './utils';

export const NON_WOOD_COLOR = '#b45309';
export const displayUnit = unit => unit === 'm3' ? 'm³' : unit;

export function ProductionPanel({ title, code, subtitle, color, children, actions }) {
  return <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <header className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
      <div className="flex items-center gap-3">
        <span className="h-10 w-1 rounded-full" style={{ backgroundColor: color }} aria-hidden="true" />
        <div>
          <h3 className="text-lg font-bold tracking-tight text-slate-900">{title} <span className="ml-1 text-xs font-semibold text-slate-400">{code}</span></h3>
          <p className="mt-0.5 text-xs text-slate-500">{subtitle}</p>
        </div>
      </div>
      {actions}
    </header>
    {children}
  </section>;
}

export function ProductionMetric({ label, value, unit, note, color }) {
  return <div className="min-w-0">
    <p className="text-xs font-medium text-slate-500">{label}</p>
    <p className="mt-1 flex flex-wrap items-baseline gap-x-1.5 text-2xl font-bold tracking-tight tabular-nums sm:text-3xl" style={{ color: color || '#0f172a' }}>
      <span className="break-all">{value}</span>
      {unit && <span className="text-sm font-medium text-slate-500">{displayUnit(unit)}</span>}
    </p>
    {note && <p className="mt-1 text-xs leading-relaxed text-slate-500">{note}</p>}
  </div>;
}

export function ProductionTrend({ labels, values, target, unit, color, commonOptions, label = 'Realisasi', title = 'Tren bulanan' }) {
  const datasets = [{
    label, data: values, borderColor: color, backgroundColor: color + '0d',
    borderWidth: 2, pointRadius: 2.5, pointHoverRadius: 5, fill: true, tension: 0.2, spanGaps: false,
  }];
  if (target) datasets.push({ label: 'Target', data: target, borderColor: '#94a3b8', borderDash: [5, 5], borderWidth: 1.5, pointRadius: 0, fill: false });
  const options = {
    ...commonOptions, responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      ...commonOptions?.plugins, legend: { display: false },
      tooltip: { callbacks: { label: ctx => ctx.raw == null ? 'Data tidak tersedia' : `${ctx.dataset.label}: ${formatNumber(ctx.raw)} ${displayUnit(unit)}` } },
    },
    scales: {
      x: { grid: { display: false }, border: { display: false }, ticks: { color: '#64748b', maxRotation: 0, autoSkip: true, autoSkipPadding: 14, font: { size: 11 } } },
      y: { beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', maxTicksLimit: 5, font: { size: 11 }, callback: value => new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(value) } },
    },
  };
  return <div className="min-w-0">
    <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
      <h4 className="text-sm font-semibold text-slate-800">{title} <span className="font-normal text-slate-400">· {displayUnit(unit)}</span></h4>
      <div className="flex items-center gap-3 text-xs text-slate-500">
        <span className="flex items-center gap-1.5"><span className="h-0.5 w-4 rounded" style={{ backgroundColor: color }} />{label}</span>
        {target && <span className="flex items-center gap-1.5"><span className="w-4 border-t-2 border-dashed border-slate-400" />Target</span>}
      </div>
    </div>
    <div className="h-[210px] sm:h-[230px]" role="img" aria-label={`${title} dalam ${displayUnit(unit)}`}>
      <Line data={{ labels, datasets }} options={options} />
    </div>
  </div>;
}

export function CommodityRanking({ items, unit, color }) {
  const max = Math.max(...items.map(item => Number(item.total)), 0);
  return <div className="min-w-0">
    <h4 className="text-sm font-semibold text-slate-800">Komoditas terbesar</h4>
    <p className="mt-1 text-xs text-slate-500">Hingga 5 komoditas · {displayUnit(unit)}</p>
    {items.length ? <ol className="mt-4 space-y-3.5">
      {items.map((item, index) => <li key={item.id ?? item.name} className="flex gap-3">
        <span className="w-4 pt-0.5 text-xs font-medium text-slate-400">{index + 1}</span>
        <div className="min-w-0 flex-1">
          <div className="mb-1.5 flex flex-wrap items-baseline justify-between gap-x-3 text-sm">
            <span className="break-words text-slate-700">{item.name}</span>
            <span className="font-semibold tabular-nums text-slate-900">{formatNumber(item.total)} <span className="text-xs font-normal text-slate-500">{displayUnit(unit)}</span></span>
          </div>
          <div className="h-1.5 overflow-hidden rounded-full bg-slate-100" aria-hidden="true"><div className="h-full rounded-full transition-all duration-300" style={{ width: `${max > 0 ? Number(item.total) / max * 100 : 0}%`, backgroundColor: color }} /></div>
        </div>
      </li>)}
    </ol> : <p className="py-8 text-sm text-slate-500">Belum ada data komoditas.</p>}
  </div>;
}

export function ProductionEmpty({ children = 'Belum ada data produksi bukan kayu' }) {
  return <div className="px-6 py-12 text-center"><p className="text-sm font-medium text-slate-600">{children}</p><p className="mt-1 text-xs text-slate-400">Coba pilih tahun atau CDK lain.</p></div>;
}
