import React, { useMemo } from 'react';
import { Line, Bar } from 'react-chartjs-2';
import SummaryTotalCard from './SummaryTotalCard';

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
        beginAtZero: true,
        min: 0,
        title: { display: true, text: 'Pengunjung', font: { size: 10, weight: 'bold' } },
        grid: { display: false }
      },
      y1: {
        type: 'linear',
        display: true,
        position: 'right',
        beginAtZero: true,
        min: 0,
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
          {/* Left: Summary */}
          <div className="lg:col-span-1 min-w-0">
            <SummaryTotalCard
              title="Total pengunjung"
              total={stats?.perlindungan?.wisata_visitors}
              unit="Orang"
              color="#4f46e5"
              year={currentYear}
              secondary={{ label: 'Pendapatan', value: stats?.perlindungan?.wisata_income, prefix: 'Rp' }}
            />
          </div>

          {/* Right: Charts Grid */}
          <div className="min-w-0 lg:col-span-3 space-y-6">
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
