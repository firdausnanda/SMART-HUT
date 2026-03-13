import React, { useMemo } from 'react';
import { Line } from 'react-chartjs-2';
import YoYStatsCard from './YoYStatsCard';
import { formatNumber } from './utils';

const YoYProductionSlide = ({ source, years, stats, commonOptions }) => {
  const chronologicalYears = useMemo(() => [...years].reverse(), [years]);

  const trendData = useMemo(() => {
    return {
      labels: chronologicalYears.map(y => `Thn ${y}`),
      datasets: [
        {
          label: 'Kayu (m³)',
          data: chronologicalYears.map(y => stats[y].bina_usaha?.[source.key]?.kayu_total || 0),
          borderColor: source.color,
          backgroundColor: source.color + '20',
          fill: true,
          tension: 0.4,
        },
        {
          label: 'HHBK (Satuan)',
          data: chronologicalYears.map(y => stats[y].bina_usaha?.[source.key]?.bukan_kayu_total || 0),
          borderColor: '#0ea5e9',
          backgroundColor: '#0ea5e920',
          fill: true,
          tension: 0.4,
          borderDash: [5, 5],
        }
      ]
    };
  }, [chronologicalYears, stats, source]);

  let yearsCount = years.length;

  return (
    <div className="min-w-full px-4">
      <div className="max-w-7xl mx-auto space-y-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <div className="lg:col-span-1 space-y-6">
            <YoYStatsCard
              title={`Kayu - ${source.title}`}
              currentTotal={stats[years[0]].bina_usaha?.[source.key]?.kayu_total || 0}
              prevTotal={stats[years[1]].bina_usaha?.[source.key]?.kayu_total || 0}
              unit="m³"
              color={source.color}
              currentYear={years[0]}
              prevYear={years[1]}
            />
            <YoYStatsCard
              title={`HHBK - ${source.title}`}
              currentTotal={stats[years[0]].bina_usaha?.[source.key]?.bukan_kayu_total || 0}
              prevTotal={stats[years[1]].bina_usaha?.[source.key]?.bukan_kayu_total || 0}
              unit="Satuan"
              color="#0ea5e9"
              currentYear={years[0]}
              prevYear={years[1]}
            />
          </div>

          <div className="md:col-span-3 space-y-6">
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Tren Produksi {yearsCount} Tahun Terakhir - {source.title}</h4>
              <div className="flex-1 min-h-[400px]">
                <Line
                  data={trendData}
                  options={commonOptions}
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(YoYProductionSlide);
