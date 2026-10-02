import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState, useEffect, useCallback } from 'react';
import { debounce } from 'lodash';
import Select from 'react-select';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import Pagination from '@/Components/Pagination';

export default function VillagesIndex({ auth, villages, provinces, regencies, districts, filters }) {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState('create');
  const [currentVillage, setCurrentVillage] = useState(null);

  const [params, setParams] = useState({
    search: filters.search || '',
    id: filters.id || '',
    name: filters.name || '',
    district_id: filters.district_id || '',
    regency_id: filters.regency_id || '',
    province_id: filters.province_id || '',
    per_page: filters.per_page || 10,
    sort: filters.sort || '',
    direction: filters.direction || 'asc',
  });

  const [searchQuery, setSearchQuery] = useState(filters.search || '');
  const [showAdvanced, setShowAdvanced] = useState(
    !!(filters.id || filters.name || filters.district_id || filters.regency_id || filters.province_id)
  );

  const customSelectStyles = {
    control: (provided, state) => ({
      ...provided,
      borderRadius: '0.75rem',
      borderColor: state.isFocused ? '#258a55' : '#e5e7eb',
      boxShadow: state.isFocused ? '0 0 0 2px rgba(37, 138, 85, 0.2)' : 'none',
      '&:hover': {
        borderColor: state.isFocused ? '#258a55' : '#d1d5db',
      },
      minHeight: '38px',
      fontSize: '0.875rem',
      backgroundColor: 'white',
    }),
    option: (provided, state) => ({
      ...provided,
      backgroundColor: state.isSelected ? '#258a55' : state.isFocused ? '#e1f8e8' : 'white',
      color: state.isSelected ? 'white' : '#374151',
      fontSize: '0.875rem',
      cursor: 'pointer',
      '&:active': {
        backgroundColor: '#258a55',
      },
    }),
    placeholder: (provided) => ({ ...provided, color: '#9ca3af' }),
    singleValue: (provided) => ({ ...provided, color: '#374151' }),
  };

  const applyFilters = (newParams) => {
    router.get(
      route('villages.index'),
      newParams,
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
    );
  };

  const handleSearch = useCallback(
    debounce((query) => {
      const newParams = { ...params, search: query };
      setParams(newParams);
      applyFilters(newParams);
    }, 500),
    [params]
  );

  const onSearchChange = (e) => {
    const query = e.target.value;
    setSearchQuery(query);
    handleSearch(query);
  };

  const handleFilterChange = (key, value) => {
    const newParams = { ...params, [key]: value };
    if (key === 'province_id') {
      newParams['regency_id'] = '';
      newParams['district_id'] = '';
    } else if (key === 'regency_id') {
      newParams['district_id'] = '';
    }
    setParams(newParams);
    applyFilters(newParams);
  };

  const resetFilters = () => {
    const defaultParams = {
      search: '',
      id: '',
      name: '',
      district_id: '',
      regency_id: '',
      province_id: '',
      per_page: 10,
      sort: '',
      direction: 'asc',
    };
    setParams(defaultParams);
    setSearchQuery('');
    applyFilters(defaultParams);
  };

  const handlePerPageChange = (perPage) => {
    handleFilterChange('per_page', perPage);
  };

  const handleSort = (field) => {
    let direction = 'asc';
    if (params.sort === field && params.direction === 'asc') {
      direction = 'desc';
    }
    const newParams = { ...params, sort: field, direction };
    setParams(newParams);
    applyFilters(newParams);
  };

  const SortIcon = ({ field }) => {
    if (params.sort !== field) return <div className="w-4 h-4 ml-1 opacity-20"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fillRule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clipRule="evenodd" /></svg></div>;
    return (
      <div className="w-4 h-4 ml-1 text-primary-600">
        {params.direction === 'asc' ? (
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fillRule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clipRule="evenodd" /></svg>
        ) : (
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" /></svg>
        )}
      </div>
    );
  };

  const provinceOptions = provinces.map(p => ({ value: p.id, label: p.name }));
  const filteredRegencies = params.province_id
    ? regencies.filter(r => r.province_id === params.province_id)
    : regencies;
  const regencyOptions = filteredRegencies.map(r => ({ value: r.id, label: r.name }));

  const filteredDistricts = params.regency_id
    ? districts.filter(d => d.regency_id === params.regency_id)
    : (params.province_id 
        ? districts.filter(d => d.regency?.province_id === params.province_id) 
        : districts);
  const districtOptions = filteredDistricts.map(d => ({ value: d.id, label: d.name }));

  const { data, setData, post, put, delete: destroy, processing, errors, reset, clearErrors } = useForm({
    id: '',
    district_id: '',
    name: '',
  });



  const openCreateModal = () => {
    setModalMode('create');
    reset();
    clearErrors();
    setIsModalOpen(true);
  };

  const openEditModal = (village) => {
    setModalMode('edit');
    setCurrentVillage(village);
    setData({
      id: village.id,
      district_id: village.district_id,
      name: village.name,
    });
    clearErrors();
    setIsModalOpen(true);
  };

  const openDeleteModal = (village) => {
    setCurrentVillage(village);
    setIsDeleteModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    reset();
    setCurrentVillage(null);
  };

  const closeDeleteModal = () => {
    setIsDeleteModalOpen(false);
    setCurrentVillage(null);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (modalMode === 'create') {
      post(route('villages.store'), {
        onSuccess: () => closeModal(),
      });
    } else {
      put(route('villages.update', currentVillage.id), {
        onSuccess: () => closeModal(),
      });
    }
  };

  const handleDelete = () => {
    destroy(route('villages.destroy', currentVillage.id), {
      onSuccess: () => closeDeleteModal(),
    });
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Master Data Desa/Kelurahan</h2>}
    >
      <Head title="Master Data Desa/Kelurahan" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-xl sm:rounded-2xl ring-1 ring-gray-900/5">
            {/* Header */}
            <div className="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center bg-gray-50/50">
              <h3 className="text-lg font-bold text-gray-900 mb-4 sm:mb-0">
                Daftar Desa/Kelurahan
                <p className="text-sm font-normal text-gray-500 mt-1">Kelola data desa/kelurahan dalam sistem.</p>
              </h3>
              <div className="flex flex-wrap items-center gap-3 w-full sm:w-auto justify-end">
                <div className="relative w-full sm:w-64">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg className="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                      <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
                    </svg>
                  </div>
                  <input
                    type="text"
                    className="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all"
                    placeholder="Cari Desa/Kelurahan..."
                    value={searchQuery}
                    onChange={onSearchChange}
                  />
                </div>

                <button
                  onClick={() => setShowAdvanced(!showAdvanced)}
                  className={`flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all border
                    ${showAdvanced ? 'bg-primary-50 border-primary-100 text-primary-600' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'}`}
                >
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                  </svg>
                  Advanced Filter
                </button>

                <div className="flex items-center gap-2">
                  <span className="text-xs font-bold text-gray-400 uppercase">Baris:</span>
                  <select
                    className="text-sm font-bold border-gray-200 rounded-lg focus:ring-primary-500 focus:border-primary-500 py-1 px-2 pr-8"
                    value={params.per_page}
                    onChange={(e) => handlePerPageChange(e.target.value)}
                  >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                </div>

                <PrimaryButton
                  onClick={openCreateModal}
                  className="justify-center whitespace-nowrap bg-primary-600 hover:bg-primary-700 text-white shadow-primary-200"
                >
                  <svg className="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                  </svg>
                  Tambah
                </PrimaryButton>
              </div>
            </div>

            {/* Advanced Filter Panel */}
            {showAdvanced && (
              <div className="px-6 py-6 bg-gray-50/50 border-b border-gray-100 animate-in fade-in slide-in-from-top-2 duration-300">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                  <div>
                    <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Provinsi</label>
                    <Select
                      options={provinceOptions}
                      styles={customSelectStyles}
                      value={provinceOptions.find(opt => opt.value === params.province_id) || null}
                      onChange={(opt) => handleFilterChange('province_id', opt ? opt.value : '')}
                      placeholder="Pilih Provinsi"
                      isSearchable
                      isClearable
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Kabupaten/Kota</label>
                    <Select
                      options={regencyOptions}
                      styles={customSelectStyles}
                      value={regencyOptions.find(opt => opt.value === params.regency_id) || null}
                      onChange={(opt) => handleFilterChange('regency_id', opt ? opt.value : '')}
                      placeholder={params.province_id ? "Pilih Kabupaten/Kota" : "Pilih Provinsi Dahulu"}
                      isSearchable
                      isClearable
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Kecamatan</label>
                    <Select
                      options={districtOptions}
                      styles={customSelectStyles}
                      value={districtOptions.find(opt => opt.value === params.district_id) || null}
                      onChange={(opt) => handleFilterChange('district_id', opt ? opt.value : '')}
                      placeholder={params.regency_id ? "Pilih Kecamatan" : (params.province_id ? "Pilih Kecamatan" : "Pilih Kabupaten Dahulu")}
                      isSearchable
                      isClearable
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">ID Desa/Kelurahan</label>
                    <input
                      type="text"
                      className="block w-full text-sm border-gray-200 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white px-3 py-[7px]"
                      placeholder="Contoh: 3501010001"
                      value={params.id}
                      onChange={(e) => handleFilterChange('id', e.target.value)}
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Nama Desa/Kelurahan</label>
                    <input
                      type="text"
                      className="block w-full text-sm border-gray-200 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white px-3 py-[7px]"
                      placeholder="Contoh: WRINGIN ANOM"
                      value={params.name}
                      onChange={(e) => handleFilterChange('name', e.target.value)}
                    />
                  </div>
                </div>

                <div className="mt-6 flex justify-end">
                  <button
                    onClick={resetFilters}
                    className="flex items-center gap-2 px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-50 rounded-xl transition-colors"
                  >
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Reset Filter
                  </button>
                </div>
              </div>
            )}

            {/* Table */}
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th
                      scope="col"
                      className="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors group"
                      onClick={() => handleSort('id')}
                    >
                      <div className="flex items-center gap-1">
                        ID <SortIcon field="id" />
                      </div>
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors group"
                      onClick={() => handleSort('district')}
                    >
                      <div className="flex items-center gap-1">
                        Kecamatan <SortIcon field="district" />
                      </div>
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors group"
                      onClick={() => handleSort('name')}
                    >
                      <div className="flex items-center gap-1">
                        Nama Desa/Kelurahan <SortIcon field="name" />
                      </div>
                    </th>
                    <th scope="col" className="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                      Aksi
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {villages.data.length > 0 ? (
                    villages.data.map((village) => (
                      <tr key={village.id} className="hover:bg-gray-50 transition-colors duration-150">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                          {village.id}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {village.district?.name || '-'}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                          {village.name}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                          <div className="flex justify-end gap-2">
                            <button
                              onClick={() => openEditModal(village)}
                              className="p-2 text-primary-600 hover:bg-primary-100 rounded-lg transition-colors shadow-sm bg-primary-50"
                              title="Edit"
                            >
                              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                              </svg>
                            </button>
                            <button
                              onClick={() => openDeleteModal(village)}
                              className="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors shadow-sm bg-red-50"
                              title="Hapus"
                            >
                              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                              </svg>
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan="4" className="px-6 py-12 text-center text-sm text-gray-500">
                        <div className="flex flex-col items-center justify-center">
                          <svg className="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                          </svg>
                          <p className="text-gray-500 text-lg font-medium">Tidak ada data desa/kelurahan</p>
                          <p className="text-gray-400">Silakan tambahkan data baru.</p>
                        </div>
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            <div className="bg-gray-50 px-6 py-4 border-t border-gray-200 rounded-b-2xl">
              <Pagination links={villages.links} />
            </div>
          </div>
        </div>
      </div>

      {/* Modal */}
      <Modal show={isModalOpen} onClose={closeModal}>
        <div className="p-6">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-xl font-bold text-gray-900">
              {modalMode === 'create' ? 'Tambah Desa/Kelurahan' : 'Edit Desa/Kelurahan'}
            </h2>
            <button onClick={closeModal} className="text-gray-400 hover:text-gray-500 transition-colors">
              <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <InputLabel htmlFor="id" value="ID Desa/Kelurahan" />
              <TextInput
                id="id"
                type="text"
                className={`mt-1 block w-full ${modalMode === 'edit' ? 'bg-gray-100' : ''}`}
                value={data.id}
                onChange={(e) => setData('id', e.target.value)}
                disabled={modalMode === 'edit'}
                isFocused={modalMode === 'create'}
                placeholder="Contoh: 3501010001"
              />
              <p className="mt-1 text-xs text-gray-500">Kode wilayah unik sesuai Kemendagri.</p>
              <InputError message={errors.id} className="mt-2" />
            </div>

            <div>
              <InputLabel htmlFor="district_id" value="Kecamatan" />
              <div className="mt-1">
                <Select
                  id="district_id"
                  options={districts.map(d => ({ value: d.id, label: `${d.regency?.name || ''} - ${d.name}` }))}
                  value={districts.map(d => ({ value: d.id, label: `${d.regency?.name || ''} - ${d.name}` })).find(opt => opt.value === data.district_id) || null}
                  onChange={(option) => setData('district_id', option ? option.value : '')}
                  placeholder="Pilih Kecamatan"
                  classNamePrefix="react-select"
                  styles={{
                    control: (base, state) => ({
                      ...base,
                      borderRadius: '0.5rem',
                      borderColor: state.isFocused ? '#6366f1' : '#d1d5db',
                      boxShadow: state.isFocused ? '0 0 0 1px #6366f1' : 'none',
                      '&:hover': {
                        borderColor: '#9ca3af'
                      }
                    })
                  }}
                />
              </div>
              <InputError message={errors.district_id} className="mt-2" />
            </div>

            <div>
              <InputLabel htmlFor="name" value="Nama Desa/Kelurahan" />
              <TextInput
                id="name"
                type="text"
                className="mt-1 block w-full"
                value={data.name}
                onChange={(e) => setData('name', e.target.value.toUpperCase())}
                placeholder="Contoh: WRINGIN ANOM"
              />
              <InputError message={errors.name} className="mt-2" />
            </div>

            <div className="flex justify-end gap-3 mt-8">
              <SecondaryButton onClick={closeModal} className="rounded-xl px-6 py-3 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:text-gray-900 h-[48px] transition-all duration-200 shadow-sm font-medium">
                Batal
              </SecondaryButton>
              <PrimaryButton disabled={processing} className="bg-primary-600 hover:bg-primary-700 text-white shadow-primary-200 h-[48px]">
                {processing ? 'Menyimpan...' : (modalMode === 'create' ? 'Simpan Data' : 'Perbarui Data')}
              </PrimaryButton>
            </div>
          </form>
        </div>
      </Modal>

      <Modal show={isDeleteModalOpen} onClose={closeDeleteModal}>
        <div className="p-6 text-center">
          <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <svg className="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">
            Hapus Data Desa/Kelurahan?
          </h2>
          <p className="text-gray-500 mb-6">
            Apakah Anda yakin ingin menghapus data <span className="font-semibold text-gray-800">"{currentVillage?.name}"</span>?
          </p>
          <div className="flex justify-center gap-3">
            <SecondaryButton onClick={closeDeleteModal} className="rounded-xl px-6 py-3 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:text-gray-900 h-[48px] transition-all duration-200 shadow-sm font-medium">
              Batal
            </SecondaryButton>
            <DangerButton onClick={handleDelete} disabled={processing}>
              Ya, Hapus
            </DangerButton>
          </div>
        </div>
      </Modal>
    </AuthenticatedLayout>
  );
}
