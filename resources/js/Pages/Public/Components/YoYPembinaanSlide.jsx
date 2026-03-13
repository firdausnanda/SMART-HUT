import React, { useMemo } from 'react';
import { Bar, Line } from 'react-chartjs-2';
import YoYStatsCard from './YoYStatsCard';
import { formatNumber } from './utils';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title, PointElement, LineElement, Filler } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title, PointElement, LineElement, Filler);

const YoYPembinaanSlide = ({ label, secKey, years, stats, color, unit, commonOptions }) => {

  const chronologicalYears = useMemo(() => [...years].reverse(), [years]);

  const multiYearTrendData = useMemo(() => {
    return {
      labels: chronologicalYears.map(y => `Thn ${y}`),
      datasets: [
        {
          label: `Total Realisasi ${label}`,
          data: chronologicalYears.map(y => stats[y].pembinaan[`${secKey}_total`] || 0),
          borderColor: color,
          backgroundColor: color + '20',
          fill: true,
          tension: 0.4,
          pointRadius: 4,
          pointBackgroundColor: color,
        }
      ]
    };
  }, [chronologicalYears, stats, secKey, color, label]);

  const regencyTrendData = useMemo(() => {
    // Get all unique regency/type labels across all years
    const allLabels = new Set();
    chronologicalYears.forEach(y => {
      const yearStat = stats[y]?.pembinaan?.[`${secKey}_regency`] || stats[y]?.pembinaan?.rhl_teknis_type || {};
      Object.keys(yearStat).forEach(label => allLabels.add(label));
    });

    const sortedLabels = Array.from(allLabels).sort();

    // Predefined professional colors for lines
    const lineColors = [
      '#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6',
      '#ec4899', '#06b6d4', '#84cc16', '#14b8a6', '#f43f5e'
    ];

    return {
      labels: chronologicalYears.map(y => `Thn ${y}`),
      datasets: sortedLabels.map((regencyLabel, index) => ({
        label: regencyLabel,
        data: chronologicalYears.map(y => {
          const yearStat = stats[y]?.pembinaan?.[`${secKey}_regency`] || stats[y]?.pembinaan?.rhl_teknis_type || {};
          return yearStat[regencyLabel] || 0;
        }),
        borderColor: lineColors[index % lineColors.length],
        backgroundColor: lineColors[index % lineColors.length] + '20',
        fill: false,
        tension: 0.3,
        pointRadius: 3,
        pointHoverRadius: 5,
        borderWidth: 2,
      }))
    };
  }, [chronologicalYears, stats, secKey]);

  let yearsCount = years.length;

  return (
    <div className="min-w-full px-4">
      <div className="max-w-7xl mx-auto space-y-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <div className="lg:col-span-1">
            <YoYStatsCard
              title={label}
              currentTotal={stats[years[0]].pembinaan[`${secKey}_total`] || 0}
              prevTotal={stats[years[1]].pembinaan[`${secKey}_total`] || 0}
              unit={unit}
              color={color}
              currentYear={years[0]}
              prevYear={years[1]}
            />
          </div>

          <div className="md:col-span-3 space-y-6">
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <div className="flex items-center justify-between mb-4">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider">Tren Realisasi {yearsCount} Tahun Terakhir</h4>
              </div>
              <div className="h-[250px]">
                <Line
                  data={multiYearTrendData}
                  options={{
                    ...commonOptions,
                    maintainAspectRatio: false,
                    plugins: { ...commonOptions.plugins, legend: { display: false } },
                  }}
                />
              </div>
            </div>

            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
              <div className="flex items-center justify-between mb-4">
                <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider">Tren Realisasi per Wilayah / Jenis</h4>
              </div>
              <div className="h-[300px]">
                <Line
                  data={regencyTrendData}
                  options={{
                    ...commonOptions,
                    maintainAspectRatio: false,
                    plugins: {
                      ...commonOptions.plugins,
                      legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                          boxWidth: 8,
                          font: { size: 9, weight: 'bold' },
                          padding: 15
                        }
                      }
                    },
                    scales: {
                      y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { font: { size: 10, weight: 'bold' } }
                      },
                      x: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' } } }
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

export default React.memo(YoYPembinaanSlide);
