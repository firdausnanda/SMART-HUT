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

export default function DemografiCreate({ auth, bezettings, statusPegawaiOptions, agamaOptions, statusKedudukanOptions, statusPernikahanOptions }) {
  const { data, setData, post, processing, errors } = useForm({
    nip: '',
    nik: '',
    nama_lengkap: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    jenis_kelamin: 'L',
    agama: 'Islam',
    status_pernikahan: '',
    pendidikan_terakhir: '',
    alamat: '',
    status_pegawai: 'PNS',
    bezetting_id: '',
    pangkat_golongan: '',
    tmt_cpns: '',
    bup: '58',
    unit_kerja: '',
    skpd: '',
    status_kedudukan: 'Aktif',
    kgb_no_sk: '',
    kgb_tanggal_sk: '',
    kgb_tmt: '',
    kgb_gaji: '',
  });

  const submit = (e) => {
    e.preventDefault();
    post(route('demografi-pegawai.store'));
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

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Tambah Pegawai Baru</h2>}
    >
      <Head title="Tambah Pegawai" />

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

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="p-8 border-b border-gray-100 bg-gray-50/50">
            <h3 className="text-xl font-bold text-gray-900">Formulir Tambah Data Demografi Pegawai</h3>
            <p className="mt-1 text-sm text-gray-500">
              Lengkapi seluruh informasi data profil, kepangkatan, dan status kedudukan pegawai.
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

              {/* Section 3: Informasi KGB Terakhir */}
              <div className="pt-4">
                <h4 className="flex items-center text-lg font-bold text-primary-700 mb-4 border-b pb-2">
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Informasi KGB Terakhir (Opsional)
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <InputLabel htmlFor="kgb_no_sk" value="Nomor SK KGB" className="text-gray-700 font-bold mb-2" />
                    <TextInput
                      id="kgb_no_sk"
                      className="w-full"
                      value={data.kgb_no_sk}
                      onChange={(e) => setData('kgb_no_sk', e.target.value)}
                      placeholder="Contoh: 821.2/123/2023"
                    />
                    <InputError message={errors.kgb_no_sk} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="kgb_gaji" value="Gaji Pokok Terakhir" className="text-gray-700 font-bold mb-2" />
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none font-bold text-gray-400 text-sm">Rp</div>
                      <TextInput
                        id="kgb_gaji"
                        type="number"
                        className="w-full pl-11"
                        value={data.kgb_gaji}
                        onChange={(e) => setData('kgb_gaji', e.target.value)}
                        placeholder="0"
                      />
                    </div>
                    <InputError message={errors.kgb_gaji} className="mt-2" />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <InputLabel htmlFor="kgb_tanggal_sk" value="Tanggal SK" className="text-gray-700 font-bold mb-2" />
                      <div className="relative">
                        <DatePicker
                          id="kgb_tanggal_sk"
                          selected={data.kgb_tanggal_sk ? new Date(data.kgb_tanggal_sk) : null}
                          onChange={(date) => {
                            if (date) {
                              const yyyy = date.getFullYear();
                              const mm = String(date.getMonth() + 1).padStart(2, '0');
                              const dd = String(date.getDate()).padStart(2, '0');
                              setData('kgb_tanggal_sk', `${yyyy}-${mm}-${dd}`);
                            } else {
                              setData('kgb_tanggal_sk', '');
                            }
                          }}
                          dateFormat="dd/MM/yyyy"
                          showMonthDropdown
                          showYearDropdown
                          dropdownMode="select"
                          className="w-full pl-11 pr-4 py-2.5 border-gray-200 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm bg-gray-50/50 hover:bg-white focus:bg-white transition-all text-sm font-medium cursor-pointer"
                          placeholderText="Pilih Tanggal SK"
                          wrapperClassName="w-full"
                        />
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-primary-600/70">
                          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                        </div>
                      </div>
                      <InputError message={errors.kgb_tanggal_sk} className="mt-2" />
                    </div>
                    <div>
                      <InputLabel htmlFor="kgb_tmt" value="TMT KGB" className="text-gray-700 font-bold mb-2" />
                      <div className="relative">
                        <DatePicker
                          id="kgb_tmt"
                          selected={data.kgb_tmt ? new Date(data.kgb_tmt) : null}
                          onChange={(date) => {
                            if (date) {
                              const yyyy = date.getFullYear();
                              const mm = String(date.getMonth() + 1).padStart(2, '0');
                              const dd = String(date.getDate()).padStart(2, '0');
                              setData('kgb_tmt', `${yyyy}-${mm}-${dd}`);
                            } else {
                              setData('kgb_tmt', '');
                            }
                          }}
                          dateFormat="dd/MM/yyyy"
                          showMonthDropdown
                          showYearDropdown
                          dropdownMode="select"
                          className="w-full pl-11 pr-4 py-2.5 border-gray-200 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm bg-gray-50/50 hover:bg-white focus:bg-white transition-all text-sm font-medium cursor-pointer"
                          placeholderText="Pilih TMT KGB"
                          wrapperClassName="w-full"
                        />
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-primary-600/70">
                          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                        </div>
                      </div>
                      <InputError message={errors.kgb_tmt} className="mt-2" />
                    </div>
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
                  Simpan Pegawai
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
