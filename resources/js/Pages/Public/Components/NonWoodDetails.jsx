import React from 'react';
import { formatNumber } from './utils';
import { displayUnit } from './ProductionUI';

export function NonWoodUnitSelect({ groups, value, onChange }) {
  return <label className="flex items-center gap-2 text-xs font-medium text-slate-500">
    Satuan
    <select aria-label="Satuan HHBK" className="max-w-full rounded-lg border-slate-200 bg-slate-50 py-2 text-sm font-semibold text-slate-800 focus:border-amber-600 focus:ring-amber-600" value={value} onChange={event => onChange(event.target.value)}>
      {groups.map(group => <option key={group.unit} value={group.unit}>{group.label}</option>)}
    </select>
  </label>;
}

export function NonWoodUnitCards({ groups, value, onChange }) {
  return <div role="group" aria-label="Satuan produksi bukan kayu" className="flex flex-wrap gap-2.5">
    {groups.map(group => <button key={group.unit} type="button" aria-pressed={value === group.unit} onClick={() => onChange(group.unit)}
      className={`min-w-[130px] flex-1 rounded-xl border px-4 py-3 text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-600 focus-visible:ring-offset-2 sm:flex-none ${value === group.unit ? 'border-amber-600 bg-white shadow-sm' : 'border-transparent bg-slate-100/70 hover:border-slate-300 hover:bg-white'}`}>
      <span className={`flex items-center justify-between gap-4 text-xs font-medium ${value === group.unit ? 'text-amber-800' : 'text-slate-500'}`}>
        {group.label}<span className={`h-2 w-2 rounded-full ${value === group.unit ? 'bg-amber-600' : 'bg-slate-300'}`} aria-hidden="true" />
      </span>
      <span className="mt-1 block text-xl font-bold tabular-nums tracking-tight text-slate-900">{formatNumber(group.total)} <span className="text-xs font-normal text-slate-500">{displayUnit(group.unit)}</span></span>
    </button>)}
  </div>;
}

export function NonWoodUnspecified({ rows }) {
  if (!rows?.length) return null;
  return <details className="mt-3 rounded-lg border border-slate-200 bg-slate-50 text-sm">
    <summary className="cursor-pointer px-4 py-3 font-medium text-slate-600 focus-visible:outline-amber-600">Satuan belum spesifik <span className="ml-1 text-xs text-slate-400">({rows.length} rincian)</span></summary>
    <div className="border-t border-slate-200 px-4 py-3">
      <p className="mb-3 text-xs text-slate-500">Rincian berikut tidak digabungkan dalam total atau grafik.</p>
      <ul className="max-h-60 space-y-2 overflow-y-auto">
        {rows.map((row, index) => <li key={`${row.year ?? ''}-${row.id}-${row.unit}-${index}`} className="flex flex-wrap justify-between gap-x-4 text-slate-700">
          <span>{row.year ? `${row.year} · ` : ''}{row.name}</span>
          <span className="font-semibold tabular-nums">{formatNumber(row.total)} <span className="whitespace-pre-wrap font-normal text-slate-500">{row.unit?.trim() ? row.unit : '(satuan kosong)'}</span></span>
        </li>)}
      </ul>
    </div>
  </details>;
}
