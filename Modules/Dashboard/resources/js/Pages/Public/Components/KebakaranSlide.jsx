import React, { useMemo } from 'react';
import { Line, Bar } from 'react-chartjs-2';
import SummaryTotalCard from './SummaryTotalCard';

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
          {/* Left: Summary */}
          <div className="lg:col-span-1 min-w-0">
            <SummaryTotalCard
              title="Total kejadian"
              total={stats?.perlindungan?.kebakaran_kejadian}
              unit="Kejadian"
              color="#dc2626"
              year={currentYear}
              secondary={{ label: 'Luas area terdampak', value: stats?.perlindungan?.kebakaran_area, unit: 'Ha' }}
            />
          </div>

          {/* Right: Charts Grid */}
          <div className="min-w-0 lg:col-span-3 space-y-6">
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
