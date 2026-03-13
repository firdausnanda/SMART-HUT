import React, { useMemo } from 'react';
import { Line } from 'react-chartjs-2';
import YoYStatsCard from './YoYStatsCard';
import { formatNumber } from './utils';

const YoYPnbpSlide = ({ years, stats, commonOptions }) => {
  const chronologicalYears = useMemo(() => [...years].reverse(), [years]);

  const trendData = useMemo(() => {
    return {
      labels: chronologicalYears.map(y => `Thn ${y}`),
      datasets: [
        {
          label: 'PNBP (Rp)',
          data: chronologicalYears.map(y => stats[y].bina_usaha?.pnbp?.total_realization || 0),
          borderColor: '#d97706',
          backgroundColor: '#d9770620',
          fill: true,
          tension: 0.4,
        }
      ]
    };
  }, [chronologicalYears, stats]);

  let yearsCount = years.length;

  return (
    <div className="min-w-full px-4">
      <div className="max-w-7xl mx-auto space-y-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <div className="lg:col-span-1">
            <YoYStatsCard
              title="Penerimaan Negara (PNBP)"
              currentTotal={stats[years[0]].bina_usaha?.pnbp?.total_realization || 0}
              prevTotal={stats[years[1]].bina_usaha?.pnbp?.total_realization || 0}
              unit="Rp"
              color="#d97706"
              currentYear={years[0]}
              prevYear={years[1]}
            />
          </div>

          <div className="md:col-span-3">
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Tren Realisasi PNBP {yearsCount} Tahun Terakhir</h4>
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

export default React.memo(YoYPnbpSlide);
