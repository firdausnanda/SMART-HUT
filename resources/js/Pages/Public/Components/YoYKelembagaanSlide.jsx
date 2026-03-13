import React, { useMemo } from 'react';
import { Line } from 'react-chartjs-2';
import YoYStatsCard from './YoYStatsCard';
import { formatNumber } from './utils';

const YoYKelembagaanSlide = ({ type, years, stats, commonOptions }) => {
  const chronologicalYears = useMemo(() => [...years].reverse(), [years]);

  const color = type === 'ps' ? '#059669' : '#84cc16';
  const title = type === 'ps' ? 'Perhutanan Sosial' : 'Hutan Rakyat';
  const groupLabel = type === 'ps' ? 'Kelompok' : 'KTH';
  const statsKey = type === 'ps' ? 'kelembagaan_ps' : 'kelembagaan_hr';

  const trendData = useMemo(() => {
    return {
      labels: chronologicalYears.map(y => `Thn ${y}`),
      datasets: [
        {
          label: `Nilai Ekonomi ${title} (Rp)`,
          data: chronologicalYears.map(y => type === 'ps' ? stats[y].kelembagaan_ps?.nekon_total : stats[y].kelembagaan_hr?.nte_total),
          borderColor: color,
          backgroundColor: color + '20',
          fill: true,
          tension: 0.4,
        }
      ]
    };
  }, [chronologicalYears, stats, type, title, color]);

  let yearsCount = years.length;

  return (
    <div className="min-w-full px-4">
      <div className="max-w-7xl mx-auto space-y-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <div className="lg:col-span-1 space-y-6">
            <YoYStatsCard
              title={`Jumlah ${groupLabel}`}
              currentTotal={stats[years[0]][statsKey]?.kelompok_count || 0}
              prevTotal={stats[years[1]][statsKey]?.kelompok_count || 0}
              unit={type === 'ps' ? 'Kelompok' : 'Lembaga'}
              color={color}
              currentYear={years[0]}
              prevYear={years[1]}
            />
            <YoYStatsCard
              title={`Nilai Ekonomi`}
              currentTotal={type === 'ps' ? stats[years[0]].kelembagaan_ps?.nekon_total : stats[years[0]].kelembagaan_hr?.nte_total}
              prevTotal={type === 'ps' ? stats[years[1]].kelembagaan_ps?.nekon_total : stats[years[1]].kelembagaan_hr?.nte_total}
              unit="Rp"
              color={color}
              currentYear={years[0]}
              prevYear={years[1]}
            />
          </div>

          <div className="md:col-span-3">
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Tren Nilai Ekonomi {yearsCount} Tahun Terakhir - {title}</h4>
              <div className="flex-1 min-h-[400px]">
                <Line
                  data={trendData}
                  options={{
                    ...commonOptions,
                    plugins: {
                      ...commonOptions.plugins,
                      tooltip: {
                        callbacks: {
                          label: (context) => ` Rp ${formatNumber(context.raw)}`
                        }
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
  );
};

export default React.memo(YoYKelembagaanSlide);
