import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

const EXPIRY_OPTIONS = [
  { value: 30, label: '30 hari' },
  { value: 90, label: '90 hari' },
  { value: 180, label: '6 bulan' },
  { value: 365, label: '1 tahun' },
  { value: '', label: 'Tidak kedaluwarsa' },
];

export default function TokenFormModal({ show, onClose, client, abilities }) {
  const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
    name: 'produksi',
    abilities: [],
    expires_in_days: 365,
  });

  useEffect(() => {
    if (show) {
      reset();
      clearErrors();
    }
  }, [show]);

  const toggleAbility = (value) => {
    setData('abilities', data.abilities.includes(value)
      ? data.abilities.filter((a) => a !== value)
      : [...data.abilities, value]);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('integrasi-klien.tokens.store', client.id), {
      preserveScroll: true,
      onSuccess: () => onClose(),
    });
  };

  const abilityError = errors.abilities || Object.entries(errors).find(([key]) => key.startsWith('abilities.'))?.[1];

  return (
    <Modal show={show} onClose={onClose}>
      <div className="p-6">
        <div className="flex items-center justify-between mb-1">
          <h2 className="text-xl font-bold text-gray-900">Terbitkan Token</h2>
          <button type="button" onClick={onClose} className="text-gray-400 hover:text-gray-500 transition-colors" aria-label="Tutup">
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <p className="text-sm text-gray-500 mb-6">
          Untuk <span className="font-semibold text-gray-800">{client?.nama}</span>
          {client?.cdk_nama ? ` · data ${client.cdk_nama}` : ' · data seluruh provinsi'}
        </p>

        <form onSubmit={handleSubmit} className="space-y-5">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <InputLabel htmlFor="token_name" value="Nama Token" />
              <TextInput
                id="token_name"
                className="mt-1 block w-full"
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                placeholder="produksi, staging, ..."
                required
              />
              <InputError message={errors.name} className="mt-2" />
            </div>
            <div>
              <InputLabel htmlFor="expires_in_days" value="Masa Berlaku" />
              <select
                id="expires_in_days"
                className="mt-1 block w-full border-gray-200 bg-gray-50 text-gray-900 focus:bg-white focus:border-primary-600 focus:ring-primary-600 rounded-xl shadow-sm text-sm"
                value={data.expires_in_days}
                onChange={(e) => setData('expires_in_days', e.target.value === '' ? '' : Number(e.target.value))}
              >
                {EXPIRY_OPTIONS.map((opt) => (
                  <option key={opt.label} value={opt.value}>{opt.label}</option>
                ))}
              </select>
              <InputError message={errors.expires_in_days} className="mt-2" />
            </div>
          </div>

          <fieldset>
            <legend className="block font-medium text-sm text-gray-700">Akses Data</legend>
            <p className="text-xs text-gray-500 mb-2">Berikan hanya akses yang benar-benar dibutuhkan sistem klien.</p>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
              {abilities.map((ability) => {
                const checked = data.abilities.includes(ability.value);
                return (
                  <label
                    key={ability.value}
                    className={`flex items-start gap-3 rounded-xl border p-3 cursor-pointer transition-colors ${
                      checked ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:bg-gray-50'
                    }`}
                  >
                    <input
                      type="checkbox"
                      className="mt-0.5 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 h-4 w-4"
                      checked={checked}
                      onChange={() => toggleAbility(ability.value)}
                    />
                    <span className="min-w-0">
                      <span className="block text-sm font-medium text-gray-900">{ability.label}</span>
                      <code className="block text-xs text-gray-500">{ability.value}</code>
                    </span>
                  </label>
                );
              })}
            </div>
            <InputError message={abilityError} className="mt-2" />
          </fieldset>

          <div className="flex justify-end gap-3 pt-2">
            <SecondaryButton type="button" onClick={onClose} className="rounded-xl px-6 py-3 h-[48px] shadow-sm font-medium">
              Batal
            </SecondaryButton>
            <PrimaryButton disabled={processing} className="bg-primary-600 hover:bg-primary-700 text-white h-[48px]">
              Terbitkan
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  );
}
