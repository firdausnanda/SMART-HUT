import React from 'react';
import SummaryTotalCard from './SummaryTotalCard';

const StatsCard = ({ title, total, unit, progress, target, color, year }) => (
  <SummaryTotalCard
    title={title || 'Total Realisasi'}
    total={total}
    unit={unit}
    color={color}
    year={year}
    secondary={target !== undefined ? { label: 'Target', value: target, unit } : undefined}
    progress={progress}
  />
);

export default React.memo(StatsCard);
