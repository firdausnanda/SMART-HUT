import React, { useMemo } from 'react';
import { Doughnut, Bar } from 'react-chartjs-2';
import StatsCard from './StatsCard';
import { formatNumber } from './utils';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title, PointElement, LineElement, Filler } from 'chart.js';

// Register ChartJS components
ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title, PointElement, LineElement, Filler);

const KepegawaianSlide = ({ stats, currentYear, commonOptions }) => {
  const kepegawaian = stats?.kepegawaian;
  const color = '#4f46e5'; // Indigo-600

  // 1. Status Kepegawaian (Donut)
  const statusChartData = useMemo(() => {
    return {
      labels: Object.keys(kepegawaian?.status || {}),
      datasets: [{
        data: Object.values(kepegawaian?.status || {}),
        backgroundColor: [
          '#10b981', // Emerald
          '#f59e0b', // Amber
          '#ef4444', // Red
          '#8b5cf6', // Violet
          '#06b6d4', // Cyan
        ],
        borderWidth: 0,
      }]
    };
  }, [kepegawaian?.status]);

  // 2. Profil Generasi (Donut)
  const generationChartData = useMemo(() => {
    return {
      labels: Object.keys(kepegawaian?.generations || {}),
      datasets: [{
        label: 'Jumlah Pegawai',
        data: Object.values(kepegawaian?.generations || {}),
        backgroundColor: [
          '#6366f1', // Indigo
          '#8b5cf6', // Violet
          '#ec4899', // Pink
          '#f43f5e', // Rose
          '#f59e0b', // Amber
          '#10b981', // Emerald
        ],
        borderWidth: 0,
      }]
    };
  }, [kepegawaian?.generations]);

  // 3. Pendidikan Terakhir (Donut)
  const educationChartData = useMemo(() => {
    return {
      labels: Object.keys(kepegawaian?.education || {}),
      datasets: [{
        data: Object.values(kepegawaian?.education || {}),
        backgroundColor: [
          '#3b82f6', // blue
          '#6366f1', // indigo
          '#8b5cf6', // violet
          '#d946ef', // fuchsia
          '#ec4899', // pink
          '#f43f5e', // rose
          '#f97316', // orange
          '#eab308', // yellow
        ],
        borderWidth: 0,
      }]
    };
  }, [kepegawaian?.education]);

  // 4. Distribusi Golongan (Bar)
  const rankChartData = useMemo(() => {
    const rawData = kepegawaian?.rank || {};
    return {
      labels: Object.keys(rawData),
      datasets: [{
        label: 'Jumlah Pegawai',
        data: Object.values(rawData),
        backgroundColor: '#6366f1',
        borderRadius: 4,
      }]
    };
  }, [kepegawaian?.rank]);

  // 5. Distribusi Masa Kerja (Bar)
  const workingYearsChartData = useMemo(() => {
    const rawData = kepegawaian?.working_years || {};
    return {
      labels: Object.keys(rawData),
      datasets: [{
        label: 'Jumlah Pegawai',
        data: Object.values(rawData),
        backgroundColor: '#10b981',
        borderRadius: 4,
      }]
    };
  }, [kepegawaian?.working_years]);

  // 6. Bezetting Jabatan (Horizontal Bar)
  const bezettingChartData = useMemo(() => {
    const details = kepegawaian?.bezetting?.details || [];
    const topDetails = [...details]
      .sort((a, b) => Math.max(b.kebutuhan, b.eksisting) - Math.max(a.kebutuhan, a.eksisting))
      .slice(0, 8);

    return {
      labels: topDetails.map(d => d.name.length > 40 ? d.name.substring(0, 40) + '...' : d.name),
      datasets: [
        {
          label: 'Kebutuhan',
          data: topDetails.map(d => d.kebutuhan),
          backgroundColor: '#e0e7ff',
          borderRadius: 6,
          borderWidth: 1,
          borderColor: '#c7d2fe',
        },
        {
          label: 'Eksisting',
          data: topDetails.map(d => d.eksisting),
          backgroundColor: color,
          borderRadius: 6,
          borderWidth: 1,
          borderColor: color,
        }
      ]
    };
  }, [kepegawaian?.bezetting, color]);

  const renderDonut = (title, data) => (
    <div className="relative group">
      <div className="absolute -inset-0.5 bg-gradient-to-b from-white to-gray-50 rounded-[2rem] blur-lg opacity-20 group-hover:opacity-40 transition-opacity"></div>
      <div className="relative bg-white p-6 rounded-[2rem] shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col h-full hover:translate-y-[-2px] transition-all duration-300">
        <h4 className="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-6">{title}</h4>
        <div className="h-[200px] flex items-center justify-center relative">
          <Doughnut
            data={data}
            options={{
              maintainAspectRatio: false,
              cutout: '75%',
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: {
                    usePointStyle: true,
                    padding: 12,
                    font: { size: 8, weight: 'bold' }
                  }
                },
                tooltip: {
                  backgroundColor: 'rgba(255, 255, 255, 0.9)',
                  titleColor: '#1f2937',
                  bodyColor: '#4b5563',
                  borderColor: '#e5e7eb',
                  borderWidth: 1,
                }
              }
            }}
          />
          <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mb-10">
            <span className="text-xl font-black text-gray-900 leading-none">
              {formatNumber(data.datasets[0].data.reduce((a, b) => a + b, 0))}
            </span>
            <span className="text-[8px] font-bold text-gray-400 uppercase tracking-widest mt-1">Total</span>
          </div>
        </div>
      </div>
    </div>
  );

  const renderBar = (title, data) => (
    <div className="relative group">
      <div className="absolute -inset-0.5 bg-gradient-to-b from-white to-gray-50 rounded-[2rem] blur-lg opacity-20 group-hover:opacity-40 transition-opacity"></div>
      <div className="relative bg-white p-6 rounded-[2rem] shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col h-full hover:translate-y-[-2px] transition-all duration-300">
        <h4 className="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-6">{title}</h4>
        <div className="h-[200px]">
          <Bar
            data={data}
            options={{
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: {
                  backgroundColor: 'rgba(255, 255, 255, 0.95)',
                  titleColor: '#1f2937',
                  bodyColor: '#4b5563',
                  borderColor: '#e5e7eb',
                  borderWidth: 1,
                }
              },
              scales: {
                y: { grid: { color: 'rgba(0,0,0,0.03)' }, ticks: { font: { size: 9, weight: 'bold' } } },
                x: { grid: { display: false }, ticks: { font: { size: 8, weight: 'bold' } } }
              }
            }}
          />
        </div>
      </div>
    </div>
  );

  return (
    <div className="min-w-full px-4">
      <div className="max-w-7xl mx-auto space-y-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          {/* Left: Stats Card & Alerts */}
          <div className="lg:col-span-1 flex flex-col gap-6">
            <StatsCard
              title="Total Pegawai"
              total={kepegawaian?.total_pegawai || 0}
              unit="Orang"
              color={color}
              year={currentYear}
            />

            {/* Employment Status Pills */}
            <div className="flex justify-between gap-2">
              {[
                { label: 'PNS', value: kepegawaian?.total_pns, color: 'bg-emerald-50 text-emerald-700 border-emerald-100' },
                { label: 'PPPK', value: kepegawaian?.total_pppk, color: 'bg-blue-50 text-blue-700 border-blue-100' },
                { label: 'Honorer', value: kepegawaian?.total_honorer, color: 'bg-amber-50 text-amber-700 border-amber-100' },
              ].map((item, i) => (
                <div key={i} className={`flex-1 flex flex-col items-center py-2 px-1 rounded-2xl border ${item.color} shadow-sm`}>
                  <span className="text-[10px] font-black uppercase tracking-tighter opacity-70 mb-0.5">{item.label}</span>
                  <span className="text-sm font-black">{formatNumber(item.value || 0)}</span>
                </div>
              ))}
            </div>

            {/* Alert Cards Grid */}
            <div className="grid grid-cols-2 gap-3">
              {[
                { title: 'Pensiun Bln Ini', value: kepegawaian?.pensiun_bulan_ini, color: 'text-red-600', bg: 'bg-red-50' },
                { title: 'Pensiun 6 Bln', value: kepegawaian?.pensiun_6_bulan, color: 'text-orange-600', bg: 'bg-orange-50' },
                { title: 'KGB Bulan Ini', value: kepegawaian?.kgb_bulan_ini, color: 'text-indigo-600', bg: 'bg-indigo-50' },
                { title: 'KGB 3 Bulan', value: kepegawaian?.kgb_3_bulan, color: 'text-emerald-600', bg: 'bg-emerald-50' },
              ].map((alert, i) => (
                <div key={i} className={`${alert.bg} p-3 rounded-2xl border border-white shadow-sm flex flex-col items-center text-center justify-center`}>
                  <span className={`text-lg font-black ${alert.color}`}>{alert.value || 0}</span>
                  <span className="text-[9px] font-bold text-gray-500 leading-tight uppercase tracking-tighter mt-1">{alert.title}</span>
                </div>
              ))}
            </div>

            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <div className="flex items-center gap-3 mb-6">
                <div className="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </div>
                <h4 className="font-bold text-gray-900">Bezetting Jabatan</h4>
              </div>

              <div className="space-y-4">
                <div className="flex justify-between items-end">
                  <span className="text-xs text-gray-500 font-medium">Kebutuhan</span>
                  <span className="text-xl font-black text-gray-900">{kepegawaian?.bezetting?.total_kebutuhan || 0}</span>
                </div>
                <div className="flex justify-between items-end">
                  <span className="text-xs text-gray-500 font-medium">Eksisting</span>
                  <span className="text-xl font-black text-indigo-600">{kepegawaian?.bezetting?.total_eksisting || 0}</span>
                </div>
              </div>
            </div>

            {/* Period Label */}
            {kepegawaian?.periode_label && (
              <div className="text-center">
                <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest italic bg-gray-100 px-3 py-1 rounded-full">
                  Periode: {kepegawaian.periode_label}
                </span>
              </div>
            )}
          </div>

          {/* Right: Charts Grid */}
          <div className="lg:col-span-3 space-y-6">
            {/* Row 1: Donuts */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {renderDonut('Status Kepegawaian', statusChartData)}
              {renderDonut('Profil Generasi', generationChartData)}
              {renderDonut('Pendidikan Terakhir', educationChartData)}
            </div>

            {/* Row 2: Bars */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {renderBar('Distribusi Masa Kerja', workingYearsChartData)}
              {renderBar('Distribusi Golongan', rankChartData)}
            </div>

            {/* Row 3: Bezetting (Full) */}
            <div className="relative group">
              <div className="absolute -inset-0.5 bg-gradient-to-b from-white to-gray-50 rounded-[2rem] blur-lg opacity-20 group-hover:opacity-40 transition-opacity"></div>
              <div className="relative bg-white p-8 rounded-[2rem] shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 min-h-[400px] hover:translate-y-[-2px] transition-all duration-300">
                <h4 className="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-8">Bezetting Per Jabatan (Top 8)</h4>
                <div className="h-[300px]">
                  <Bar
                    data={bezettingChartData}
                    options={{
                      ...commonOptions,
                      indexAxis: 'y',
                      maintainAspectRatio: false,
                      plugins: {
                        legend: { position: 'top', labels: { boxWidth: 10, font: { size: 10, weight: 'bold' }, usePointStyle: true } },
                      },
                      scales: {
                        y: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' } } },
                        x: { grid: { color: 'rgba(0,0,0,0.03)' }, ticks: { font: { size: 10, weight: 'bold' } } }
                      }
                    }}
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(KepegawaianSlide);
