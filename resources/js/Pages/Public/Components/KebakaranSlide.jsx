import React, { useMemo } from 'react';
import { Line, Bar } from 'react-chartjs-2';
import { formatNumber } from './utils';
import StatsCard from './StatsCard'; // Optionally use StatsCard if applicable, but this slide has a custom card layout.

const KebakaranSlide = ({ stats, currentYear, commonOptions }) => {
  const incidentData = useMemo(() => {
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
          label: 'Jumlah Kejadian',
          data: months.map(m => stats?.perlindungan?.kebakaranMonthly?.[m]?.incidents || 0),
          borderColor: '#dc2626',
          backgroundColor: '#dc262620',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          yAxisID: 'y',
        },
        {
          label: 'Luas Area (Ha)',
          data: months.map(m => stats?.perlindungan?.kebakaranMonthly?.[m]?.area || 0),
          borderColor: '#f9f111ff',
          backgroundColor: '#fb923c20',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          borderDash: [5, 5],
          yAxisID: 'y1',
        }
      ]
    };
  }, [stats?.perlindungan?.kebakaranMonthly]);

  const pengelolaData = useMemo(() => {
    return {
      labels: stats?.perlindungan?.kebakaranByPengelola ? Object.keys(stats.perlindungan.kebakaranByPengelola) : [],
      datasets: [{
        label: 'Luas Area (Ha)',
        data: stats?.perlindungan?.kebakaranByPengelola ? Object.values(stats.perlindungan.kebakaranByPengelola).map(d => d.area) : [],
        backgroundColor: '#dc2626CC',
        borderRadius: 6
      }]
    };
  }, [stats?.perlindungan?.kebakaranByPengelola]);

  const multiAxisOptions = useMemo(() => ({
    ...commonOptions,
    maintainAspectRatio: false,
    plugins: { ...commonOptions.plugins, legend: { display: false } },
    scales: {
      y: {
        type: 'linear',
        display: true,
        position: 'left',
        beginAtZero: true,
        min: 0,
        title: { display: true, text: 'Kejadian', font: { size: 10, weight: 'bold' } },
        grid: { display: false }
      },
      y1: {
        type: 'linear',
        display: true,
        position: 'right',
        beginAtZero: true,
        min: 0,
        title: { display: true, text: 'Luas (Ha)', font: { size: 10, weight: 'bold' } },
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
          {/* Left: Professional Stats Card (Custom for Kebakaran) */}
          <div className="lg:col-span-1 flex flex-col gap-6">
            <div className="relative group">
              <div className="absolute -inset-1 bg-gradient-to-b from-white to-gray-50 rounded-[2rem] blur-xl opacity-20 group-hover:opacity-40 transition-opacity"></div>
              <div className="relative bg-white p-8 rounded-[2rem] shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col overflow-hidden">
                {/* Decorative Circle */}
                <div className="absolute -right-10 -top-10 w-32 h-32 rounded-full opacity-[0.03] pointer-events-none bg-red-600"></div>

                <div className="flex flex-col items-center justify-between relative z-10">
                  <div className="text-center w-full">
                    <div className={`w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg transform group-hover:scale-110 transition-transform duration-500`} style={{ background: `linear-gradient(135deg, #dc2626, #dc2626DD)`, color: 'white' }}>
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.99 7.99 0 0120 13a7.99 7.99 0 01-2.343 5.657z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.879 16.121A3 3 0 1012.015 11L11 14l.879 2.121z" />
                      </svg>
                    </div>
                    <span className="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 block mb-2">Total Kejadian</span>
                    <div className="flex flex-col gap-1 items-center justify-center">
                      <h3 className="text-6xl font-black text-gray-900 leading-none tabular-nums tracking-tighter">
                        {formatNumber(stats?.perlindungan?.kebakaran_kejadian || 0)}
                      </h3>
                      <span className="text-xl font-bold text-gray-400" style={{ letterSpacing: '-0.02em' }}>Kejadian</span>
                    </div>
                  </div>

                  <div className="w-full mt-8 pt-8 border-t border-gray-50">
                    <div className="flex justify-between items-end mb-3">
                      <div className="flex flex-col">
                        <span className="text-[10px] uppercase font-bold text-gray-400 tracking-widest">Luas Area</span>
                        <span className="text-2xl font-black text-gray-900">{formatNumber(stats?.perlindungan?.kebakaran_area || 0)}</span>
                      </div>
                      <span className="text-[10px] font-bold text-gray-400 uppercase tracking-tighter mb-1">Hektar (Ha)</span>
                    </div>
                    <div className="h-3 w-full bg-gray-100 rounded-full overflow-hidden shadow-inner p-0.5">
                      <div
                        className="h-full rounded-full transition-all duration-1000 ease-out shadow-sm relative overflow-hidden bg-red-500"
                        style={{ width: `100%` }}
                      >
                        <div className="absolute inset-0 bg-[linear-gradient(45deg,rgba(255,255,255,0.2)_25%,transparent_25%,transparent_50%,rgba(255,255,255,0.2)_50%,rgba(255,255,255,0.2)_75%,transparent_75%,transparent)] bg-[length:20px_20px] animate-[shimmer_2s_linear_infinite]"></div>
                      </div>
                    </div>
                    <p className="text-[9px] text-gray-400 mt-4 uppercase font-black text-center tracking-[0.2em]">Monitoring Karhutla {currentYear}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Right: Charts Grid */}
          <div className="md:col-span-3 space-y-6">
            {/* Trend Chart: Luas vs Kejadian */}
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <div className="flex items-center justify-between mb-4">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider">Tren Luas Area & Jumlah Kejadian</h4>
                <div className="flex gap-4">
                  <span className="flex items-center gap-1.5 text-[8px] font-bold text-red-500 uppercase">
                    <span className="w-2 h-2 rounded-full bg-red-500"></span> Kejadian
                  </span>
                  <span className="flex items-center gap-1.5 text-[8px] font-bold text-orange-400 uppercase">
                    <span className="w-2 h-2 rounded-full bg-orange-400"></span> Luas Area
                  </span>
                </div>
              </div>
              <div className="h-[200px]">
                <Line
                  data={incidentData}
                  options={multiAxisOptions}
                />
              </div>
            </div>

            {/* Pengelola Bar Chart */}
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <div className="flex items-center justify-between mb-4">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider">Luas Kebakaran Berdasarkan Pengelola</h4>
                <span className="px-2 py-1 rounded-full bg-red-50 text-[8px] font-bold text-red-500 uppercase">Pengelola</span>
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

export default React.memo(KebakaranSlide);
