import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Select from 'react-select';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

export default function FormModal({ show, onClose, mode, cdk, regencies }) {
  const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
    kode: '',
    nama: '',
    kepala_nama: '',
    alamat: '',
    is_active: true,
    regencies: [],
  });

  useEffect(() => {
    if (show) {
      if (mode === 'edit' && cdk) {
        setData({
          kode: cdk.kode || '',
          nama: cdk.nama || '',
          kepala_nama: cdk.kepala_nama || '',
          alamat: cdk.alamat || '',
          is_active: cdk.is_active ?? true,
          regencies: cdk.regencies ? cdk.regencies.map(r => r.id) : [],
        });
      } else {
        reset();
      }
      clearErrors();
    }
  }, [show, mode, cdk]);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (mode === 'create') {
      post(route('cdks.store'), {
        onSuccess: () => onClose(),
      });
    } else {
      put(route('cdks.update', cdk.id), {
        onSuccess: () => onClose(),
      });
    }
  };

  const selectOptions = regencies.map(r => ({ value: r.id, label: r.name }));
  const selectedValues = selectOptions.filter(opt => data.regencies.includes(opt.value));

  return (
    <Modal show={show} onClose={onClose}>
      <div className="p-6">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-bold text-gray-900">
            {mode === 'create' ? 'Tambah Cabang Dinas Kehutanan' : 'Edit Cabang Dinas Kehutanan'}
          </h2>
          <button type="button" onClick={onClose} className="text-gray-400 hover:text-gray-500 transition-colors">
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">
          <div>
            <InputLabel htmlFor="kode" value="Kode CDK" />
            <TextInput
              id="kode"
              type="text"
              className="mt-1 block w-full"
              value={data.kode}
              onChange={(e) => setData('kode', e.target.value)}
              placeholder="Contoh: CDK-MLG"
              required
              isFocused={mode === 'create'}
            />
            <InputError message={errors.kode} className="mt-2" />
          </div>

          <div>
            <InputLabel htmlFor="nama" value="Nama CDK" />
            <TextInput
              id="nama"
              type="text"
              className="mt-1 block w-full"
              value={data.nama}
              onChange={(e) => setData('nama', e.target.value)}
              placeholder="Contoh: CDK Wilayah Malang"
              required
            />
            <InputError message={errors.nama} className="mt-2" />
          </div>

          <div>
            <InputLabel htmlFor="kepala_nama" value="Nama Kepala CDK" />
            <TextInput
              id="kepala_nama"
              type="text"
              className="mt-1 block w-full"
              value={data.kepala_nama}
              onChange={(e) => setData('kepala_nama', e.target.value)}
              placeholder="Contoh: Ir. Budi Santoso, M.P"
            />
            <InputError message={errors.kepala_nama} className="mt-2" />
          </div>

          <div>
            <InputLabel htmlFor="alamat" value="Alamat Kantor" />
            <textarea
              id="alamat"
              rows="3"
              className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
              value={data.alamat}
              onChange={(e) => setData('alamat', e.target.value)}
              placeholder="Masukkan alamat lengkap kantor..."
            />
            <InputError message={errors.alamat} className="mt-2" />
          </div>

          <div>
            <InputLabel htmlFor="regencies" value="Wilayah Kerja (Kabupaten/Kota)" />
            <div className="mt-1">
              <Select
                id="regencies"
                isMulti
                options={selectOptions}
                value={selectedValues}
                onChange={(options) => setData('regencies', options ? options.map(opt => opt.value) : [])}
                placeholder="Pilih Kabupaten/Kota..."
                classNamePrefix="react-select"
                menuPortalTarget={document.body}
                menuPlacement="top"
                styles={{
                  control: (base, state) => ({
                    ...base,
                    borderRadius: '0.375rem',
                    borderColor: state.isFocused ? '#6366f1' : '#d1d5db',
                    boxShadow: state.isFocused ? '0 0 0 1px #6366f1' : 'none',
                    '&:hover': {
                      borderColor: '#9ca3af'
                    }
                  }),
                  menuPortal: (base) => ({ ...base, zIndex: 9999 })
                }}
              />
            </div>
            <InputError message={errors.regencies} className="mt-2" />
          </div>

          <div className="flex items-center">
            <input
              id="is_active"
              type="checkbox"
              className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-4 w-4"
              checked={data.is_active}
              onChange={(e) => setData('is_active', e.target.checked)}
            />
            <label htmlFor="is_active" className="ml-2 block text-sm text-gray-900 font-medium">
              CDK Aktif
            </label>
            <InputError message={errors.is_active} className="mt-2" />
          </div>

          <div className="flex justify-end gap-3 mt-8">
            <SecondaryButton type="button" onClick={onClose} className="rounded-xl px-6 py-3 h-[48px] transition-all duration-200 shadow-sm font-medium">
              Batal
            </SecondaryButton>
            <PrimaryButton disabled={processing} className="bg-primary-600 hover:bg-primary-700 text-white shadow-primary-200 h-[48px]">
              {mode === 'create' ? 'Simpan' : 'Perbarui'}
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  );
}
