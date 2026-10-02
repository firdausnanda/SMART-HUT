import React, { useMemo } from 'react';
import { Line, Bar } from 'react-chartjs-2';
import SummaryTotalCard from './SummaryTotalCard';

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
          {/* Left: Summary */}
          <div className="lg:col-span-1 min-w-0">
            <SummaryTotalCard
              title="Total PNBP"
              total={stats?.bina_usaha?.pnbp?.total_realization}
              prefix="Rp"
              color="#d97706"
              year={currentYear}
              secondary={{ label: 'Target tahunan', value: stats?.bina_usaha?.pnbp?.total_target, prefix: 'Rp' }}
            />
          </div>

          {/* Right: Charts Grid */}
          <div className="min-w-0 lg:col-span-3 space-y-6">
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
