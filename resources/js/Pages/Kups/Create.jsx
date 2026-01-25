import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { useState, useEffect } from 'react';
import Select from 'react-select';
import axios from 'axios';

export default function Create({ auth }) {
  const { data, setData, post, processing, errors } = useForm({
    province_id: 35, // JAWA TIMUR
    regency_id: '',
    district_id: '',
    nama_kups: '',
    category: '',
    number_of_kups: '',
    commodity: '',
  });

  const [regencies, setRegencies] = useState([]);
  const [districts, setDistricts] = useState([]);
  const [loadingRegencies, setLoadingRegencies] = useState(false);
  const [loadingDistricts, setLoadingDistricts] = useState(false);

  const categories = [
    { value: 'Blue', label: 'Blue' },
    { value: 'Silver', label: 'Silver' },
    { value: 'Gold', label: 'Gold' },
    { value: 'Platinum', label: 'Platinum' },
  ];

  const formatLabel = (name) => {
    if (!name) return '';
    return name.toLowerCase()
      .replace('kabupaten', 'Kab.')
      .replace('kota', 'Kota')
      .split(' ')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  };

  // Initial load: Regencies for Jatim (35)
  useEffect(() => {
    setLoadingRegencies(true);
    axios.get(route('locations.regencies', 35))
      .then(res => {
        setRegencies(res.data.map(item => ({
          value: item.id,
          label: formatLabel(item.name)
        })));
        setLoadingRegencies(false);
      })
      .catch(() => setLoadingRegencies(false));
  }, []);

  // Load Districts when Regency changes
  useEffect(() => {
    if (data.regency_id) {
      setLoadingDistricts(true);
      axios.get(route('locations.districts', data.regency_id))
        .then(res => {
          setDistricts(res.data.map(item => ({
            value: item.id,
            label: formatLabel(item.name)
          })));
          setLoadingDistricts(false);
        })
        .catch(() => setLoadingDistricts(false));
    } else {
      setDistricts([]);
    }
  }, [data.regency_id]);

  const submit = (e) => {
    e.preventDefault();
    post(route('kups.store'));
  };

  const selectStyles = {
    control: (base, state) => ({
      ...base,
      borderRadius: '0.75rem',
      padding: '2px',
      backgroundColor: state.isDisabled ? '#f3f4f6' : '#f9fafb',
      borderColor: state.isDisabled ? '#f3f4f6' : '#e5e7eb',
      boxShadow: state.isFocused ? '0 0 0 1px #10b981' : 'none', // Emerald-500
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
    menu: (base) => ({
      ...base,
      borderRadius: '1rem',
      boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
      padding: '4px',
      border: '1px solid #e5e7eb',
      zIndex: 9999,
    }),
    option: (base, state) => ({
      ...base,
      borderRadius: '0.5rem',
      padding: '8px 12px',
      fontSize: '0.875rem',
      fontWeight: '500',
      backgroundColor: state.isSelected ? '#10b981' : state.isFocused ? '#ecfdf5' : 'transparent',
      color: state.isSelected ? 'white' : state.isFocused ? '#059669' : '#374151',
      cursor: 'pointer',
    }),
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Input Data Baru</h2>}
    >
      <Head title="Input Data KUPS" />

      <div className="max-w-4xl mx-auto">
        <Link
          href={route('kups.index')}
          className="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-800 transition-colors mb-6 group"
        >
          <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 transform group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Kembali ke Daftar Data
        </Link>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="p-8 border-b border-gray-100 bg-gray-50/50">
            <h3 className="text-xl font-bold text-gray-900">Formulir Input Data</h3>
            <p className="mt-1 text-sm text-gray-500">
              Lengkapi informasi di bawah ini untuk menambahkan data Perkembangan KUPS.
            </p>
          </div>

          <div className="p-8">
            <form onSubmit={submit} className="space-y-8">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <div className="md:col-span-2">
                  <h4 className="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">Informasi Lokasi</h4>
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

                <div>
                  <InputLabel value="Kecamatan" className="text-gray-700 font-bold mb-2" />
                  <Select
                    options={districts}
                    isLoading={loadingDistricts}
                    isDisabled={!data.regency_id}
                    onChange={(opt) => {
                      setData((prev) => ({
                        ...prev,
                        district_id: opt?.value || '',
                      }));
                    }}
                    placeholder="Pilih Kecamatan..."
                    styles={selectStyles}
                    isClearable
                    value={districts.find(d => d.value === data.district_id) || null}
                  />
                  <InputError message={errors.district_id} className="mt-2" />
                </div>

                <div className="md:col-span-2 mt-4">
                  <h4 className="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">Detail KUPS</h4>
                </div>

                <div className="md:col-span-2">
                  <InputLabel htmlFor="nama_kups" value="Nama KUPS" className="text-gray-700 font-bold mb-2" />
                  <TextInput
                    id="nama_kups"
                    type="text"
                    className="w-full focus:border-emerald-500 focus:ring-emerald-500 rounded-xl"
                    value={data.nama_kups}
                    onChange={(e) => setData('nama_kups', e.target.value)}
                    required
                    placeholder="Masukkan Nama KUPS"
                  />
                  <InputError message={errors.nama_kups} className="mt-2" />
                </div>

                <div>
                  <InputLabel htmlFor="category" value="Kategori KUPS" className="text-gray-700 font-bold mb-2" />
                  <Select
                    options={categories}
                    onChange={(opt) => setData('category', opt?.value || '')}
                    placeholder="Pilih Kategori..."
                    styles={selectStyles}
                    isClearable
                    value={categories.find(c => c.value === data.category) || null}
                  />
                  <InputError message={errors.category} className="mt-2" />
                </div>

                <div>
                  <InputLabel htmlFor="number_of_kups" value="Jumlah KUPS" className="text-gray-700 font-bold mb-2" />
                  <div className="relative">
                    <TextInput
                      id="number_of_kups"
                      type="number"
                      className="w-full pr-12 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl"
                      value={data.number_of_kups}
                      onChange={(e) => setData('number_of_kups', e.target.value)}
                      required
                      placeholder="0"
                    />
                    <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-gray-400 text-xs font-bold">Unit</div>
                  </div>
                  <InputError message={errors.number_of_kups} className="mt-2" />
                </div>

                <div className="md:col-span-2">
                  <InputLabel htmlFor="commodity" value="Komoditas" className="text-gray-700 font-bold mb-2" />
                  <TextInput
                    id="commodity"
                    type="text"
                    className="w-full focus:border-emerald-500 focus:ring-emerald-500 rounded-xl"
                    value={data.commodity}
                    onChange={(e) => setData('commodity', e.target.value)}
                    required
                    placeholder="Masukkan Komoditas (contoh: Kopi, Madu, dll)"
                  />
                  <InputError message={errors.commodity} className="mt-2" />
                </div>
              </div>

              <div className="pt-6 border-t border-gray-100 flex items-center justify-end gap-4">
                <Link
                  href={route('kups.index')}
                  className="px-6 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors"
                >
                  Batal
                </Link>
                <PrimaryButton
                  className="px-8 py-3 bg-gradient-to-r from-emerald-700 to-green-800 hover:from-emerald-600 hover:to-green-700 text-white rounded-xl font-bold shadow-lg shadow-emerald-900/20 transition-all transform active:scale-95"
                  loading={processing}
                >
                  {processing ? 'Menyimpan...' : 'Simpan Laporan'}
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
