import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { useState, useEffect } from 'react';
import Select from 'react-select';
import axios from 'axios';

export default function Edit({ auth, data: item, kayu_list, pengelola_hutan_list = [] }) {
  const { data, setData, patch, processing, errors } = useForm({
    year: item.year || new Date().getFullYear(),
    month: item.month || new Date().getMonth() + 1,
    province_id: item.province_id || 35,
    regency_id: item.regency_id || '',
    district_id: item.district_id || '',
    pengelola_hutan_id: item.pengelola_hutan_id || '',
    volume_target: item.volume_target || '',
    details: (item.details && item.details.length > 0)
      ? item.details.map(d => ({
        kayu_id: d.kayu_id,
        volume_realization: d.volume_realization
      }))
      : [{ kayu_id: '', volume_realization: '' }],
  });

  const [regencies, setRegencies] = useState([]);
  const [districts, setDistricts] = useState([]);

  const [loadingRegencies, setLoadingRegencies] = useState(false);
  const [loadingDistricts, setLoadingDistricts] = useState(false);

  const formatLabel = (name) => {
    if (!name) return '';
    return name.toLowerCase()
      .replace('kota', 'Kota')
      .split(' ')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  };

  // Initial load: Regencies
  useEffect(() => {
    setLoadingRegencies(true);
    axios.get(route('locations.regencies', 35))
      .then(res => {
        setRegencies(res.data.map(i => ({
          value: i.id,
          label: formatLabel(i.name)
        })));
        setLoadingRegencies(false);
      })
      .catch(() => setLoadingRegencies(false));
  }, []);

  // Load Districts when Regency changes
  // Load Districts when Regency changes
  useEffect(() => {
    if (data.regency_id && data.forest_type !== 'Hutan Negara') {
      setLoadingDistricts(true);
      axios.get(route('locations.districts', data.regency_id))
        .then(res => {
          setDistricts(res.data.map(i => ({
            value: i.id,
            label: formatLabel(i.name)
          })));
          setLoadingDistricts(false);
        })
        .catch(() => setLoadingDistricts(false));
    } else {
      setDistricts([]);
    }
  }, [data.regency_id, data.forest_type]);

  const addDetail = () => {
    setData('details', [...data.details, { kayu_id: '', volume_realization: '' }]);
  };

  const removeDetail = (index) => {
    const newDetails = [...data.details];
    newDetails.splice(index, 1);
    setData('details', newDetails);
  };

  const updateDetail = (index, field, value) => {
    const newDetails = [...data.details];
    newDetails[index][field] = value;
    setData('details', newDetails);
  };

  const submit = (e) => {
    e.preventDefault();
    patch(route('hasil-hutan-kayu.update', item.id));
  };

  const selectStyles = {
    control: (base, state) => ({
      ...base,
      borderRadius: '0.75rem',
      padding: '2px',
      backgroundColor: state.isDisabled ? '#f3f4f6' : '#f9fafb',
      borderColor: state.isDisabled ? '#f3f4f6' : '#e5e7eb',
      boxShadow: state.isFocused ? '0 0 0 1px #10b981' : 'none',
      '&:hover': {
        borderColor: state.isDisabled ? '#f3f4f6' : '#10b981',
      },
      cursor: state.isDisabled ? 'not-allowed' : 'default',
      transition: 'all 0.2s',
    }),
    singleValue: (base, state) => ({
      ...base,
      color: state.isDisabled ? '#9ca3af' : '#111827',
      fontWeight: '500',
      fontSize: '0.875rem',
    }),
    placeholder: (base) => ({
      ...base,
      color: '#9ca3af',
      fontSize: '0.875rem',
    }),
    dropdownIndicator: (base, state) => ({
      ...base,
      color: state.isDisabled ? '#d1d5db' : '#9ca3af',
      '&:hover': {
        color: state.isDisabled ? '#d1d5db' : '#10b981',
      }
    }),
    indicatorSeparator: () => ({ display: 'none' }),
    menu: (base) => ({
      ...base,
      borderRadius: '1rem',
      boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
      padding: '4px',
      border: '1px solid #e5e7eb',
      zIndex: 9999,
    }),
    menuList: (base) => ({
      ...base,
      padding: '4px',
      maxHeight: '250px',
      '&::-webkit-scrollbar': {
        width: '6px',
      },
      '&::-webkit-scrollbar-track': {
        background: 'transparent',
      },
      '&::-webkit-scrollbar-thumb': {
        background: '#e5e7eb',
        borderRadius: '10px',
      },
      '&::-webkit-scrollbar-thumb:hover': {
        background: '#d1d5db',
      },
    }),
    option: (base, state) => ({
      ...base,
      borderRadius: '0.5rem',
      padding: '8px 12px',
      fontSize: '0.875rem',
      fontWeight: '500',
      backgroundColor: state.isSelected
        ? '#10b981'
        : state.isFocused
          ? '#ecfdf5'
          : 'transparent',
      color: state.isSelected
        ? 'white'
        : state.isFocused
          ? '#10b981'
          : '#374151',
      '&:active': {
        backgroundColor: '#10b981',
        color: 'white',
      },
      cursor: 'pointer',
    }),
  };

  const kayuOptions = kayu_list.map(k => ({ value: k.id, label: k.name }));

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Edit Data Produksi dari {data.forest_type}</h2>}
    >
      <Head title={`Edit Produksi dari ${data.forest_type} - Hasil Hutan Kayu`} />

      <div className="max-w-4xl mx-auto">
        <Link
          href={route('hasil-hutan-kayu.index', { forest_type: data.forest_type })}
          className="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-800 transition-colors mb-6 group"
        >
          <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 transform group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Kembali ke Daftar Data
        </Link>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="p-8 border-b border-gray-100 bg-gray-50/50">
            <h3 className="text-xl font-bold text-gray-900">Formulir Edit Data</h3>
            <p className="mt-1 text-sm text-gray-500">
              Perbarui informasi hasil hutan kayu di bawah ini.
            </p>
          </div>

          <div className="p-8">
            <form onSubmit={submit} className="space-y-8">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <div className="md:col-span-2">
                  <h4 className="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">Informasi Waktu & Lokasi</h4>
                </div>

                <div>
                  <InputLabel htmlFor="year" value="Tahun Laporan" className="text-gray-700 font-bold mb-2" />
                  <TextInput
                    id="year"
                    type="number"
                    className="w-full"
                    value={data.year}
                    onChange={(e) => setData('year', e.target.value)}
                    required
                  />
                  <InputError message={errors.year} className="mt-2" />
                </div>

                <div>
                  <InputLabel htmlFor="month" value="Bulan Laporan" className="text-gray-700 font-bold mb-2" />
                  <select
                    id="month"
                    className="w-full bg-gray-50 border-gray-200 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm transition-all duration-200 py-[11px] text-sm disabled:bg-gray-100/50 disabled:text-gray-400 disabled:border-gray-100 disabled:cursor-not-allowed"
                    value={data.month}
                    onChange={(e) => setData('month', e.target.value)}
                  >
                    {[...Array(12)].map((_, i) => (
                      <option key={i + 1} value={i + 1}>{new Date(0, i).toLocaleString('id-ID', { month: 'long' })}</option>
                    ))}
                  </select>
                  <InputError message={errors.month} className="mt-2" />
                </div>

                {/* Location Selects */}
                <div>
                  <InputLabel value="Provinsi" className="text-gray-700 font-bold mb-2" />
                  <Select
                    isDisabled
                    value={{ value: 35, label: 'JAWA TIMUR' }}
                    styles={selectStyles}
                  />
                </div>

                <div>
                  <InputLabel value="Kabupaten" className="text-gray-700 font-bold mb-2" />
                  <Select
                    options={regencies}
                    isLoading={loadingRegencies}
                    value={regencies.find(r => r.value == data.regency_id) || (item.regency ? { value: item.regency_id, label: formatLabel(item.regency.name) } : null)}
                    onChange={(opt) => {
                      setData((prev) => ({
                        ...prev,
                        regency_id: opt?.value || '',
                        district_id: '',
                      }));
                    }}
                    placeholder="Pilih Kabupaten..."
                    styles={selectStyles}
                    isClearable
                  />
                  <InputError message={errors.regency_id} className="mt-2" />
                </div>

                {data.forest_type !== 'Hutan Negara' && (
                  <div>
                    <InputLabel value="Kecamatan" className="text-gray-700 font-bold mb-2" />
                    <Select
                      options={districts}
                      isLoading={loadingDistricts}
                      isDisabled={!data.regency_id}
                      value={districts.find(d => d.value == data.district_id) || (item.district ? { value: item.district_id, label: formatLabel(item.district.name) } : null)}
                      onChange={(opt) => {
                        setData((prev) => ({
                          ...prev,
                          district_id: opt?.value || '',
                        }));
                      }}
                      placeholder="Pilih Kecamatan..."
                      styles={selectStyles}
                      isClearable
                    />
                    <InputError message={errors.district_id} className="mt-2" />
                  </div>
                )}

                {data.forest_type === 'Hutan Negara' && (
                  <div>
                    <InputLabel value="Pengelola Hutan" className="text-gray-700 font-bold mb-2" />
                    <Select
                      options={pengelola_hutan_list.map(p => ({ value: p.id, label: p.name }))}
                      value={pengelola_hutan_list.map(p => ({ value: p.id, label: p.name })).find(p => p.value === data.pengelola_hutan_id) || (item.pengelolaHutan ? { value: item.pengelola_hutan_id, label: item.pengelolaHutan.name } : null)}
                      onChange={(opt) => {
                        setData((prev) => ({
                          ...prev,
                          pengelola_hutan_id: opt?.value || '',
                        }));
                      }}
                      placeholder="Pilih Pengelola Hutan..."
                      styles={selectStyles}
                      isClearable
                    />
                    <InputError message={errors.pengelola_hutan_id} className="mt-2" />
                  </div>
                )}

                <div className="md:col-span-2 mt-4">
                  <h4 className="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">Target Produksi</h4>
                </div>

                <div className="md:col-span-2">
                  <InputLabel htmlFor="volume_target" value="Target Volume (m³)" className="text-gray-700 font-bold mb-2" />
                  <TextInput
                    id="volume_target"
                    type="number"
                    step="0.01"
                    className="w-full md:w-1/2"
                    value={data.volume_target}
                    onChange={(e) => setData('volume_target', e.target.value)}
                    placeholder="0.00"
                    required
                  />
                  <InputError message={errors.volume_target} className="mt-2" />
                  <p className="mt-1 text-xs text-gray-400 font-medium">Target volume total untuk periode dan lokasi yang dipilih.</p>
                </div>

                <div className="md:col-span-2 mt-4">
                  <div className="flex items-center justify-between border-b border-emerald-100 pb-2 mb-4">
                    <h4 className="text-xs font-bold text-emerald-600 uppercase tracking-widest">Detail Realisasi Kayu</h4>
                    <button
                      type="button"
                      onClick={addDetail}
                      className="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg hover:bg-emerald-100 transition-colors border border-emerald-200/50"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M12 4v16m8-8H4" />
                      </svg>
                      Tambah Detail
                    </button>
                  </div>

                  <div className="space-y-4">
                    {data.details.map((detail, index) => (
                      <div key={index} className="bg-gray-50/50 p-5 rounded-2xl border border-gray-100 relative group hover:border-emerald-200 transition-all duration-300">
                        {data.details.length > 1 && (
                          <button
                            type="button"
                            onClick={() => removeDetail(index)}
                            className="absolute -top-2 -right-2 bg-white text-red-400 hover:text-red-600 p-1.5 rounded-full shadow-sm border border-red-50 hover:border-red-100 transition-all opacity-0 group-hover:opacity-100"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                              <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                            </svg>
                          </button>
                        )}

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                          <div>
                            <InputLabel value="Jenis Kayu" className="text-xs font-bold text-gray-500 mb-1.5 ml-1" />
                            <Select
                              options={kayuOptions}
                              value={kayuOptions.find(opt => opt.value === detail.kayu_id) || null}
                              onChange={(opt) => updateDetail(index, 'kayu_id', opt?.value)}
                              placeholder="Pilih Jenis Kayu..."
                              styles={selectStyles}
                              menuPlacement="auto"
                            />
                            <InputError message={errors[`details.${index}.kayu_id`]} className="mt-1" />
                          </div>

                          <div>
                            <InputLabel value="Realisasi (m³)" className="text-xs font-bold text-gray-500 mb-1.5 ml-1" />
                            <TextInput
                              type="number"
                              step="0.01"
                              className="w-full"
                              value={detail.volume_realization}
                              onChange={(e) => updateDetail(index, 'volume_realization', e.target.value)}
                              placeholder="0.00"
                            />
                            <InputError message={errors[`details.${index}.volume_realization`]} className="mt-1" />
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                  <InputError message={errors.details} className="mt-2" />
                </div>
              </div>

              <div className="pt-8 border-t border-gray-100 flex items-center justify-end gap-4 overflow-hidden">
                <Link
                  href={route('hasil-hutan-kayu.index', { forest_type: data.forest_type })}
                  className="px-6 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors"
                >
                  Batalkan
                </Link>
                <PrimaryButton
                  className="px-10 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white rounded-xl font-bold shadow-lg shadow-emerald-900/10 hover:shadow-emerald-900/20 transition-all transform active:scale-95 flex items-center gap-2"
                  loading={processing}
                >
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                  {processing ? 'Sedang Memperbarui...' : 'Perbarui Laporan'}
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
