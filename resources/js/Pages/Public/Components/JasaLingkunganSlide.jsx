import React, { useMemo } from 'react';
import { Line, Bar } from 'react-chartjs-2';
import { formatNumber, formatCurrency } from './utils';

const JasaLingkunganSlide = ({ stats, currentYear, commonOptions }) => {
  const trendData = useMemo(() => {
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
          label: 'Jumlah Pengunjung',
          data: months.map(m => stats?.perlindungan?.wisataMonthly?.[m]?.visitors || 0),
          borderColor: '#4f46e5',
          backgroundColor: '#4f46e520',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          yAxisID: 'y',
        },
        {
          label: 'Pendapatan (Rp)',
          data: months.map(m => stats?.perlindungan?.wisataMonthly?.[m]?.income || 0),
          borderColor: '#10b981',
          backgroundColor: '#10b98120',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          borderDash: [5, 5],
          yAxisID: 'y1',
        }
      ]
    };
  }, [stats?.perlindungan?.wisataMonthly]);

  const pengelolaData = useMemo(() => {
    return {
      labels: stats?.perlindungan?.wisataByPengelola ? Object.keys(stats.perlindungan.wisataByPengelola) : [],
      datasets: [{
        label: 'Jumlah Pengunjung',
        data: stats?.perlindungan?.wisataByPengelola ? Object.values(stats.perlindungan.wisataByPengelola).map(d => d.visitors) : [],
        backgroundColor: '#4f46e5CC',
        borderRadius: 6
      }]
    };
  }, [stats?.perlindungan?.wisataByPengelola]);

  const multiAxisOptions = useMemo(() => ({
    ...commonOptions,
    maintainAspectRatio: false,
    plugins: { ...commonOptions.plugins, legend: { display: false } },
    scales: {
      y: {
        type: 'linear',
        display: true,
        position: 'left',
        title: { display: true, text: 'Pengunjung', font: { size: 10, weight: 'bold' } },
        grid: { display: false }
      },
      y1: {
        type: 'linear',
        display: true,
        position: 'right',
        title: { display: true, text: 'Pendapatan', font: { size: 10, weight: 'bold' } },
        grid: { drawOnChartArea: false }
      },
      x: { grid: { display: false } }
    }
  }), [commonOptions]);

  const pengelolaOptions = useMemo(() => ({
    ...commonOptions,
    indexAxis: 'y',
    maintainAspectRatio: false,
    plugins: { ...commonOptions.plugins, legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { display: true } },
      y: {
        grid: { display: false },
        ticks: {
          autoSkip: false,
          font: { size: 9, weight: 'bold' }
        }
      }
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
                <div className="absolute -right-10 -top-10 w-32 h-32 rounded-full opacity-[0.03] pointer-events-none bg-indigo-600"></div>

                <div className="flex flex-col items-center justify-between relative z-10">
                  <div className="text-center w-full">
                    <div className={`w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg transform group-hover:scale-110 transition-transform duration-500`} style={{ background: `linear-gradient(135deg, #4f46e5, #4f46e5DD)`, color: 'white' }}>
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                    </div>
                    <span className="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 block mb-2">Total Pengunjung</span>
                    <div className="flex flex-col gap-1 items-center justify-center">
                      <h3 className="text-6xl font-black text-gray-900 leading-none tabular-nums tracking-tighter">
                        {formatNumber(stats?.perlindungan?.wisata_visitors || 0)}
                      </h3>
                      <span className="text-xl font-bold text-gray-400" style={{ letterSpacing: '-0.02em' }}>Orang</span>
                    </div>
                  </div>

                  <div className="w-full mt-8 pt-8 border-t border-gray-50">
                    <div className="flex justify-between items-end mb-3">
                      <div className="flex flex-col">
                        <span className="text-[10px] uppercase font-bold text-gray-400 tracking-widest">Pendapatan</span>
                        <span className="text-2xl font-black text-gray-900">{formatCurrency(stats?.perlindungan?.wisata_income || 0)}</span>
                      </div>
                    </div>
                    <div className="h-3 w-full bg-gray-100 rounded-full overflow-hidden shadow-inner p-0.5">
                      <div
                        className="h-full rounded-full transition-all duration-1000 ease-out shadow-sm relative overflow-hidden bg-indigo-500"
                        style={{ width: `100%` }}
                      >
                        <div className="absolute inset-0 bg-[linear-gradient(45deg,rgba(255,255,255,0.2)_25%,transparent_25%,transparent_50%,rgba(255,255,255,0.2)_50%,rgba(255,255,255,0.2)_75%,transparent_75%,transparent)] bg-[length:20px_20px] animate-[shimmer_2s_linear_infinite]"></div>
                      </div>
                    </div>
                    <p className="text-[9px] text-gray-400 mt-4 uppercase font-black text-center tracking-[0.2em]">Jasa Lingkungan {currentYear}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Right: Charts Grid */}
          <div className="md:col-span-3 space-y-6">
            {/* Trend Chart: Pengunjung vs Pendapatan */}
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <div className="flex items-center justify-between mb-4">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider">Tren Pengunjung & Pendapatan</h4>
                <div className="flex gap-4">
                  <span className="flex items-center gap-1.5 text-[8px] font-bold text-indigo-500 uppercase">
                    <span className="w-2 h-2 rounded-full bg-indigo-500"></span> Pengunjung
                  </span>
                  <span className="flex items-center gap-1.5 text-[8px] font-bold text-emerald-400 uppercase">
                    <span className="w-2 h-2 rounded-full bg-emerald-400"></span> Pendapatan
                  </span>
                </div>
              </div>
              <div className="h-[200px]">
                <Line
                  data={trendData}
                  options={multiAxisOptions}
                />
              </div>
            </div>

            {/* Pengelola Bar Chart */}
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <div className="flex items-center justify-between mb-4">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider">Jumlah Pengunjung Berdasarkan Pengelola</h4>
                <span className="px-2 py-1 rounded-full bg-indigo-50 text-[8px] font-bold text-indigo-500 uppercase">Pengelola</span>
              </div>
              <div className="h-[400px]">
                <Bar
                  data={pengelolaData}
                  options={pengelolaOptions}
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(JasaLingkunganSlide);
