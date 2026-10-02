export const unitOrder = ['kg', 'ton', 'm3', 'liter', 'batang', 'ekor', 'buah', 'pcs', 'ikat', 'bibit', 'stup', 'orang', 'butir'];

export function availableUnits(groups) {
  const unique = new Map(groups.map(group => [group.unit, group]));
  return [...unique.values()].sort((a, b) => unitOrder.indexOf(a.unit) - unitOrder.indexOf(b.unit));
}

export function selectedUnit(groups, preferred) {
  return groups.find(group => group.unit === preferred) ?? groups.find(group => group.unit === 'kg') ?? groups[0] ?? null;
}

export function compareProduction(current, previous) {
  if (current == null || previous == null) return { growth: null, reason: 'Data salah satu tahun tidak tersedia.' };
  if (previous <= 0) return { growth: null, reason: 'Nilai tahun sebelumnya nol atau kurang.' };
  return { growth: (current - previous) / previous * 100, reason: null };
}

export function totalForUnit(production, unit) {
  return production?.bukan_kayu_by_unit?.find(group => group.unit === unit)?.total ?? null;
}

export function topCommodities(group) {
  return (group?.commodities ?? []).slice(0, 5);
}
