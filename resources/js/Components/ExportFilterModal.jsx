import { useEffect, useState } from 'react';
import axios from 'axios';
import Modal from '@/Components/Modal';

const statusOptions = [
  ['all', 'Semua Status'],
  ['draft', 'Draft'],
  ['waiting_kasi', 'Menunggu Kasi'],
  ['waiting_cdk', 'Menunggu Kacabdin'],
  ['final', 'Final (Disetujui)'],
  ['rejected', 'Ditolak'],
];

const emptyLocation = { regency_id: '', district_id: '' };

export default function ExportFilterModal({ show, onClose, onExport, year, years = [], defaultStatus = 'all', commodities = [] }) {
  const [filters, setFilters] = useState({ year: year ?? '', month: '', status: defaultStatus, ...emptyLocation, commodity_id: '' });
  const [regencies, setRegencies] = useState([]);
  const [districts, setDistricts] = useState([]);
  const [loadingRegencies, setLoadingRegencies] = useState(false);
  const [loadingDistricts, setLoadingDistricts] = useState(false);

  useEffect(() => {
    if (!show) return;
    setFilters({ year: year ?? '', month: '', status: defaultStatus, ...emptyLocation, commodity_id: '' });
    setDistricts([]);
    let active = true;
    setLoadingRegencies(true);
    axios.get(route('locations.regencies', 35))
      .then(({ data }) => { if (active) setRegencies(data); })
      .catch(() => { if (active) setRegencies([]); })
      .finally(() => { if (active) setLoadingRegencies(false); });
    return () => { active = false; };
  }, [show, year, defaultStatus]);

  useEffect(() => {
    if (!show || !filters.regency_id) {
      setDistricts([]);
      return;
    }
    let active = true;
    setLoadingDistricts(true);
    axios.get(route('locations.districts', filters.regency_id))
      .then(({ data }) => { if (active) setDistricts(data); })
      .catch(() => { if (active) setDistricts([]); })
      .finally(() => { if (active) setLoadingDistricts(false); });
    return () => { active = false; };
  }, [show, filters.regency_id]);

  const update = (key, value) => setFilters(current => ({
    ...current, [key]: value, ...(key === 'regency_id' ? { district_id: '' } : {}),
  }));
  const selectClass = 'w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500';
  const labelClass = 'mb-1 block text-sm font-semibold text-gray-700';
  const availableYears = [...new Set([year, ...years].filter(Boolean))];

  const submit = (event) => {
    event.preventDefault();
    onExport(Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '')));
    onClose();
  };

  return (
    <Modal show={show} onClose={onClose} maxWidth="lg">
      <form onSubmit={submit}>
        <div className="border-b border-gray-100 px-6 py-5">
          <h3 className="text-lg font-bold text-gray-900">Filter Data Export</h3>
          <p className="mt-1 text-sm text-gray-500">Pilih data yang akan dimasukkan ke file Excel.</p>
        </div>
        <div className="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2">
          <div>
            <label htmlFor="export-year" className={labelClass}>Tahun</label>
            <select id="export-year" className={selectClass} value={filters.year} onChange={(e) => update('year', e.target.value)}>
              <option value="">Semua Tahun</option>
              {availableYears.map(value => <option key={value} value={value}>{value}</option>)}
            </select>
          </div>
          <div>
            <label htmlFor="export-month" className={labelClass}>Bulan</label>
            <select id="export-month" className={selectClass} value={filters.month} onChange={(e) => update('month', e.target.value)}>
              <option value="">Semua Bulan</option>
              {Array.from({ length: 12 }, (_, index) => <option key={index + 1} value={index + 1}>{new Date(2020, index, 1).toLocaleString('id-ID', { month: 'long' })}</option>)}
            </select>
          </div>
          <div>
            <label htmlFor="export-status" className={labelClass}>Status Verifikasi</label>
            <select id="export-status" className={selectClass} value={filters.status} onChange={(e) => update('status', e.target.value)}>
              {statusOptions.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
            </select>
          </div>
          <div>
            <label htmlFor="export-regency" className={labelClass}>Kabupaten/Kota</label>
            <select id="export-regency" className={selectClass} value={filters.regency_id} onChange={(e) => update('regency_id', e.target.value)} disabled={loadingRegencies}>
              <option value="">{loadingRegencies ? 'Memuat...' : 'Semua Kabupaten/Kota'}</option>
              {regencies.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          </div>
          <div>
            <label htmlFor="export-district" className={labelClass}>Kecamatan</label>
            <select id="export-district" className={selectClass} value={filters.district_id} onChange={(e) => update('district_id', e.target.value)} disabled={!filters.regency_id || loadingDistricts}>
              <option value="">{loadingDistricts ? 'Memuat...' : 'Semua Kecamatan'}</option>
              {districts.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          </div>
          {commodities.length > 0 && <div>
            <label htmlFor="export-commodity" className={labelClass}>Komoditas</label>
            <select id="export-commodity" className={selectClass} value={filters.commodity_id} onChange={(e) => update('commodity_id', e.target.value)}>
              <option value="">Semua Komoditas</option>
              {commodities.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          </div>}
        </div>
        <div className="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
          <button type="button" onClick={onClose} className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
          <button type="submit" className="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Unduh Excel</button>
        </div>
      </form>
    </Modal>
  );
}
