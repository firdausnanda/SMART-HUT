import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link, usePage } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import Select from 'react-select';
import { useState, useEffect } from 'react';
import axios from 'axios';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';

const MySwal = withReactContent(Swal);

export default function Edit({ auth, data: item, sumberDana, bangunanKta }) {
  const { flash } = usePage().props;
  const { data, setData, patch, processing, errors } = useForm({
    year: item?.year || new Date().getFullYear(),
    month: item?.month || new Date().getMonth() + 1,
    province_id: item?.province_id || 35,
    regency_id: item?.regency_id || '',
    district_id: item?.district_id || '',
    village_id: item?.village_id || '',
    target_annual: item?.target_annual ?? '',
    fund_source: item?.fund_source || '',
    details: (item?.details && item.details.length > 0) ? item.details.map(d => ({
      bangunan_kta_id: d.bangunan_kta_id || '',
      unit_amount: d.unit_amount || 0
    })) : [{ bangunan_kta_id: '', unit_amount: 0 }],
  });

  const [regencies, setRegencies] = useState([]);
  const [districts, setDistricts] = useState([]);
  const [villages, setVillages] = useState([]);

  const [loadingRegencies, setLoadingRegencies] = useState(false);
  const [loadingDistricts, setLoadingDistricts] = useState(false);
  const [loadingVillages, setLoadingVillages] = useState(false);

  const formatLabel = (name) => {
    if (!name) return '';
    return name.toLowerCase()
      .replace('kota', 'Kota')
      .split(' ')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  };

  useEffect(() => {
    if (flash?.error) {
      MySwal.fire({ title: 'Gagal', text: flash.error, icon: 'error', confirmButtonText: 'Tutup', confirmButtonColor: '#d33' });
    }
    if (flash?.success) {
      MySwal.fire({ title: 'Berhasil', text: flash.success, icon: 'success', timer: 2000, showConfirmButton: false });
    }
  }, [flash]);

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

  // Load Villages when District changes
  useEffect(() => {
    if (data.district_id) {
      setLoadingVillages(true);
      axios.get(route('locations.villages', data.district_id))
        .then(res => {
          setVillages(res.data.map(item => ({
            value: item.id,
            label: formatLabel(item.name)
          })));
          setLoadingVillages(false);
        })
        .catch(() => setLoadingVillages(false));
    } else {
      setVillages([]);
    }
  }, [data.district_id]);

  const addDetailRow = () => {
    setData('details', [...data.details, { bangunan_kta_id: '', unit_amount: 0 }]);
  };

  const removeDetailRow = (index) => {
    const newDetails = [...data.details];
    newDetails.splice(index, 1);
    setData('details', newDetails);
  };

  const handleDetailChange = (index, field, value) => {
    const newDetails = [...data.details];
    newDetails[index][field] = value;
    setData('details', newDetails);
  };

  const submit = (e) => {
    e.preventDefault();
    patch(route('rhl-teknis.update', item.id));
  };

  const selectStyles = {
    control: (base, state) => ({
      ...base,
      borderRadius: '0.75rem',
      backgroundColor: state.isDisabled ? '#f3f4f6' : '#f9fafb',
      borderColor: state.isFocused ? '#059669' : '#e5e7eb',
      boxShadow: state.isFocused ? '0 0 0 1px #059669' : 'none',
      '&:hover': { borderColor: '#059669' }
    }),
    option: (base, state) => ({
      ...base,
      backgroundColor: state.isSelected ? '#059669' : state.isFocused ? '#ecfdf5' : 'transparent',
      color: state.isSelected ? 'white' : '#374151',
    })
  };

  const bangunanOptions = bangunanKta.map(b => ({
    value: b.id,
    label: b.name
  }));

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Edit Data RHL Bangunan Konservasi Tanah dan Air</h2>}
    >
      <Head title="Edit RHL Bangunan Konservasi Tanah dan Air" />

      <div className="max-w-4xl mx-auto">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="p-8 border-b border-gray-100 bg-gray-50/50">
            <h3 className="text-xl font-bold text-gray-900">Formulir Edit Data</h3>
            <p className="mt-1 text-sm text-gray-500">Perbarui detail pembangunan bangunan KTA di bawah ini. Anda dapat menambah atau menghapus baris rincian teknis.</p>
          </div>

          <div className="p-8">
            <form onSubmit={submit} className="space-y-8">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <div className="md:col-span-2">
                  <h4 className="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">Informasi Laporan</h4>
                </div>

                <div>
                  <InputLabel value="Tahun Laporan" className="mb-2" />
                  <TextInput type="number" className="w-full" value={data.year} onChange={e => setData('year', e.target.value)} required />
                  <InputError message={errors.year} />
                </div>

                <div>
                  <InputLabel value="Bulan Laporan" className="mb-2" />
                  <select
                    className="w-full border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl"
                    value={data.month}
                    onChange={e => setData('month', e.target.value)}
                  >
                    {[...Array(12)].map((_, i) => (
                      <option key={i + 1} value={i + 1}>{new Date(0, i).toLocaleString('id-ID', { month: 'long' })}</option>
                    ))}
                  </select>
                </div>

                {/* Location Selects */}
                <div>
                  <InputLabel value="Provinsi" className="mb-2" />
                  <Select
                    isDisabled
                    value={{ value: 35, label: 'JAWA TIMUR' }}
                    styles={selectStyles}
                  />
                </div>

                <div>
                  <InputLabel value="Kabupaten" className="mb-2" />
                  <Select
                    options={regencies}
                    isLoading={loadingRegencies}
                    onChange={(opt) => {
                      setData((prev) => ({
                        ...prev,
                        regency_id: opt?.value || '',
                        district_id: '',
                        village_id: ''
                      }));
                    }}
                    placeholder="Pilih Kabupaten..."
                    styles={selectStyles}
                    isClearable
                    value={regencies.find(r => r.value == data.regency_id) || null}
                  />
                  <InputError message={errors.regency_id} className="mt-2" />
                </div>

                <div>
                  <InputLabel value="Kecamatan" className="mb-2" />
                  <Select
                    options={districts}
                    isLoading={loadingDistricts}
                    isDisabled={!data.regency_id}
                    onChange={(opt) => {
                      setData((prev) => ({
                        ...prev,
                        district_id: opt?.value || '',
                        village_id: ''
                      }));
                    }}
                    placeholder="Pilih Kecamatan..."
                    styles={selectStyles}
                    isClearable
                    value={districts.find(d => d.value == data.district_id) || null}
                  />
                  <InputError message={errors.district_id} className="mt-2" />
                </div>

                <div>
                  <InputLabel value="Desa" className="mb-2" />
                  <Select
                    options={villages}
                    isLoading={loadingVillages}
                    isDisabled={!data.district_id}
                    onChange={(opt) => setData('village_id', opt?.value || '')}
                    placeholder="Pilih Desa..."
                    styles={selectStyles}
                    isClearable
                    value={villages.find(v => v.value == data.village_id) || null}
                  />
                  <InputError message={errors.village_id} className="mt-2" />
                </div>



                <div>
                  <InputLabel value="Target Tahunan (Unit)" className="mb-2" />
                  <TextInput type="number" step="1" className="w-full" value={data.target_annual} onChange={e => setData('target_annual', e.target.value)} required />
                  <InputError message={errors.target_annual} />
                </div>

                <div>
                  <InputLabel value="Sumber Dana" className="mb-2" />
                  <Select
                    options={sumberDana.map(sd => ({ value: sd.name, label: sd.name }))}
                    value={data.fund_source ? { value: data.fund_source, label: data.fund_source } : null}
                    onChange={opt => setData('fund_source', opt?.value || '')}
                    styles={selectStyles}
                    placeholder="Pilih Sumber Dana..."
                  />
                  <InputError message={errors.fund_source} />
                </div>

                <div className="md:col-span-2 mt-4 flex justify-between items-center border-b border-emerald-100 pb-2">
                  <h4 className="text-xs font-bold text-emerald-600 uppercase tracking-widest">Detail Teknis Bangunan</h4>
                  <button
                    type="button"
                    onClick={addDetailRow}
                    className="text-[10px] bg-emerald-600 text-white px-3 py-1 rounded-lg font-bold hover:bg-emerald-700 transition-colors flex items-center gap-1"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M12 4v16m8-8H4" />
                    </svg>
                    TAMBAH BARIS
                  </button>
                </div>

                <div className="md:col-span-2 space-y-4">
                  {data.details.map((detail, index) => (
                    <div key={index} className="flex flex-col md:flex-row gap-4 p-4 bg-gray-50 rounded-xl relative border border-gray-100 group">
                      <div className="flex-1">
                        <InputLabel value="Jenis Bangunan" className="mb-2 text-[10px]" />
                        <Select
                          options={bangunanOptions}
                          styles={selectStyles}
                          placeholder="Pilih Jenis Bangunan..."
                          value={bangunanOptions.find(opt => opt.value == detail.bangunan_kta_id)}
                          onChange={opt => handleDetailChange(index, 'bangunan_kta_id', opt?.value || '')}
                        />
                        <InputError message={errors[`details.${index}.bangunan_kta_id`]} />
                      </div>
                      <div className="w-full md:w-32">
                        <InputLabel value="Jumlah Unit" className="mb-2 text-[10px]" />
                        <TextInput
                          type="number"
                          className="w-full text-sm"
                          value={detail.unit_amount}
                          onChange={e => handleDetailChange(index, 'unit_amount', e.target.value)}
                          required
                        />
                        <InputError message={errors[`details.${index}.unit_amount`]} />
                      </div>
                      {data.details.length > 1 && (
                        <button
                          type="button"
                          onClick={() => removeDetailRow(index)}
                          className="absolute -top-2 -right-2 md:relative md:top-auto md:right-auto md:mt-8 p-1.5 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors h-fit shadow-sm md:shadow-none"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              <div className="pt-6 border-t border-gray-100 flex justify-end gap-4">
                <Link href={route('rhl-teknis.index')} className="px-6 py-2.5 text-sm font-bold text-gray-500">Batal</Link>
                <PrimaryButton className="bg-emerald-700 hover:bg-emerald-600 px-8 text-white" loading={processing}>Perbarui Laporan</PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
