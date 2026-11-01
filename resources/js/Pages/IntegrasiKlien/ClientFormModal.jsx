import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

export default function ClientFormModal({ show, onClose, client, cdks }) {
  const isEdit = Boolean(client);
  const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
    kode: '',
    nama: '',
    deskripsi: '',
    cdk_id: '',
    is_active: true,
  });

  useEffect(() => {
    if (!show) return;
    if (client) {
      setData({
        kode: client.kode,
        nama: client.nama,
        deskripsi: client.deskripsi || '',
        cdk_id: client.cdk_id ?? '',
        is_active: client.is_active,
      });
    } else {
      reset();
    }
    clearErrors();
  }, [show, client]);

  const handleSubmit = (e) => {
    e.preventDefault();
    const options = { preserveScroll: true, onSuccess: () => onClose() };
    if (isEdit) {
      put(route('integrasi-klien.update', client.id), options);
    } else {
      post(route('integrasi-klien.store'), options);
    }
  };

  return (
    <Modal show={show} onClose={onClose}>
      <div className="p-6">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-bold text-gray-900">
            {isEdit ? 'Edit Klien Integrasi' : 'Tambah Klien Integrasi'}
          </h2>
          <button type="button" onClick={onClose} className="text-gray-400 hover:text-gray-500 transition-colors" aria-label="Tutup">
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <InputLabel htmlFor="nama" value="Nama Sistem" />
              <TextInput
                id="nama"
                className="mt-1 block w-full"
                value={data.nama}
                onChange={(e) => setData('nama', e.target.value)}
                placeholder="Contoh: SIPETAK"
                required
                isFocused={!isEdit}
              />
              <InputError message={errors.nama} className="mt-2" />
            </div>
            <div>
              <InputLabel htmlFor="kode" value="Kode Klien" />
              <TextInput
                id="kode"
                className="mt-1 block w-full font-mono"
                value={data.kode}
                onChange={(e) => setData('kode', e.target.value.toLowerCase())}
                placeholder="Contoh: cdk-trenggalek"
                required
              />
              <p className="mt-1 text-xs text-gray-500">Huruf kecil, angka, dan tanda hubung.</p>
              <InputError message={errors.kode} className="mt-2" />
            </div>
          </div>

          <div>
            <InputLabel htmlFor="cdk_id" value="Cakupan Data" />
            <select
              id="cdk_id"
              className="mt-1 block w-full border-gray-200 bg-gray-50 text-gray-900 focus:bg-white focus:border-primary-600 focus:ring-primary-600 rounded-xl shadow-sm text-sm"
              value={data.cdk_id}
              onChange={(e) => setData('cdk_id', e.target.value === '' ? '' : Number(e.target.value))}
            >
              <option value="">Seluruh Provinsi Jawa Timur</option>
              {cdks.map((cdk) => (
                <option key={cdk.id} value={cdk.id}>{cdk.nama}</option>
              ))}
            </select>
            <p className="mt-1 text-xs text-gray-500">
              Jika memilih CDK, klien hanya bisa membaca data CDK tersebut. Data yang dikirim selalu berstatus final.
            </p>
            <InputError message={errors.cdk_id} className="mt-2" />
          </div>

          <div>
            <InputLabel htmlFor="deskripsi" value="Keterangan" />
            <textarea
              id="deskripsi"
              rows="3"
              className="mt-1 block w-full border-gray-200 bg-gray-50 text-gray-900 focus:bg-white focus:border-primary-600 focus:ring-primary-600 rounded-xl shadow-sm text-sm"
              value={data.deskripsi}
              onChange={(e) => setData('deskripsi', e.target.value)}
              placeholder="Pengelola sistem, kontak teknis, atau tujuan integrasi"
            />
            <InputError message={errors.deskripsi} className="mt-2" />
          </div>

          <div className="flex items-start">
            <input
              id="is_active"
              type="checkbox"
              className="mt-0.5 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 h-4 w-4"
              checked={data.is_active}
              onChange={(e) => setData('is_active', e.target.checked)}
            />
            <label htmlFor="is_active" className="ml-2 text-sm text-gray-900">
              <span className="font-medium">Klien aktif</span>
              <span className="block text-xs text-gray-500">Jika dinonaktifkan, semua token klien ditolak tanpa dicabut.</span>
            </label>
          </div>

          <div className="flex justify-end gap-3 pt-2">
            <SecondaryButton type="button" onClick={onClose} className="rounded-xl px-6 py-3 h-[48px] shadow-sm font-medium">
              Batal
            </SecondaryButton>
            <PrimaryButton disabled={processing} className="bg-primary-600 hover:bg-primary-700 text-white h-[48px]">
              {isEdit ? 'Perbarui' : 'Simpan'}
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  );
}
