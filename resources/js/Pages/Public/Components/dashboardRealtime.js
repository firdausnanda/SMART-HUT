export function affectsDashboard(change, visibleYears, effectiveCdkId) {
  if (!change || !Array.isArray(visibleYears)) return false;

  const eventCdk = change.cdkId == null ? null : Number(change.cdkId);
  const selectedCdk = effectiveCdkId == null ? null : Number(effectiveCdkId);
  if (selectedCdk !== null && eventCdk !== null && selectedCdk !== eventCdk) return false;

  return change.year == null || visibleYears.some((year) => Number(year) === Number(change.year));
}
