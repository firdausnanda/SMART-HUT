import React, { useMemo } from 'react';
import { Line, Bar } from 'react-chartjs-2';
import { formatNumber, formatCurrency } from './utils';

const PnbpSlide = ({ stats, currentYear, commonOptions }) => {
  const trendChartData = useMemo(() => {
    const months = Array.from({ length: 12 }, (_, i) => i + 1);
    const labels = months.map(m => {
      const date = new Date();
      date.setMonth(m - 1);
      return date.toLocaleString('id-ID', { month: 'short' });
    });

    return {
      labels,
      datasets: [
        {
          label: 'Realisasi',
          data: months.map(m => stats?.bina_usaha?.pnbp?.monthly?.[m]?.realization || 0),
          borderColor: '#d97706',
          backgroundColor: '#d9770620',
          fill: true,
          tension: 0.4,
        },
        {
          label: 'Target',
          data: months.map(m => stats?.bina_usaha?.pnbp?.monthly?.[m]?.target || 0),
          borderColor: '#cbd5e1',
          borderDash: [5, 5],
          tension: 0.4,
        }
      ]
    };
  }, [stats?.bina_usaha?.pnbp?.monthly]);

  const regencyChartData = useMemo(() => {
    return {
      labels: stats?.bina_usaha?.pnbp?.by_regency ? Object.keys(stats.bina_usaha.pnbp.by_regency) : [],
      datasets: [{
        label: 'Realisasi (Rp)',
        data: stats?.bina_usaha?.pnbp?.by_regency ? Object.values(stats.bina_usaha.pnbp.by_regency) : [],
        backgroundColor: '#d97706CC',
        borderRadius: 6
      }]
    };
  }, [stats?.bina_usaha?.pnbp?.by_regency]);

  const pengelolaChartData = useMemo(() => {
    return {
      labels: stats?.bina_usaha?.pnbp?.by_pengelola ? Object.keys(stats.bina_usaha.pnbp.by_pengelola) : [],
      datasets: [{
        label: 'Realisasi (Rp)',
        data: stats?.bina_usaha?.pnbp?.by_pengelola ? Object.values(stats.bina_usaha.pnbp.by_pengelola) : [],
        backgroundColor: '#d33c06CC',
        borderRadius: 6
      }]
    };
  }, [stats?.bina_usaha?.pnbp?.by_pengelola]);

  const trendOptions = useMemo(() => ({
    ...commonOptions,
    maintainAspectRatio: false
  }), [commonOptions]);

  const barOptions = useMemo(() => ({
    ...commonOptions,
    indexAxis: 'y',
    maintainAspectRatio: false,
    plugins: { ...commonOptions.plugins, legend: { display: false } },
    scales: {
      y: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' } } },
      x: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } }
    }
  }), [commonOptions]);

  const pengelolaBarOptions = useMemo(() => ({
    ...commonOptions,
    indexAxis: 'y',
    maintainAspectRatio: false,
    plugins: { ...commonOptions.plugins, legend: { display: false } },
    scales: {
      y: { grid: { display: false }, ticks: { font: { size: 9, weight: 'bold' } } },
      x: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } }
    }
  }), [commonOptions]);

  return (
    <div className="min-w-full px-4">
      <div className="max-w-7xl mx-auto space-y-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          {/* Left: Professional Stats Card */}
          <div className="lg:col-span-1 flex flex-col gap-6">
            <div className="relative group">
              <div className="absolute -inset-1 bg-gradient-to-b from-white to-gray-50 rounded-[2rem] blur-xl opacity-20 group-hover:opacity-40 transition-opacity"></div>
              <div className="relative bg-white p-8 rounded-[2rem] shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col overflow-hidden">
                {/* Decorative Circle */}
                <div className="absolute -right-10 -top-10 w-32 h-32 rounded-full opacity-[0.03] pointer-events-none bg-amber-600"></div>

                <div className="flex flex-col items-center justify-between relative z-10">
                  <div className="text-center w-full">
                    <div className={`w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg transform group-hover:scale-110 transition-transform duration-500`} style={{ background: `linear-gradient(135deg, #d33c06, #d33c06DD)`, color: 'white' }}>
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <span className="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 block mb-2">Total PNBP</span>
                    <div className="flex flex-col gap-1 items-center justify-center">
                      <h3 className="text-4xl font-black text-gray-900 leading-none tabular-nums tracking-tighter">
                        {formatCurrency(stats?.bina_usaha?.pnbp?.total_realization || 0)}
                      </h3>
                    </div>
                  </div>

                  <div className="w-full mt-8 pt-8 border-t border-gray-50">
                    <div className="flex justify-between items-end mb-3">
                      <div className="flex flex-col">
                        <span className="text-[10px] uppercase font-bold text-gray-400 tracking-widest">Target Tahunan</span>
                        <span className="text-xl font-black text-gray-500">{formatCurrency(stats?.bina_usaha?.pnbp?.total_target || 0)}</span>
                      </div>
                    </div>
                    <p className="text-[9px] text-gray-400 mt-4 uppercase font-black text-center tracking-[0.2em]">Penerimaan Negara {currentYear}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Right: Charts Grid */}
          <div className="md:col-span-3 space-y-6">
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-4">Tren Penerimaan Bulanan</h4>
              <div className="h-[250px]">
                <Line
                  data={trendChartData}
                  options={trendOptions}
                />
              </div>
            </div>
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-4">Realisasi per Kabupaten</h4>
              <div className="h-[150px]">
                <Bar
                  data={regencyChartData}
                  options={barOptions}
                />
              </div>
            </div>
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-4">Realisasi per Pengelola</h4>
              <div className="h-[300px]">
                <Bar
                  data={pengelolaChartData}
                  options={pengelolaBarOptions}
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(PnbpSlide);
