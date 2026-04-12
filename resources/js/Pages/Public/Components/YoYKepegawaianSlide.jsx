import React, { useMemo } from 'react';
import { Line, Bar } from 'react-chartjs-2';
import YoYStatsCard from './YoYStatsCard';
import { formatNumber } from './utils';

const YoYKepegawaianSlide = ({ years, stats, commonOptions }) => {
  const chronologicalYears = useMemo(() => [...years].reverse(), [years]);
  const currentYear = years[0];
  const prevYear = years[1];
  const color = '#4f46e5'; // Indigo-600

  // 1. Tren Total Pegawai & Gender
  const genderTrend = useMemo(() => ({
    labels: chronologicalYears.map(y => `Thn ${y}`),
    datasets: [
      {
        label: 'Laki-laki',
        data: chronologicalYears.map(y => stats[y]?.kepegawaian?.gender?.['Laki-laki'] || 0),
        backgroundColor: '#3b82f6',
        stack: 'gender',
      },
      {
        label: 'Perempuan',
        data: chronologicalYears.map(y => stats[y]?.kepegawaian?.gender?.['Perempuan'] || 0),
        backgroundColor: '#ec4899',
        stack: 'gender',
      }
    ]
  }), [chronologicalYears, stats]);

  const totalTrend = useMemo(() => ({
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

  // 2. Tren Status Pegawai (PNS, PPPK, Honorer)
  const statusTypeTrend = useMemo(() => ({
    labels: chronologicalYears.map(y => `Thn ${y}`),
    datasets: [
      {
        label: 'PNS',
        data: chronologicalYears.map(y => stats[y]?.kepegawaian?.total_pns || 0),
        backgroundColor: '#10b981',
      },
      {
        label: 'PPPK',
        data: chronologicalYears.map(y => stats[y]?.kepegawaian?.total_pppk || 0),
        backgroundColor: '#3b82f6',
      },
      {
        label: 'Honorer',
        data: chronologicalYears.map(y => stats[y]?.kepegawaian?.total_honorer || 0),
        backgroundColor: '#f59e0b',
      }
    ]
  }), [chronologicalYears, stats]);

  // 3. Tren Bezetting
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

  // 4. Tren Masa Kerja YoY
  const workingYearsTrend = useMemo(() => {
    const categories = ['0-5 Tahun', '6-10 Tahun', '11-15 Tahun', '16-20 Tahun', '> 20 Tahun'];
    const colors = ['#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b'];

    return {
      labels: chronologicalYears.map(y => `Thn ${y}`),
      datasets: categories.map((cat, i) => ({
        label: cat,
        data: chronologicalYears.map(y => stats[y]?.kepegawaian?.working_years?.[cat] || 0),
        backgroundColor: colors[i],
      }))
    };
  }, [chronologicalYears, stats]);

  const yearsCount = years.length;

  return (
    <div className="min-w-full px-4">
      <div className="max-w-7xl mx-auto space-y-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          
          {/* Sidebar: Total Pegawai */}
          <div className="lg:col-span-1">
            <YoYStatsCard
              title="Total Pegawai"
              currentTotal={stats[currentYear]?.kepegawaian?.total_pegawai || 0}
              prevTotal={stats[prevYear]?.kepegawaian?.total_pegawai || 0}
              unit="Orang" color={color} currentYear={currentYear} prevYear={prevYear}
            />
          </div>

          {/* Main Content: Charts Stack */}
          <div className="lg:col-span-3 space-y-6">
            
            {/* Row 1: Primary Trends */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Tren Pertumbuhan Pegawai</h4>
                <div className="h-[250px]">
                  <Line data={totalTrend} options={{ ...commonOptions, maintainAspectRatio: false }} />
                </div>
              </div>

              <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Analisis Bezetting YoY</h4>
                <div className="h-[250px]">
                  <Line data={bezettingTrend} options={{ ...commonOptions, maintainAspectRatio: false }} />
                </div>
              </div>
            </div>

            {/* Row 2: Composition Breakdowns */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Komposisi Gender YoY</h4>
                <div className="h-[250px]">
                  <Bar data={genderTrend} options={{ ...commonOptions, maintainAspectRatio: false, scales: { ...commonOptions.scales, x: { stacked: true }, y: { stacked: true } } }} />
                </div>
              </div>

              <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Distribusi Status Pegawai</h4>
                <div className="h-[250px]">
                  <Bar data={statusTypeTrend} options={{ ...commonOptions, maintainAspectRatio: false, scales: { ...commonOptions.scales, x: { stacked: true }, y: { stacked: true } } }} />
                </div>
              </div>
            </div>

            {/* Row 3: Length of Service (Full Width) */}
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider">Analisis Masa Kerja Pegawai (YoY)</h4>
                
                <div className="flex items-center gap-6 px-5 py-2.5 bg-indigo-50/50 rounded-2xl border border-indigo-100/50">
                  <div className="flex flex-col">
                    <span className="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Fulfillment Rate</span>
                    <div className="flex items-baseline gap-1">
                      <span className="text-lg font-black text-indigo-600">
                        {stats[currentYear]?.kepegawaian?.bezetting?.total_kebutuhan > 0
                          ? ((stats[currentYear].kepegawaian.bezetting.total_eksisting / stats[currentYear].kepegawaian.bezetting.total_kebutuhan) * 100).toFixed(1)
                          : 0}%
                      </span>
                    </div>
                  </div>
                  <div className="w-px h-8 bg-indigo-100"></div>
                  <div className="flex flex-col">
                    <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Target</span>
                    <span className="text-lg font-black text-gray-900">{formatNumber(stats[currentYear]?.kepegawaian?.bezetting?.total_kebutuhan || 0)}</span>
                  </div>
                </div>
              </div>
              
              <div className="h-[280px]">
                <Bar data={workingYearsTrend} options={{ ...commonOptions, maintainAspectRatio: false }} />
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(YoYKepegawaianSlide);

