import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { useState } from 'react';
import Select from 'react-select';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';

const MySwal = withReactContent(Swal);

export default function DemografiEdit({ auth, pegawai, bezettings, statusPegawaiOptions, agamaOptions, statusKedudukanOptions, statusPernikahanOptions }) {
  const { data, setData, put, processing, errors } = useForm({
    nip: pegawai.nip || '',
    nik: pegawai.nik || '',
    nama_lengkap: pegawai.nama_lengkap || '',
    tempat_lahir: pegawai.tempat_lahir || '',
    tanggal_lahir: pegawai.tanggal_lahir ? pegawai.tanggal_lahir.split('T')[0] : '',
    jenis_kelamin: pegawai.jenis_kelamin || 'L',
    agama: pegawai.agama || 'Islam',
    status_pernikahan: pegawai.status_pernikahan || '',
    pendidikan_terakhir: pegawai.pendidikan_terakhir || '',
    alamat: pegawai.alamat || '',
    status_pegawai: pegawai.status_pegawai || 'PNS',
    bezetting_id: pegawai.bezetting_id || '',
    pangkat_golongan: pegawai.pangkat_golongan || '',
    tmt_cpns: pegawai.tmt_cpns ? pegawai.tmt_cpns.split('T')[0] : '',
    tmt_pns: pegawai.tmt_pns ? pegawai.tmt_pns.split('T')[0] : '',
    bup: pegawai.bup || '58',
    unit_kerja: pegawai.unit_kerja || '',
    skpd: pegawai.skpd || '',
    status_kedudukan: pegawai.status_kedudukan || 'Aktif',
  });

  const [activeTab, setActiveTab] = useState('profil');
  const [showKgbModal, setShowKgbModal] = useState(false);
  const [editingKgb, setEditingKgb] = useState(null);

  const kgbForm = useForm({
    no_sk: '',
    tanggal_sk: '',
    tmt_kgb: '',
    gaji_pokok_baru: '',
  });

  const submit = (e) => {
    e.preventDefault();
    put(route('demografi-pegawai.update', pegawai.id));
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

  const bezettingOptions = bezettings.map(bez => ({
    value: bez.id,
    label: bez.nama_jabatan
  }));

  const jenisKelaminOptions = [
    { value: 'L', label: 'Laki-laki' },
    { value: 'P', label: 'Perempuan' }
  ];

  const openKgbModal = (kgb = null) => {
    setEditingKgb(kgb);
    if (kgb) {
      kgbForm.setData({
        no_sk: kgb.no_sk,
        tanggal_sk: kgb.tanggal_sk?.split('T')[0] || '',
        tmt_kgb: kgb.tmt_kgb?.split('T')[0] || '',
        gaji_pokok_baru: kgb.gaji_pokok_baru,
      });
    } else {
      kgbForm.reset();
    }
    setShowKgbModal(true);
  };

  const submitKgb = (e) => {
    e.preventDefault();
    if (editingKgb) {
      kgbForm.put(route('demografi-pegawai.kgb.update', editingKgb.id), {
        onSuccess: () => {
          setShowKgbModal(false);
          kgbForm.reset();
          MySwal.fire('Berhasil', 'Riwayat KGB berhasil diperbarui', 'success');
        },
      });
    } else {
      kgbForm.post(route('demografi-pegawai.kgb.store', pegawai.id), {
        onSuccess: () => {
          setShowKgbModal(false);
          kgbForm.reset();
          MySwal.fire('Berhasil', 'Riwayat KGB berhasil ditambahkan', 'success');
        },
      });
    }
  };

  const deleteKgb = (id) => {
    MySwal.fire({
      title: 'Hapus Riwayat KGB?',
      text: "Data yang dihapus tidak dapat dikembalikan!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        kgbForm.delete(route('demografi-pegawai.kgb.destroy', id), {
          onSuccess: () => MySwal.fire('Terhapus!', 'Riwayat KGB telah dihapus.', 'success'),
        });
      }
    });
  };



  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Edit Data Pegawai</h2>}
    >
      <Head title="Edit Pegawai" />

      <div className="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <Link
          href={route('demografi-pegawai.index')}
          className="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:text-primary-800 transition-colors mb-6 group"
        >
          <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 transform group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Kembali ke Daftar Pegawai
        </Link>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
          <div className="flex border-b border-gray-100">
            <button
              onClick={() => setActiveTab('profil')}
              className={`flex-1 py-4 px-6 text-sm font-bold transition-all duration-300 flex items-center justify-center gap-2 ${activeTab === 'profil'
                  ? 'text-primary-700 border-b-2 border-primary-600 bg-primary-50/30'
                  : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                }`}
            >
              <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Profil Pegawai
            </button>
            <button
              onClick={() => setActiveTab('kgb')}
              className={`flex-1 py-4 px-6 text-sm font-bold transition-all duration-300 flex items-center justify-center gap-2 ${activeTab === 'kgb'
                  ? 'text-primary-700 border-b-2 border-primary-600 bg-primary-50/30'
                  : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                }`}
            >
              <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Riwayat KGB
            </button>
          </div>

          {activeTab === 'profil' ? (
            <>
              <div className="p-8 border-b border-gray-100 bg-gray-50/50">
                <h3 className="text-xl font-bold text-gray-900">Formulir Edit Data Demografi Pegawai</h3>
                <p className="mt-1 text-sm text-gray-500">
                  Perbarui informasi pegawai <span className="font-semibold text-gray-700">{pegawai.nama_lengkap}</span>.
                </p>
              </div>

              <div className="p-8">
                <form onSubmit={submit} className="space-y-8">
              {/* Section 1: Profil Pegawai */}
              <div>
                <h4 className="flex items-center text-lg font-bold text-primary-700 mb-4 border-b pb-2">
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Informasi Profil
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <InputLabel htmlFor="nip" value="NIP" className="text-gray-700 font-bold mb-2" />
                    <TextInput
                      id="nip"
                      type="text"
                      className="w-full"
                      value={data.nip}
                      onChange={(e) => setData('nip', e.target.value)}
                      required
                      placeholder="Masukkan NIP (tanpa spasi)"
                    />
                    <InputError message={errors.nip} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="nama_lengkap" value="Nama Lengkap" className="text-gray-700 font-bold mb-2" />
                    <TextInput
                      id="nama_lengkap"
                      type="text"
                      className="w-full"
                      value={data.nama_lengkap}
                      onChange={(e) => setData('nama_lengkap', e.target.value)}
                      required
                      placeholder="Masukkan nama lengkap beserta gelar"
                    />
                    <InputError message={errors.nama_lengkap} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="nik" value="NIK" className="text-gray-700 font-bold mb-2" />
                    <TextInput
                      id="nik"
                      type="text"
                      className="w-full"
                      value={data.nik}
                      onChange={(e) => setData('nik', e.target.value)}
                      placeholder="Masukkan NIK 16 digit"
                    />
                    <InputError message={errors.nik} className="mt-2" />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <InputLabel htmlFor="tempat_lahir" value="Tempat Lahir" className="text-gray-700 font-bold mb-2" />
                      <TextInput
                        id="tempat_lahir"
                        type="text"
                        className="w-full"
                        value={data.tempat_lahir}
                        onChange={(e) => setData('tempat_lahir', e.target.value)}
                        required
                        placeholder="Kota kelahiran"
                      />
                      <InputError message={errors.tempat_lahir} className="mt-2" />
                    </div>
                    <div>
                      <InputLabel htmlFor="tanggal_lahir" value="Tanggal Lahir" className="text-gray-700 font-bold mb-2" />
                      <div className="relative">
                        <DatePicker
                          id="tanggal_lahir"
                          selected={data.tanggal_lahir ? new Date(data.tanggal_lahir) : null}
                          onChange={(date) => {
                            if (date) {
                              const yyyy = date.getFullYear();
                              const mm = String(date.getMonth() + 1).padStart(2, '0');
                              const dd = String(date.getDate()).padStart(2, '0');
                              setData('tanggal_lahir', `${yyyy}-${mm}-${dd}`);
                            } else {
                              setData('tanggal_lahir', '');
                            }
                          }}
                          dateFormat="dd/MM/yyyy"
                          showMonthDropdown
                          showYearDropdown
                          dropdownMode="select"
                          className="w-full pl-11 pr-4 py-2.5 border-gray-200 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm bg-gray-50/50 hover:bg-white focus:bg-white transition-all text-sm font-medium cursor-pointer"
                          placeholderText="Pilih Tanggal Lahir"
                          required
                          wrapperClassName="w-full"
                        />
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-primary-600/70">
                          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                        </div>
                      </div>
                      <InputError message={errors.tanggal_lahir} className="mt-2" />
                    </div>
                  </div>

                  <div>
                    <InputLabel htmlFor="jenis_kelamin" value="Jenis Kelamin" className="text-gray-700 font-bold mb-2" />
                    <Select
                      options={jenisKelaminOptions}
                      value={jenisKelaminOptions.find(j => j.value === data.jenis_kelamin)}
                      onChange={(opt) => setData('jenis_kelamin', opt?.value || '')}
                      styles={selectStyles}
                    />
                    <InputError message={errors.jenis_kelamin} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="agama" value="Agama" className="text-gray-700 font-bold mb-2" />
                    <Select
                      options={agamaOptions}
                      value={agamaOptions.find(a => a.value === data.agama)}
                      onChange={(opt) => setData('agama', opt?.value || '')}
                      styles={selectStyles}
                    />
                    <InputError message={errors.agama} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="status_pernikahan" value="Status Pernikahan" className="text-gray-700 font-bold mb-2" />
                    <Select
                      options={statusPernikahanOptions}
                      value={statusPernikahanOptions?.find(s => s.value === data.status_pernikahan) || null}
                      onChange={(opt) => setData('status_pernikahan', opt?.value || '')}
                      styles={selectStyles}
                      isClearable
                      placeholder="Pilih Status Pernikahan"
                    />
                    <InputError message={errors.status_pernikahan} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="pendidikan_terakhir" value="Pendidikan Terakhir" className="text-gray-700 font-bold mb-2" />
                    <TextInput
                      id="pendidikan_terakhir"
                      type="text"
                      className="w-full"
                      value={data.pendidikan_terakhir}
                      onChange={(e) => setData('pendidikan_terakhir', e.target.value)}
                      required
                      placeholder="Contoh: S1 Kehutanan"
                    />
                    <InputError message={errors.pendidikan_terakhir} className="mt-2" />
                  </div>

                  <div className="md:col-span-2">
                    <InputLabel htmlFor="alamat" value="Alamat Lengkap" className="text-gray-700 font-bold mb-2" />
                    <textarea
                      id="alamat"
                      className="w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm"
                      rows="3"
                      value={data.alamat}
                      onChange={(e) => setData('alamat', e.target.value)}
                      placeholder="Masukkan alamat lengkap dengan RT/RW, Kelurahan, Kecamatan"
                    />
                    <InputError message={errors.alamat} className="mt-2" />
                  </div>
                </div>
              </div>

              {/* Section 2: Kepegawaian */}
              <div className="pt-4">
                <h4 className="flex items-center text-lg font-bold text-primary-700 mb-4 border-b pb-2">
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  Informasi Kepegawaian
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <InputLabel htmlFor="status_pegawai" value="Status Pegawai" className="text-gray-700 font-bold mb-2" />
                    <Select
                      options={statusPegawaiOptions}
                      value={statusPegawaiOptions.find(s => s.value === data.status_pegawai)}
                      onChange={(opt) => setData('status_pegawai', opt?.value || '')}
                      styles={selectStyles}
                    />
                    <InputError message={errors.status_pegawai} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="bezetting_id" value="Jabatan (Bezetting)" className="text-gray-700 font-bold mb-2" />
                    <Select
                      options={bezettingOptions}
                      value={bezettingOptions.find(b => b.value === data.bezetting_id)}
                      onChange={(opt) => setData('bezetting_id', opt?.value || '')}
                      placeholder="Cari dan pilih Jabatan..."
                      styles={selectStyles}
                      isClearable
                    />
                    <InputError message={errors.bezetting_id} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="pangkat_golongan" value="Pangkat / Golongan" className="text-gray-700 font-bold mb-2" />
                    <TextInput
                      id="pangkat_golongan"
                      type="text"
                      className="w-full"
                      value={data.pangkat_golongan}
                      onChange={(e) => setData('pangkat_golongan', e.target.value)}
                      placeholder="Contoh: Penata Muda / III.a"
                    />
                    <InputError message={errors.pangkat_golongan} className="mt-2" />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <InputLabel htmlFor="tmt_cpns" value="TMT CPNS" className="text-gray-700 font-bold mb-2" />
                      <div className="relative">
                        <DatePicker
                          id="tmt_cpns"
                          selected={data.tmt_cpns ? new Date(data.tmt_cpns) : null}
                          onChange={(date) => {
                            if (date) {
                              const yyyy = date.getFullYear();
                              const mm = String(date.getMonth() + 1).padStart(2, '0');
                              const dd = String(date.getDate()).padStart(2, '0');
                              setData('tmt_cpns', `${yyyy}-${mm}-${dd}`);
                            } else {
                              setData('tmt_cpns', '');
                            }
                          }}
                          dateFormat="dd/MM/yyyy"
                          showMonthDropdown
                          showYearDropdown
                          dropdownMode="select"
                          className="w-full pl-11 pr-4 py-2.5 border-gray-200 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm bg-gray-50/50 hover:bg-white focus:bg-white transition-all text-sm font-medium cursor-pointer"
                          placeholderText="Pilih TMT CPNS"
                          wrapperClassName="w-full"
                        />
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-primary-600/70">
                          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                        </div>
                      </div>
                      <InputError message={errors.tmt_cpns} className="mt-2" />
                    </div>
                    <div>
                      <InputLabel htmlFor="tmt_pns" value="TMT PNS" className="text-gray-700 font-bold mb-2" />
                      <div className="relative">
                        <DatePicker
                          id="tmt_pns"
                          selected={data.tmt_pns ? new Date(data.tmt_pns) : null}
                          onChange={(date) => {
                            if (date) {
                              const yyyy = date.getFullYear();
                              const mm = String(date.getMonth() + 1).padStart(2, '0');
                              const dd = String(date.getDate()).padStart(2, '0');
                              setData('tmt_pns', `${yyyy}-${mm}-${dd}`);
                            } else {
                              setData('tmt_pns', '');
                            }
                          }}
                          dateFormat="dd/MM/yyyy"
                          showMonthDropdown
                          showYearDropdown
                          dropdownMode="select"
                          className="w-full pl-11 pr-4 py-2.5 border-gray-200 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm bg-gray-50/50 hover:bg-white focus:bg-white transition-all text-sm font-medium cursor-pointer"
                          placeholderText="Pilih TMT PNS"
                          wrapperClassName="w-full"
                          isClearable
                        />
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-primary-600/70">
                          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                        </div>
                      </div>
                      <InputError message={errors.tmt_pns} className="mt-2" />
                    </div>
                  </div>

                  <div>
                    <InputLabel htmlFor="bup" value="Batas Usia Pensiun" className="text-gray-700 font-bold mb-2" />
                    <div className="relative">
                      <TextInput
                        id="bup"
                        type="number"
                        className="w-full pr-12"
                        value={data.bup}
                        onChange={(e) => setData('bup', e.target.value)}
                        required
                      />
                      <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400 font-semibold">Tahun</div>
                    </div>
                    <InputError message={errors.bup} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="unit_kerja" value="Unit Kerja" className="text-gray-700 font-bold mb-2" />
                    <TextInput
                      id="unit_kerja"
                      type="text"
                      className="w-full"
                      value={data.unit_kerja}
                      onChange={(e) => setData('unit_kerja', e.target.value)}
                      placeholder="Masukkan Unit Kerja"
                    />
                    <InputError message={errors.unit_kerja} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="skpd" value="SKPD" className="text-gray-700 font-bold mb-2" />
                    <TextInput
                      id="skpd"
                      type="text"
                      className="w-full"
                      value={data.skpd}
                      onChange={(e) => setData('skpd', e.target.value)}
                      placeholder="Masukkan SKPD"
                    />
                    <InputError message={errors.skpd} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="status_kedudukan" value="Status Kedudukan" className="text-gray-700 font-bold mb-2" />
                    <Select
                      options={statusKedudukanOptions}
                      value={statusKedudukanOptions.find(s => s.value === data.status_kedudukan)}
                      onChange={(opt) => setData('status_kedudukan', opt?.value || '')}
                      styles={selectStyles}
                    />
                    <InputError message={errors.status_kedudukan} className="mt-2" />
                  </div>
                </div>
              </div>

              <div className="pt-8 mt-8 border-t border-gray-100 flex items-center justify-end gap-4">
                <Link
                  href={route('demografi-pegawai.index')}
                  className="px-6 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors"
                >
                  Batal
                </Link>
                <PrimaryButton
                  className="px-8 py-3 bg-gradient-to-r from-emerald-700 to-green-800 hover:from-emerald-600 hover:to-green-700 text-white rounded-xl font-bold shadow-lg shadow-emerald-900/20 transition-all transform active:scale-95"
                  loading={processing}
                >
                  Perbarui Pegawai
                </PrimaryButton>
              </div>
            </form>
          </div>
            </>
          ) : (
            <div className="p-8">
              <div className="flex justify-between items-center mb-6">
                <div>
                  <h3 className="text-lg font-bold text-gray-900">Riwayat Kenaikan Gaji Berkala</h3>
                  <p className="text-sm text-gray-500">Kelola riwayat kenaikan gaji untuk {pegawai.nama_lengkap}</p>
                </div>
                <PrimaryButton onClick={() => openKgbModal()} className="bg-primary-600 hover:bg-primary-700">
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                  </svg>
                  Tambah KGB
                </PrimaryButton>
              </div>

              <div className="overflow-x-auto rounded-xl border border-gray-100 shadow-sm">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-gray-50 border-b border-gray-100">
                      <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase">No. SK & Tanggal</th>
                      <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase">TMT KGB</th>
                      <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Gaji Pokok Baru</th>
                      <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase">TMT Berikutnya</th>
                      <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {pegawai.riwayat_kgb && pegawai.riwayat_kgb.length > 0 ? (
                      pegawai.riwayat_kgb.map((kgb) => (
                        <tr key={kgb.id} className="hover:bg-gray-50 transition-colors">
                          <td className="px-6 py-4">
                            <div className="flex flex-col">
                              <span className="font-bold text-gray-900 text-sm">{kgb.no_sk}</span>
                              <span className="text-xs text-gray-500">
                                {new Date(kgb.tanggal_sk).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                              </span>
                            </div>
                          </td>
                          <td className="px-6 py-4 text-sm font-medium text-gray-700">
                            {new Date(kgb.tmt_kgb).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                          </td>
                          <td className="px-6 py-4 text-sm font-bold text-emerald-700">
                            Rp {new Intl.NumberFormat('id-ID').format(kgb.gaji_pokok_baru)}
                          </td>
                          <td className="px-6 py-4 text-sm font-medium text-blue-700 bg-blue-50/30">
                            {kgb.tmt_kgb_berikutnya ? new Date(kgb.tmt_kgb_berikutnya).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-'}
                          </td>
                          <td className="px-6 py-4">
                            <div className="flex justify-center gap-2">
                              <button onClick={() => openKgbModal(kgb)} className="p-1.5 text-amber-600 hover:bg-amber-100 rounded-lg transition-colors" title="Edit KGB">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                              </button>
                              <button onClick={() => deleteKgb(kgb.id)} className="p-1.5 text-red-600 hover:bg-red-100 rounded-lg transition-colors" title="Hapus KGB">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                              </button>
                            </div>
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan="5" className="px-6 py-12 text-center">
                          <div className="flex flex-col items-center">
                            <div className="h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                              <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                              </svg>
                            </div>
                            <span className="text-sm font-medium text-gray-400">Belum ada riwayat KGB</span>
                          </div>
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* KGB Modal */}
      <Modal show={showKgbModal} onClose={() => setShowKgbModal(false)} maxWidth="lg">
        <form onSubmit={submitKgb} className="p-6">
          <div className="flex items-center justify-between mb-6 border-b pb-4">
            <h2 className="text-lg font-bold text-gray-900">
              {editingKgb ? 'Edit Riwayat KGB' : 'Tambah Riwayat KGB'}
            </h2>
            <button type="button" onClick={() => setShowKgbModal(false)} className="text-gray-400 hover:text-gray-600 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <div className="space-y-6">
            <div>
              <InputLabel htmlFor="no_sk" value="Nomor SK" className="text-gray-700 font-bold mb-2" />
              <TextInput
                id="no_sk"
                type="text"
                className="w-full"
                value={kgbForm.data.no_sk}
                onChange={(e) => kgbForm.setData('no_sk', e.target.value)}
                required
                placeholder="Masukkan Nomor SK KGB"
              />
              <InputError message={kgbForm.errors.no_sk} className="mt-2" />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <InputLabel htmlFor="tanggal_sk" value="Tanggal SK" className="text-gray-700 font-bold mb-2" />
                <div className="relative">
                  <DatePicker
                    id="tanggal_sk"
                    selected={kgbForm.data.tanggal_sk ? new Date(kgbForm.data.tanggal_sk) : null}
                    onChange={(date) => {
                      if (date) {
                        const yyyy = date.getFullYear();
                        const mm = String(date.getMonth() + 1).padStart(2, '0');
                        const dd = String(date.getDate()).padStart(2, '0');
                        kgbForm.setData('tanggal_sk', `${yyyy}-${mm}-${dd}`);
                      } else {
                        kgbForm.setData('tanggal_sk', '');
                      }
                    }}
                    dateFormat="dd/MM/yyyy"
                    showMonthDropdown
                    showYearDropdown
                    dropdownMode="select"
                    className="w-full pl-11 pr-4 py-2.5 border-gray-200 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm bg-gray-50/50 hover:bg-white focus:bg-white transition-all text-sm font-medium cursor-pointer"
                    placeholderText="Pilih Tanggal SK"
                    required
                    wrapperClassName="w-full"
                  />
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-primary-600/70">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                </div>
                <InputError message={kgbForm.errors.tanggal_sk} className="mt-2" />
              </div>

              <div>
                <InputLabel htmlFor="tmt_kgb" value="TMT KGB" className="text-gray-700 font-bold mb-2" />
                <div className="relative">
                  <DatePicker
                    id="tmt_kgb"
                    selected={kgbForm.data.tmt_kgb ? new Date(kgbForm.data.tmt_kgb) : null}
                    onChange={(date) => {
                      if (date) {
                        const yyyy = date.getFullYear();
                        const mm = String(date.getMonth() + 1).padStart(2, '0');
                        const dd = String(date.getDate()).padStart(2, '0');
                        kgbForm.setData('tmt_kgb', `${yyyy}-${mm}-${dd}`);
                      } else {
                        kgbForm.setData('tmt_kgb', '');
                      }
                    }}
                    dateFormat="dd/MM/yyyy"
                    showMonthDropdown
                    showYearDropdown
                    dropdownMode="select"
                    className="w-full pl-11 pr-4 py-2.5 border-gray-200 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm bg-gray-50/50 hover:bg-white focus:bg-white transition-all text-sm font-medium cursor-pointer"
                    placeholderText="Pilih TMT KGB"
                    required
                    wrapperClassName="w-full"
                  />
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-primary-600/70">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                </div>
                <InputError message={kgbForm.errors.tmt_kgb} className="mt-2" />
              </div>
            </div>

            <div>
              <InputLabel htmlFor="gaji_pokok_baru" value="Gaji Pokok Baru" className="text-gray-700 font-bold mb-2" />
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none font-bold text-gray-400">Rp</div>
                <TextInput
                  id="gaji_pokok_baru"
                  type="number"
                  className="w-full pl-11"
                  value={kgbForm.data.gaji_pokok_baru}
                  onChange={(e) => kgbForm.setData('gaji_pokok_baru', e.target.value)}
                  required
                  placeholder="0"
                />
              </div>
              <InputError message={kgbForm.errors.gaji_pokok_baru} className="mt-2" />
            </div>

            <div className="bg-blue-50 p-4 rounded-xl border border-blue-100">
              <div className="flex gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div className="text-xs text-blue-800 leading-relaxed">
                  <p className="font-bold mb-1 underline">Informasi Otomasi:</p>
                  Status akan otomatis menjadi <span className="font-bold italic text-blue-900">"Final"</span> dan **TMT KGB Berikutnya** akan dihitung otomatis **2 tahun** dari TMT yang Anda pilih.
                </div>
              </div>
            </div>
          </div>

          <div className="mt-8 flex justify-end gap-3">
            <SecondaryButton onClick={() => setShowKgbModal(false)} type="button">
              Batal
            </SecondaryButton>
            <PrimaryButton className="bg-primary-600 hover:bg-primary-700" loading={kgbForm.processing}>
              {editingKgb ? 'Simpan Perubahan' : 'Tambahkan KGB'}
            </PrimaryButton>
          </div>
        </form>
      </Modal>
    </AuthenticatedLayout>
  );
}
