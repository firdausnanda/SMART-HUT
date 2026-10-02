import React from 'react';
import { formatNumber } from './utils';

const YoYStatsCard = ({ title, currentTotal, prevTotal, unit, color, currentYear, prevYear }) => {
  const diff = currentTotal - prevTotal;
  const growth = prevTotal > 0 ? (diff / prevTotal) * 100 : (currentTotal > 0 ? 100 : 0);
  const isPositive = diff > 0;
  const isNeutral = diff === 0;

  return (
    <div className="relative group">

      <div className="relative bg-white/80 backdrop-blur-xl p-7 rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-white/60 flex flex-col h-full overflow-hidden transition-all duration-500 group-hover:-translate-y-1">
        {/* Subtle Gradient Background */}
        <div
          className="absolute inset-0 opacity-[0.03] pointer-events-none"
          style={{ background: `radial-gradient(circle at top right, ${color}, transparent)` }}
        ></div>

        <div className="flex flex-col justify-between h-full relative z-10">
          <div>
            {/* Header */}
            <div className="flex items-center gap-4 mb-8">
              <div
                className="w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg shadow-black/5 rotate-3 group-hover:rotate-0 transition-transform duration-500"
                style={{ background: `linear-gradient(135deg, ${color}, ${color}CC)`, color: 'white' }}
              >
                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
              </div>
              <div className="flex flex-col">
                <span className="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-0.5">Modul Statistik</span>
                <h4 className="text-sm font-bold text-gray-800 leading-tight">{title}</h4>
              </div>
            </div>

            {/* Main Stats */}
            <div className="space-y-6">
              <div className="relative pl-4 border-l-2" style={{ borderColor: color }}>
                <span className="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Capaian {currentYear}</span>
                <div className="flex items-baseline flex-wrap gap-x-2 gap-y-1">
                  {unit == 'Rp' && <span className="text-xs sm:text-sm font-black text-gray-400">{unit}. </span>}
                  <h3 className="text-xl sm:text-2xl lg:text-3xl font-black text-gray-900 tabular-nums tracking-tight leading-none break-all">
                    {formatNumber(currentTotal || 0)}
                  </h3>
                  {unit != 'Rp' && <span className="text-xs sm:text-sm font-black text-gray-400">{unit}</span>}
                </div>
              </div>

              <div className="p-4 rounded-2xl bg-gray-50/50 border border-gray-100/50">
                <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Tahun Sebelumnya ({prevYear})</span>
                <div className="flex items-baseline justify-between flex-wrap gap-2">
                  <div className="flex items-baseline flex-wrap gap-x-2 gap-y-0.5">
                    {unit == 'Rp' && <span className="text-[10px] sm:text-xs font-bold text-gray-400">{unit}. </span>}
                    <span className="text-lg sm:text-xl font-bold text-gray-600 tabular-nums break-all">
                      {formatNumber(prevTotal || 0)}
                    </span>
                    {unit != 'Rp' && <span className="text-[10px] sm:text-xs font-bold text-gray-400">{unit}</span>}
                  </div>
                  {/* Mini comparison tag */}
                  <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${isNeutral ? 'bg-gray-100 text-gray-600' : isPositive ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}`}>
                    {isNeutral ? '=' : isPositive ? '↑' : '↓'}
                  </span>
                </div>
              </div>
            </div>
          </div>

          {/* Footer Integration */}
          <div className="mt-10 pt-6 border-t border-gray-100/80">
            <div className="flex items-center justify-between bg-white/50 p-2 rounded-2xl">
              <div className="flex flex-col pl-2">
                <span className="text-[9px] uppercase font-black text-gray-400 tracking-[0.15em]">Pertumbuhan Tahunan</span>
                <div className={`flex items-center gap-1.5 mt-0.5 ${isNeutral ? 'text-gray-500' : isPositive ? 'text-emerald-600' : 'text-red-600'}`}>
                  <span className="text-2xl font-black tracking-tighter leading-none">
                    {growth > 0 ? '+' : ''}{growth.toFixed(1)}<span className="text-sm ml-0.5">%</span>
                  </span>
                </div>
              </div>

              <div className={`w-10 h-10 rounded-xl flex items-center justify-center shadow-sm transition-all duration-300 ${isNeutral
                ? 'bg-gray-500 text-white shadow-gray-200'
                : isPositive
                  ? 'bg-emerald-600 text-white shadow-emerald-200'
                  : 'bg-red-600 text-white shadow-red-200'
                }`}>
                {isNeutral ? (
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 12h14" />
                  </svg>
                ) : isPositive ? (
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                ) : (
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                  </svg>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(YoYStatsCard);
