import React, { useMemo } from 'react';
import { Doughnut, Bar } from 'react-chartjs-2';
import { formatNumber, formatCurrency } from './utils';

const KelembagaanHrSlide = ({ stats, commonOptions }) => {
  const classData = useMemo(() => {
    return {
      labels: stats?.kelembagaan_hr?.class_distribution ? stats.kelembagaan_hr.class_distribution.map(d => d.class_name) : [],
      datasets: [{
        data: stats?.kelembagaan_hr?.class_distribution ? stats.kelembagaan_hr.class_distribution.map(d => d.count) : [],
        backgroundColor: ['#4d7c0f', '#65a30d', '#84cc16', '#a3e635'],
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverOffset: 10,
      }]
    };
  }, [stats?.kelembagaan_hr?.class_distribution]);

  const economicData = useMemo(() => {
    return {
      labels: stats?.kelembagaan_hr?.economic_by_regency ? Object.keys(stats.kelembagaan_hr.economic_by_regency) : [],
      datasets: [{
        label: 'NTE (Rp)',
        data: stats?.kelembagaan_hr?.economic_by_regency ? Object.values(stats.kelembagaan_hr.economic_by_regency) : [],
        backgroundColor: '#84cc16CC',
        borderRadius: 4
      }]
    };
  }, [stats?.kelembagaan_hr?.economic_by_regency]);

  const topCommoditiesData = useMemo(() => {
    return {
      labels: stats?.kelembagaan_hr?.top_commodities ? Object.keys(stats.kelembagaan_hr.top_commodities) : [],
      datasets: [{
        label: 'Nilai Transaksi',
        data: stats?.kelembagaan_hr?.top_commodities ? Object.values(stats.kelembagaan_hr.top_commodities) : [],
        backgroundColor: '#65a30dCC',
        borderRadius: 4
      }]
    };
  }, [stats?.kelembagaan_hr?.top_commodities]);

  const doughnutPlugins = useMemo(() => [{
    id: 'centerTextHr',
    beforeDraw: (chart) => {
      const { ctx, chartArea: { top, bottom, left, right, width, height } } = chart;
      ctx.save();
      const text = stats?.kelembagaan_hr?.kelompok_count || 0;
      ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      ctx.font = '900 64px Inter, sans-serif'; ctx.fillStyle = '#1e293b';
      ctx.fillText(text, left + width / 2, top + height / 2 - 12);
      ctx.font = 'bold 11px Inter, sans-serif'; ctx.fillStyle = '#64748b';
      ctx.letterSpacing = '1px';
      ctx.fillText('TOTAL KELOMPOK', left + width / 2, top + height / 2 + 20); ctx.restore();
    }
  }], [stats?.kelembagaan_hr?.kelompok_count]);

  const doughnutOptions = useMemo(() => ({
    maintainAspectRatio: false,
    cutout: '75%',
    layout: { padding: 20 },
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          usePointStyle: true,
          pointStyle: 'circle',
          padding: 20,
          font: { weight: '600', size: 10, family: "'Inter', sans-serif" },
          color: '#475569'
        }
      },
      tooltip: {
        backgroundColor: 'rgba(255, 255, 255, 0.95)',
        titleColor: '#1e293b',
        bodyColor: '#475569',
        borderColor: '#e2e8f0',
        borderWidth: 1,
        padding: 12,
        boxPadding: 6,
        callbacks: {
          label: (ctx) => {
            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
            const percentage = ((ctx.raw / total) * 100).toFixed(1);
            return ` ${ctx.label}: ${ctx.raw} Kelompok (${percentage}%)`;
          },
          labelColor: function (context) {
            return {
              borderColor: context.dataset.backgroundColor[context.dataIndex],
              backgroundColor: context.dataset.backgroundColor[context.dataIndex]
            };
          }
        }
      }
    }
  }), []);

  const barOptions = useMemo(() => ({
    ...commonOptions,
    maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: { ...commonOptions.plugins, legend: { display: false } }
  }), [commonOptions]);

  const topCommoditiesOptions = useMemo(() => ({
    ...commonOptions,
    maintainAspectRatio: false,
    plugins: { ...commonOptions.plugins, legend: { display: false } }
  }), [commonOptions]);

  return (
    <div className="min-w-full px-4">
      <div className="max-w-7xl mx-auto space-y-8">
        {/* Top: Metric Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <p className="text-[10px] font-black uppercase tracking-widest text-lime-600 mb-2">Total Kelompok</p>
            <h3 className="text-3xl font-black text-gray-900">{formatNumber(stats?.kelembagaan_hr?.kelompok_count || 0)}</h3>
            <p className="text-[9px] text-gray-400 mt-1 uppercase font-bold">Unit Kelompok</p>
          </div>
          <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <p className="text-[10px] font-black uppercase tracking-widest text-lime-600 mb-2">Total Luas</p>
            <h3 className="text-3xl font-black text-gray-900">{formatNumber(stats?.kelembagaan_hr?.area_total || 0)}</h3>
            <p className="text-[9px] text-gray-400 mt-1 uppercase font-bold">Hektar (Ha)</p>
          </div>
          <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <p className="text-[10px] font-black uppercase tracking-widest text-lime-600 mb-2">Jumlah Anggota</p>
            <h3 className="text-3xl font-black text-gray-900">{formatNumber(stats?.kelembagaan_hr?.anggota_total || 0)}</h3>
            <p className="text-[9px] text-gray-400 mt-1 uppercase font-bold">Orang Anggota</p>
          </div>
          <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <p className="text-[10px] font-black uppercase tracking-widest text-lime-600 mb-2">Total NTE</p>
            <h3 className="text-xl font-black text-gray-900">{formatCurrency(stats?.kelembagaan_hr?.nte_total || 0)}</h3>
            <p className="text-[9px] text-gray-400 mt-1 uppercase font-bold">Nilai Transaksi Ekonomi</p>
          </div>
        </div>

        {/* Charts Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Kelas Kelembagaan (Pie) */}
          <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col h-full">
            <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Kelas Kelembagaan (Persentase & Jumlah)</h4>
            <div className="h-[250px] mt-auto relative">
              <Doughnut
                data={classData}
                plugins={doughnutPlugins}
                options={doughnutOptions}
              />
            </div>
          </div>

          {/* Nilai Ekonomi Per Kabupaten (Bar) */}
          <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col h-full">
            <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Nilai Transaksi Ekonomi (NTE) Per Kabupaten</h4>
            <div className="h-[250px] mt-auto">
              <Bar
                data={economicData}
                options={barOptions}
              />
            </div>
          </div>

          {/* Komoditas Terbesar (Bar) */}
          <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col h-full">
            <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">5 Komoditas Terbesar (NTE)</h4>
            <div className="h-[250px] mt-auto">
              <Bar
                data={topCommoditiesData}
                options={topCommoditiesOptions}
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(KelembagaanHrSlide);
