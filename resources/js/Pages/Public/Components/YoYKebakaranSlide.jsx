import React, { useMemo } from 'react';
import { Line, Bar } from 'react-chartjs-2';
import YoYStatsCard from './YoYStatsCard';

const YoYKebakaranSlide = ({ years, stats, commonOptions }) => {
  const chronologicalYears = useMemo(() => [...years].reverse(), [years]);

  const trendData = useMemo(() => {
    return {
      labels: chronologicalYears.map(y => `Thn ${y}`),
      datasets: [
        {
          label: 'Kejadian',
          data: chronologicalYears.map(y => stats[y].perlindungan?.kebakaran_kejadian || 0),
          borderColor: '#dc2626',
          backgroundColor: '#dc262620',
          fill: true,
          tension: 0.4,
          yAxisID: 'y',
        },
        {
          label: 'Luas Area (Ha)',
          data: chronologicalYears.map(y => stats[y].perlindungan?.kebakaran_area || 0),
          borderColor: '#f97316',
          backgroundColor: '#f9731620',
          fill: true,
          tension: 0.4,
          borderDash: [5, 5],
          yAxisID: 'y1',
        }
      ]
    };
  }, [chronologicalYears, stats]);

  let yearsCount = years.length;

  return (
    <div className="min-w-full px-4">
      <div className="max-w-7xl mx-auto space-y-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <div className="lg:col-span-1 space-y-6">
            <YoYStatsCard
              title="Kejadian Kebakaran"
              currentTotal={stats[years[0]]?.perlindungan?.kebakaran_kejadian || 0}
              prevTotal={stats[years[1]]?.perlindungan?.kebakaran_kejadian || 0}
              unit="Kejadian"
              color="#dc2626"
              currentYear={years[0]}
              prevYear={years[1]}
            />
            <YoYStatsCard
              title="Luas Area Terbakar"
              currentTotal={stats[years[0]]?.perlindungan?.kebakaran_area || 0}
              prevTotal={stats[years[1]]?.perlindungan?.kebakaran_area || 0}
              unit="Ha"
              color="#f97316"
              currentYear={years[0]}
              prevYear={years[1]}
            />
          </div>

          <div className="md:col-span-3 space-y-6">
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Tren Karhutla {yearsCount} Tahun Terakhir</h4>
              <div className="flex-1 min-h-[400px]">
                <Line
                  data={trendData}
                  options={{
                    ...commonOptions,
                    maintainAspectRatio: true,
                    scales: {
                      y: { position: 'left', title: { display: true, text: 'Kejadian' } },
                      y1: { position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Luas (Ha)' } },
                      x: { grid: { display: false } }
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

export default React.memo(YoYKebakaranSlide);
