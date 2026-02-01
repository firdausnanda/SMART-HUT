export const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value);
};

export const formatNumber = (value) => {
  return new Intl.NumberFormat('id-ID').format(value);
};

export const truncateName = (name, length = 15) => {
  if (!name) return '';
  return name.length > length ? name.substring(0, length) + '...' : name;
};
