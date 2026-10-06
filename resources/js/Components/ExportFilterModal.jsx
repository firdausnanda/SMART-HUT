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
const emptyExportColumns = [];
const fundSourceLabels = { apbn: 'APBN', apbd: 'APBD', swasta: 'Swasta', swadaya: 'Swadaya Masyarakat', other: 'Lainnya' };

export default function ExportFilterModal({ show, onClose, onExport, year, years = [], defaultStatus = 'all', commodities = [], managers = [], fundSources = [], psManagers = [], buildingTypes = [], cdks = [], exportColumns = emptyExportColumns, showYear = true, showMonth = true, showLocation = true, showDistrict = true }) {
  const [filters, setFilters] = useState({ year: year ?? '', month: '', status: defaultStatus, ...emptyLocation, commodity_id: '', pengelola_wisata_id: '', fund_source: '', pengelola_id: '', bangunan_kta_id: '', cdk_id: '' });
  const [selectedColumns, setSelectedColumns] = useState(() => exportColumns.map(column => column.key));
  const [regencies, setRegencies] = useState([]);
  const [districts, setDistricts] = useState([]);
  const [loadingRegencies, setLoadingRegencies] = useState(false);
  const [loadingDistricts, setLoadingDistricts] = useState(false);

  useEffect(() => {
    if (!show) return;
    setFilters({ year: year ?? '', month: '', status: defaultStatus, ...emptyLocation, commodity_id: '', pengelola_wisata_id: '', fund_source: '', pengelola_id: '', bangunan_kta_id: '', cdk_id: '' });
    setSelectedColumns(exportColumns.map(column => column.key));
    setDistricts([]);
    let active = true;
    if (showLocation) {
      setLoadingRegencies(true);
      axios.get(route('locations.regencies', 35))
        .then(({ data }) => { if (active) setRegencies(data); })
        .catch(() => { if (active) setRegencies([]); })
        .finally(() => { if (active) setLoadingRegencies(false); });
    }
    return () => { active = false; };
  }, [show, year, defaultStatus, exportColumns, showLocation]);

  useEffect(() => {
    if (!show || !showDistrict || !filters.regency_id) {
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
  }, [show, showDistrict, filters.regency_id]);

  const update = (key, value) => setFilters(current => ({
    ...current, [key]: value, ...(key === 'regency_id' ? { district_id: '' } : {}),
  }));
  const selectClass = 'w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500';
  const labelClass = 'mb-1 block text-sm font-semibold text-gray-700';
  const availableYears = [...new Set([year, ...years].filter(Boolean))];
  const toggleColumn = (key) => setSelectedColumns(current => {
    const selected = new Set(current);
    if (selected.has(key)) {
      selected.delete(key);
    } else {
      selected.add(key);
    }
    return exportColumns.map(column => column.key).filter(columnKey => selected.has(columnKey));
  });

  const submit = (event) => {
    event.preventDefault();
    if (exportColumns.length > 0 && selectedColumns.length === 0) return;
    onExport({
      ...Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '')),
      ...(exportColumns.length > 0 ? { columns: selectedColumns } : {}),
    });
    onClose();
  };

  return (
    <Modal show={show} onClose={onClose} maxWidth="lg">
      <form onSubmit={submit} className="flex max-h-[calc(100vh-3rem)] flex-col">
        <div className="border-b border-gray-100 px-6 py-5">
          <h3 className="text-lg font-bold text-gray-900">Filter Data Export</h3>
          <p className="mt-1 text-sm text-gray-500">Pilih data yang akan dimasukkan ke file Excel.</p>
        </div>
        <div className="overflow-y-auto">
          <div className="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2">
          {cdks.length > 0 && <div>
            <label htmlFor="export-cdk" className={labelClass}>CDK</label>
            <select id="export-cdk" className={selectClass} value={filters.cdk_id} onChange={(e) => update('cdk_id', e.target.value)}>
              <option value="">Semua CDK</option>
              {cdks.map(cdk => <option key={cdk.id} value={cdk.id}>{cdk.nama}</option>)}
            </select>
          </div>}
          {showYear && <div>
            <label htmlFor="export-year" className={labelClass}>Tahun</label>
            <select id="export-year" className={selectClass} value={filters.year} onChange={(e) => update('year', e.target.value)}>
              <option value="">Semua Tahun</option>
              {availableYears.map(value => <option key={value} value={value}>{value}</option>)}
            </select>
          </div>}
          {showMonth && <div>
            <label htmlFor="export-month" className={labelClass}>Bulan</label>
            <select id="export-month" className={selectClass} value={filters.month} onChange={(e) => update('month', e.target.value)}>
              <option value="">Semua Bulan</option>
              {Array.from({ length: 12 }, (_, index) => <option key={index + 1} value={index + 1}>{new Date(2020, index, 1).toLocaleString('id-ID', { month: 'long' })}</option>)}
            </select>
          </div>}
          <div>
            <label htmlFor="export-status" className={labelClass}>Status Verifikasi</label>
            <select id="export-status" className={selectClass} value={filters.status} onChange={(e) => update('status', e.target.value)}>
              {statusOptions.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
            </select>
          </div>
          {showLocation && <div>
            <label htmlFor="export-regency" className={labelClass}>Kabupaten/Kota</label>
            <select id="export-regency" className={selectClass} value={filters.regency_id} onChange={(e) => update('regency_id', e.target.value)} disabled={loadingRegencies}>
              <option value="">{loadingRegencies ? 'Memuat...' : 'Semua Kabupaten/Kota'}</option>
              {regencies.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          </div>}
          {showLocation && showDistrict && <div>
            <label htmlFor="export-district" className={labelClass}>Kecamatan</label>
            <select id="export-district" className={selectClass} value={filters.district_id} onChange={(e) => update('district_id', e.target.value)} disabled={!filters.regency_id || loadingDistricts}>
              <option value="">{loadingDistricts ? 'Memuat...' : 'Semua Kecamatan'}</option>
              {districts.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          </div>}
          {managers.length > 0 && <div>
            <label htmlFor="export-manager" className={labelClass}>Pengelola Wisata</label>
            <select id="export-manager" className={selectClass} value={filters.pengelola_wisata_id} onChange={(e) => update('pengelola_wisata_id', e.target.value)}>
              <option value="">Semua Pengelola Wisata</option>
              {managers.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          </div>}
          {fundSources.length > 0 && <div>
            <label htmlFor="export-fund-source" className={labelClass}>Sumber Dana</label>
            <select id="export-fund-source" className={selectClass} value={filters.fund_source} onChange={(e) => update('fund_source', e.target.value)}>
              <option value="">Semua Sumber Dana</option>
              {fundSources.map(value => <option key={value} value={value}>{fundSourceLabels[value] ?? value}</option>)}
            </select>
          </div>}
          {psManagers.length > 0 && <div>
            <label htmlFor="export-ps-manager" className={labelClass}>Pengelola</label>
            <select id="export-ps-manager" className={selectClass} value={filters.pengelola_id} onChange={(e) => update('pengelola_id', e.target.value)}>
              <option value="">Semua Pengelola</option>
              {psManagers.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          </div>}
          {buildingTypes.length > 0 && <div>
            <label htmlFor="export-building-type" className={labelClass}>Jenis Bangunan</label>
            <select id="export-building-type" className={selectClass} value={filters.bangunan_kta_id} onChange={(e) => update('bangunan_kta_id', e.target.value)}>
              <option value="">Semua Jenis Bangunan</option>
              {buildingTypes.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          </div>}
          {commodities.length > 0 && <div>
            <label htmlFor="export-commodity" className={labelClass}>Komoditas</label>
            <select id="export-commodity" className={selectClass} value={filters.commodity_id} onChange={(e) => update('commodity_id', e.target.value)}>
              <option value="">Semua Komoditas</option>
              {commodities.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          </div>}
          </div>
          {exportColumns.length > 0 && <div className="border-t border-gray-100 px-6 py-5">
          <div className="mb-3 flex items-center justify-between gap-3">
            <div>
              <h4 className="text-sm font-semibold text-gray-900">Kolom Excel</h4>
              <p className="text-xs text-gray-500">{selectedColumns.length} dari {exportColumns.length} kolom dipilih</p>
            </div>
            <button type="button" onClick={() => setSelectedColumns(selectedColumns.length === exportColumns.length ? [] : exportColumns.map(column => column.key))} className="text-sm font-semibold text-emerald-700 hover:text-emerald-900">
              {selectedColumns.length === exportColumns.length ? 'Kosongkan' : 'Pilih semua'}
            </button>
          </div>
          <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
            {exportColumns.map(column => <label key={column.key} className="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
              <input type="checkbox" checked={selectedColumns.includes(column.key)} onChange={() => toggleColumn(column.key)} className="rounded border-gray-300 text-emerald-700 focus:ring-emerald-500" />
              <span>{column.label}</span>
            </label>)}
          </div>
          {selectedColumns.length === 0 && <p className="mt-3 text-sm text-red-600">Pilih minimal satu kolom.</p>}
          </div>}
        </div>
        <div className="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
          <button type="button" onClick={onClose} className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
          <button type="submit" disabled={exportColumns.length > 0 && selectedColumns.length === 0} className="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50">Unduh Excel</button>
        </div>
      </form>
    </Modal>
  );
}
