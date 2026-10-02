import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import Select from 'react-select';

export default function Edit({ auth, data: item, pengelolaWisata }) {
  const { data, setData, patch, processing, errors } = useForm({
    year: item.year || new Date().getFullYear(),
    month: item.month || new Date().getMonth() + 1,
    id_pengelola_wisata: item.id_pengelola_wisata || '',
    number_of_visitors: item.number_of_visitors || '',
    gross_income: item.gross_income || '',
  });

  const submit = (e) => {
    e.preventDefault();
    patch(route('pengunjung-wisata.update', item.id));
  };

  const selectStyles = {
    control: (base, state) => ({
      ...base,
      borderRadius: '0.75rem',
      padding: '2px',
      backgroundColor: state.isDisabled ? '#f3f4f6' : '#f9fafb',
      borderColor: state.isDisabled ? '#f3f4f6' : '#e5e7eb',
      boxShadow: state.isFocused ? '0 0 0 1px #16a34a' : 'none',
      '&:hover': {
        borderColor: state.isDisabled ? '#f3f4f6' : '#16a34a',
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
        color: state.isDisabled ? '#d1d5db' : '#16a34a',
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
        ? '#16a34a'
        : state.isFocused
          ? '#f0fdf4'
          : 'transparent',
      color: state.isSelected
        ? 'white'
        : state.isFocused
          ? '#16a34a'
          : '#374151',
      '&:active': {
        backgroundColor: '#16a34a',
        color: 'white',
      },
      cursor: 'pointer',
    }),
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Edit Data</h2>}
    >
      <Head title="Edit Pengunjung Wisata" />

      <div className="max-w-4xl mx-auto">
        <Link
          href={route('pengunjung-wisata.index')}
          className="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:text-primary-800 transition-colors mb-6 group"
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
              Perbarui informasi di bawah ini.
            </p>
          </div>

          <div className="p-8">
            <form onSubmit={submit} className="space-y-8">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <div className="md:col-span-2">
                  <h4 className="text-xs font-bold text-primary-600 uppercase tracking-widest mb-4 border-b border-primary-100 pb-2">Informasi Waktu & Lokasi</h4>
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
                    className="w-full bg-gray-50 border-gray-200 focus:bg-white focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm transition-all duration-200 py-[11px] text-sm disabled:bg-gray-100/50 disabled:text-gray-400 disabled:border-gray-100 disabled:cursor-not-allowed"
                    value={data.month}
                    onChange={(e) => setData('month', e.target.value)}
                  >
                    {[...Array(12)].map((_, i) => (
                      <option key={i + 1} value={i + 1}>{new Date(0, i).toLocaleString('id-ID', { month: 'long' })}</option>
                    ))}
                  </select>
                  <InputError message={errors.month} className="mt-2" />
                </div>

                <div className="md:col-span-2">
                  <InputLabel htmlFor="id_pengelola_wisata" value="Pengelola Wisata" className="text-gray-700 font-bold mb-2" />
                  <Select
                    options={pengelolaWisata.map(pw => ({ value: pw.id, label: pw.name }))}
                    onChange={(opt) => setData('id_pengelola_wisata', opt?.value || '')}
                    placeholder="Pilih Pengelola Wisata..."
                    styles={selectStyles}
                    isClearable
                    value={(() => {
                      const id = data.id_pengelola_wisata;
                      const found = pengelolaWisata.find(pw => pw.id == id);
                      if (found) return { value: found.id, label: found.name };
                      if (id == item.id_pengelola_wisata) {
                        const rel = item.pengelolaWisata || item.pengelola_wisata;
                        if (rel) return { value: rel.id, label: rel.name };
                      }
                      return null;
                    })()}
                  />
                  <InputError message={errors.id_pengelola_wisata} className="mt-2" />
                </div>

                <div className="md:col-span-2 mt-4">
                  <h4 className="text-xs font-bold text-primary-600 uppercase tracking-widest mb-4 border-b border-primary-100 pb-2">Data Kunjungan & Pendapatan</h4>
                </div>

                <div>
                  <InputLabel htmlFor="number_of_visitors" value="Jumlah Pengunjung" className="text-gray-700 font-bold mb-2" />
                  <div className="relative">
                    <TextInput
                      id="number_of_visitors"
                      type="text"
                      className="w-full pr-12"
                      value={data.number_of_visitors ? data.number_of_visitors.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : ''}
                      onChange={(e) => {
                        const val = e.target.value.replace(/\D/g, '');
                        setData('number_of_visitors', val);
                      }}
                      required
                    />
                    <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-gray-400 text-xs font-bold">Orang</div>
                  </div>
                  <InputError message={errors.number_of_visitors} className="mt-2" />
                </div>

                <div>
                  <InputLabel htmlFor="gross_income" value="Pendapatan Bruto" className="text-gray-700 font-bold mb-2" />
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-gray-400 text-sm font-bold">Rp</div>
                    <TextInput
                      id="gross_income"
                      type="text"
                      className="w-full pl-12"
                      value={(data.gross_income !== undefined && data.gross_income !== null) ? (() => {
                        let str = data.gross_income.toString();
                        let parts = str.split('.');
                        let intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                        return parts.length > 1 ? `${intPart},${parts[1]}` : intPart;
                      })() : ''}
                      onChange={(e) => {
                        let val = e.target.value;
                        // Remove dots (thousands separator)
                        val = val.replace(/\./g, '');
                        // Replace comma with dot (decimal)
                        val = val.replace(/,/g, '.');
                        // Allow digits and one dot
                        if (/^\d*\.?\d*$/.test(val)) {
                          setData('gross_income', val);
                        }
                      }}
                      required
                    />
                  </div>
                  <InputError message={errors.gross_income} className="mt-2" />
                </div>
              </div>

              <div className="pt-6 border-t border-gray-100 flex items-center justify-end gap-4">
                <Link
                  href={route('pengunjung-wisata.index')}
                  className="px-6 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors"
                >
                  Batal
                </Link>
                <PrimaryButton
                  className="px-8 py-3 bg-gradient-to-r from-emerald-700 to-green-800 hover:from-emerald-600 hover:to-green-700 text-white rounded-xl font-bold shadow-lg shadow-emerald-900/20 transition-all transform active:scale-95"
                  loading={processing}
                >
                  {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
