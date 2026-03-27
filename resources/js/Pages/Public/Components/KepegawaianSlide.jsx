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

  const genderChartData = useMemo(() => {
    const rawLabels = Object.keys(kepegawaian?.gender || {});
    const data = Object.values(kepegawaian?.gender || {});

    const labels = rawLabels.map(label => {
      const lower = label.toLowerCase();
      if (lower === 'laki-laki' || lower === 'l') return 'Laki - Laki';
      if (lower === 'perempuan' || lower === 'p') return 'Perempuan';
      return label;
    });

    return {
      labels,
      datasets: [{
        data,
        backgroundColor: [
          '#3b82f6', // Indigo
          '#ec4899', // Pink
        ],
        borderWidth: 0,
      }]
    };
  }, [kepegawaian?.gender]);

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

  const generationChartData = useMemo(() => {
    return {
      labels: Object.keys(kepegawaian?.generations || {}),
      datasets: [{
        label: 'Jumlah Pegawai',
        data: Object.values(kepegawaian?.generations || {}),
        backgroundColor: [
          '#6366f1', // Indigo
          '#e4f65cff', // Violet
          '#ec4899', // Pink
          '#f43f5e', // Rose
          '#f59e0b', // Amber
          '#10b981', // Emerald
        ],
        borderWidth: 0,
      }]
    };
  }, [kepegawaian?.generations]);

  const bezettingChartData = useMemo(() => {
    const details = kepegawaian?.bezetting?.details || [];
    // Take top 8 positions for clarity
    const topDetails = [...details]
      .sort((a, b) => Math.max(b.kebutuhan, b.eksisting) - Math.max(a.kebutuhan, a.eksisting))
      .slice(0, 8);

    return {
      labels: topDetails.map(d => d.name.length > 40 ? d.name.substring(0, 40) + '...' : d.name),
      datasets: [
        {
          label: 'Kebutuhan',
          data: topDetails.map(d => d.kebutuhan),
          backgroundColor: '#e0e7ff', // indigo-100
          borderRadius: 6,
          borderWidth: 1,
          borderColor: '#c7d2fe', // indigo-200
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

  return (
    <div className="min-w-full px-4">
      <div className="max-w-7xl mx-auto space-y-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          {/* Left: Stats Card */}
          <div className="lg:col-span-1 flex flex-col gap-6">
            <StatsCard
              title="Total Pegawai"
              total={kepegawaian?.total_pegawai || 0}
              unit="Orang"
              color={color}
              year={currentYear}
            />

            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex-1">
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
                  <span className="text-xs text-gray-500 font-medium">Kebutuhan Total</span>
                  <span className="text-xl font-black text-gray-900">{kepegawaian?.bezetting?.total_kebutuhan || 0}</span>
                </div>
                <div className="flex justify-between items-end">
                  <span className="text-xs text-gray-500 font-medium">Eksisting Total</span>
                  <span className="text-xl font-black text-indigo-600">{kepegawaian?.bezetting?.total_eksisting || 0}</span>
                </div>
                <div className="pt-4 border-t border-gray-50">
                  <div className="flex justify-between items-center mb-1">
                    <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pemenuhan</span>
                    <span className="text-xs font-bold text-indigo-600">
                      {kepegawaian?.bezetting?.total_kebutuhan > 0
                        ? ((kepegawaian.bezetting.total_eksisting / kepegawaian.bezetting.total_kebutuhan) * 100).toFixed(1)
                        : 0}%
                    </span>
                  </div>
                  <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div
                      className="h-full bg-indigo-600 rounded-full transition-all duration-1000"
                      style={{ width: `${Math.min((kepegawaian?.bezetting?.total_eksisting / kepegawaian?.bezetting?.total_kebutuhan) * 100, 100)}%` }}
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Right: Charts Grid */}
          <div className="lg:col-span-3 space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {[
                { title: 'Distribusi Jenis Kelamin', data: genderChartData, label: 'Gender' },
                { title: 'Status Kepegawaian', data: statusChartData, label: 'Status' },
                { title: 'Profil Generasi', data: generationChartData, label: 'Generasi' }
              ].map((chart, idx) => (
                <div key={idx} className="relative group">
                  <div className="absolute -inset-0.5 bg-gradient-to-b from-white to-gray-50 rounded-[2rem] blur-lg opacity-20 group-hover:opacity-40 transition-opacity"></div>
                  <div className="relative bg-white p-6 rounded-[2rem] shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col h-full hover:translate-y-[-2px] transition-all duration-300">
                    <h4 className="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-6">{chart.title}</h4>
                    <div className="h-[200px] flex items-center justify-center relative">
                      <Doughnut
                        data={chart.data}
                        options={{
                          maintainAspectRatio: false,
                          cutout: '80%',
                          plugins: {
                            legend: {
                              position: 'bottom',
                              labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: { size: 9, weight: 'bold' }
                              }
                            },
                            tooltip: {
                              backgroundColor: 'rgba(255, 255, 255, 0.9)',
                              titleColor: '#1f2937',
                              bodyColor: '#4b5563',
                              borderColor: '#e5e7eb',
                              borderWidth: 1,
                              padding: 12,
                              boxPadding: 6,
                              usePointStyle: true,
                            }
                          }
                        }}
                      />
                      <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mb-8">
                        <span className="text-2xl font-black text-gray-900 leading-none">
                          {formatNumber(chart.data.datasets[0].data.reduce((a, b) => a + b, 0))}
                        </span>
                        <span className="text-[8px] font-bold text-gray-400 uppercase tracking-widest mt-1">Total</span>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <div className="grid grid-cols-1 gap-6">
              {/* Bezetting Comparison Chart */}
              <div className="relative group">
                <div className="absolute -inset-0.5 bg-gradient-to-b from-white to-gray-50 rounded-[2rem] blur-lg opacity-20 group-hover:opacity-40 transition-opacity"></div>
                <div className="relative bg-white p-8 rounded-[2rem] shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 min-h-[500px] hover:translate-y-[-2px] transition-all duration-300">
                  <h4 className="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-8">Bezetting Per Jabatan (Top 8)</h4>
                  <div className="h-[400px]">
                    <Bar
                      data={bezettingChartData}
                      options={{
                        ...commonOptions,
                        indexAxis: 'y',
                        maintainAspectRatio: false,
                        plugins: {
                          legend: {
                            position: 'top',
                            labels: {
                              boxWidth: 10,
                              font: { size: 10, weight: 'bold' },
                              usePointStyle: true,
                              padding: 20
                            }
                          },
                          tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1f2937',
                            bodyColor: '#4b5563',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            padding: 12,
                            boxPadding: 6,
                            usePointStyle: true,
                          }
                        },
                        scales: {
                          y: {
                            grid: { display: false },
                            ticks: {
                              font: { size: 10, weight: 'bold' },
                              padding: 10
                            }
                          },
                          x: {
                            grid: {
                              color: 'rgba(0, 0, 0, 0.03)',
                              drawBorder: false
                            },
                            ticks: {
                              stepSize: 5,
                              font: { size: 10, weight: 'bold' }
                            },
                            beginAtZero: true
                          }
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
    </div>
  );
};

export default React.memo(KepegawaianSlide);
