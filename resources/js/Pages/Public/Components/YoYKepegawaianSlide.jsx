import React, { useMemo } from 'react';
import { Line, Bar } from 'react-chartjs-2';
import YoYStatsCard from './YoYStatsCard';
import { formatNumber } from './utils';

const YoYKepegawaianSlide = ({ years, stats, commonOptions }) => {
  const chronologicalYears = useMemo(() => [...years].reverse(), [years]);
  const color = '#4f46e5'; // Indigo-600

  // 1. Tren Total Pegawai
  const totalPegawaiTrend = useMemo(() => ({
    labels: chronologicalYears.map(y => `Thn ${y}`),
    datasets: [{
      label: 'Total Pegawai',
      data: chronologicalYears.map(y => stats[y]?.kepegawaian?.total_pegawai || 0),
      borderColor: color,
      backgroundColor: color + '20',
      fill: true,
      tension: 0.4,
      pointRadius: 4,
    }]
  }), [chronologicalYears, stats, color]);

  // 2. Tren Bezetting (Kebutuhan vs Eksisting)
  const bezettingTrend = useMemo(() => ({
    labels: chronologicalYears.map(y => `Thn ${y}`),
    datasets: [
      {
        label: 'Kebutuhan',
        data: chronologicalYears.map(y => stats[y]?.kepegawaian?.bezetting?.total_kebutuhan || 0),
        borderColor: '#94a3b8',
        backgroundColor: '#94a3b820',
        borderDash: [5, 5],
        fill: false,
        tension: 0.3,
      },
      {
        label: 'Eksisting',
        data: chronologicalYears.map(y => stats[y]?.kepegawaian?.bezetting?.total_eksisting || 0),
        borderColor: color,
        backgroundColor: color + '20',
        fill: false,
        tension: 0.3,
      }
    ]
  }), [chronologicalYears, stats, color]);

  // 3. Komposisi Status Pegawai YoY (Stacked Bar)
  const statusCompositionTrend = useMemo(() => {
    const allStatusTypes = new Set();
    chronologicalYears.forEach(y => {
      Object.keys(stats[y]?.kepegawaian?.status || {}).forEach(s => allStatusTypes.add(s));
    });

    const statusTypes = Array.from(allStatusTypes);
    const chartColors = ['#10b981', '#f59e0b', '#3b82f6', '#8b5cf6', '#ec4899'];

    return {
      labels: chronologicalYears.map(y => `Thn ${y}`),
      datasets: statusTypes.map((status, i) => ({
        label: status,
        data: chronologicalYears.map(y => stats[y]?.kepegawaian?.status?.[status] || 0),
        backgroundColor: chartColors[i % chartColors.length],
      }))
    };
  }, [chronologicalYears, stats]);

  const yearsCount = years.length;

  return (
    <div className="min-w-full px-4">
      <div className="max-w-7xl mx-auto space-y-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <div className="lg:col-span-1 space-y-6">
            <YoYStatsCard
              title="Total Pegawai"
              currentTotal={stats[years[0]]?.kepegawaian?.total_pegawai || 0}
              prevTotal={stats[years[1]]?.kepegawaian?.total_pegawai || 0}
              unit="Orang"
              color={color}
              currentYear={years[0]}
              prevYear={years[1]}
            />

            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <h4 className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Ringkasan Bezetting</h4>
              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-xs text-gray-500 font-medium">Kebutuhan</span>
                  <span className="text-sm font-bold text-gray-900">{formatNumber(stats[years[0]]?.kepegawaian?.bezetting?.total_kebutuhan || 0)}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-xs text-gray-500 font-medium">Eksisting</span>
                  <span className="text-sm font-bold text-indigo-600">{formatNumber(stats[years[0]]?.kepegawaian?.bezetting?.total_eksisting || 0)}</span>
                </div>
                <div className="pt-2 border-t border-gray-50">
                  <div className="flex justify-between items-center text-[10px] font-bold">
                    <span className="text-gray-400 uppercase">Fulfillment</span>
                    <span className="text-indigo-600">
                      {stats[years[0]]?.kepegawaian?.bezetting?.total_kebutuhan > 0
                        ? ((stats[years[0]].kepegawaian.bezetting.total_eksisting / stats[years[0]].kepegawaian.bezetting.total_kebutuhan) * 100).toFixed(1)
                        : 0}%
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="lg:col-span-3 space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Tren Pertumbuhan Pegawai ({yearsCount} Thn)</h4>
                <div className="h-[250px]">
                  <Line data={totalPegawaiTrend} options={{ ...commonOptions, maintainAspectRatio: false, plugins: { ...commonOptions.plugins, legend: { display: false } } }} />
                </div>
              </div>

              <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Analisis Bezetting YoY</h4>
                <div className="h-[250px]">
                  <Line data={bezettingTrend} options={{ ...commonOptions, maintainAspectRatio: false }} />
                </div>
              </div>
            </div>

            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Komposisi Status Pegawai</h4>
              <div className="h-[250px]">
                <Bar
                  data={statusCompositionTrend}
                  options={{
                    ...commonOptions,
                    maintainAspectRatio: false,
                    scales: {
                      ...commonOptions.scales,
                      x: { stacked: true },
                      y: { stacked: true, beginAtZero: true }
                    }
                  }}
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(YoYKepegawaianSlide);
