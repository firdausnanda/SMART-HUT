import React, { useMemo } from 'react';
import { Line } from 'react-chartjs-2';
import YoYStatsCard from './YoYStatsCard';
import { formatNumber, formatCurrency } from './utils';

const YoYJasaLingkunganSlide = ({ years, stats, commonOptions }) => {
  const chronologicalYears = useMemo(() => [...years].reverse(), [years]);

  const trendData = useMemo(() => {
    return {
      labels: chronologicalYears.map(y => `Thn ${y}`),
      datasets: [
        {
          label: 'Pengunjung',
          data: chronologicalYears.map(y => stats[y]?.perlindungan?.wisata_visitors || 0),
          borderColor: '#4f46e5',
          backgroundColor: '#4f46e520',
          fill: true,
          tension: 0.4,
          yAxisID: 'y',
        },
        {
          label: 'Pendapatan (Rp)',
          data: chronologicalYears.map(y => stats[y]?.perlindungan?.wisata_income || 0),
          borderColor: '#10b981',
          backgroundColor: '#10b98120',
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
              title="Jumlah Pengunjung"
              currentTotal={stats[years[0]]?.perlindungan?.wisata_visitors || 0}
              prevTotal={stats[years[1]]?.perlindungan?.wisata_visitors || 0}
              unit="Orang"
              color="#4f46e5"
              currentYear={years[0]}
              prevYear={years[1]}
            />
            <YoYStatsCard
              title="Pendapatan Wisata"
              currentTotal={stats[years[0]]?.perlindungan?.wisata_income || 0}
              prevTotal={stats[years[1]]?.perlindungan?.wisata_income || 0}
              unit="Rp"
              color="#10b981"
              currentYear={years[0]}
              prevYear={years[1]}
            />
          </div>

          <div className="md:col-span-3 space-y-6">
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider mb-6">Tren Jasa Lingkungan {yearsCount} Tahun Terakhir</h4>
              <div className="flex-1 min-h-[400px]">
                <Line
                  data={trendData}
                  options={{
                    ...commonOptions,
                    maintainAspectRatio: true,
                    scales: {
                      y: { position: 'left', title: { display: true, text: 'Pengunjung' }, grid: { display: false } },
                      y1: { position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Pendapatan (Rp)' } },
                      x: { grid: { display: false } }
                    },
                    plugins: {
                      ...commonOptions.plugins,
                      tooltip: {
                        callbacks: {
                          label: (context) => {
                            const val = context.raw;
                            return context.dataset.label.includes('Pendapatan') ? ` Rp ${formatNumber(val)}` : ` ${formatNumber(val)} Orang`;
                          }
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

export default React.memo(YoYJasaLingkunganSlide);
