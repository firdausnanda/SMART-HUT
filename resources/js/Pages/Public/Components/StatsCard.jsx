import React from 'react';
import { formatNumber } from './utils';

const StatsCard = ({ title, total, unit, progress, target, color, year }) => {
  return (
    <div className="relative group">
      <div className="absolute -inset-1 bg-gradient-to-b from-white to-gray-50 rounded-[2rem] blur-xl opacity-20 group-hover:opacity-40 transition-opacity"></div>
      <div className="relative bg-white p-8 rounded-[2rem] shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col overflow-hidden">
        {/* Decorative Circle */}
        <div className="absolute -right-10 -top-10 w-32 h-32 rounded-full opacity-[0.03] pointer-events-none" style={{ backgroundColor: color }}></div>

        <div className="flex flex-col items-center justify-between h-full relative z-10">
          <div className="text-center w-full">
            <div className={`w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg transform group-hover:scale-110 transition-transform duration-500`} style={{ background: `linear-gradient(135deg, ${color}, ${color}DD)`, color: 'white' }}>
              <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                {/* Default Icon (can be customized via props if needed, but keeping generic for now or this specific icon from original code) */}
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
            <span className="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 block mb-2">{title || 'Total Realisasi'}</span>
            <div className="flex flex-col gap-1 items-center justify-center">
              <h3 className="text-6xl font-black text-gray-900 leading-none tabular-nums tracking-tighter">
                {formatNumber(total || 0)}
              </h3>
              <span className="text-xl font-bold text-gray-400" style={{ letterSpacing: '-0.02em' }}>{unit}</span>
            </div>
          </div>

          <div className="w-full mt-8 pt-8 border-t border-gray-50">
            {target !== undefined && (
              <>
                <div className="flex justify-between items-end mb-3">
                  <div className="flex flex-col">
                    <span className="text-[10px] uppercase font-bold text-gray-400 tracking-widest">Progress</span>
                    <span className="text-2xl font-black text-gray-900">{progress?.toFixed(1)}%</span>
                  </div>
                  <span className="text-[10px] font-bold text-gray-400 uppercase tracking-tighter mb-1">Target: {formatNumber(target || 0)}</span>
                </div>
                <div className="h-3 w-full bg-gray-100 rounded-full overflow-hidden shadow-inner p-0.5">
                  <div
                    className="h-full rounded-full transition-all duration-1000 ease-out shadow-sm relative overflow-hidden"
                    style={{ width: `${Math.min(progress, 100)}%`, backgroundColor: color }}
                  >
                    <div className="absolute inset-0 bg-[linear-gradient(45deg,rgba(255,255,255,0.2)_25%,transparent_25%,transparent_50%,rgba(255,255,255,0.2)_50%,rgba(255,255,255,0.2)_75%,transparent_75%,transparent)] bg-[length:20px_20px] animate-[shimmer_2s_linear_infinite]"></div>
                  </div>
                </div>
              </>
            )}
            {/* If no target/progress, maybe render something else or nothing - adapting to original usage */}

            <p className="text-[9px] text-gray-400 mt-4 uppercase font-black text-center tracking-[0.2em]">Data Terverifikasi {year}</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(StatsCard);
