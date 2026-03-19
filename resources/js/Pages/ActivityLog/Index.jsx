import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState, useCallback, useMemo } from 'react';
import { debounce } from 'lodash';
import Pagination from '@/Components/Pagination';
import Select from 'react-select';

export default function Index({ auth, activities, filters, options }) {
  const [params, setParams] = useState({
    search: filters.search || '',
    per_page: filters.per_page || 10,
    sort: filters.sort || '',
    direction: filters.direction || 'desc',
    user_id: filters.user_id || '',
    action: filters.action || '',
    subject_type: filters.subject_type || '',
    subject_id: filters.subject_id || '',
    date_start: filters.date_start || '',
    date_end: filters.date_end || '',
  });
  const [searchQuery, setSearchQuery] = useState(filters.search || '');
  const [showAdvanced, setShowAdvanced] = useState(
    !!(filters.user_id || filters.action || filters.subject_type || filters.subject_id || filters.date_start || filters.date_end)
  );

  // Transform options for react-select
  const userOptions = useMemo(() => [
    { value: '', label: 'Semua User' },
    ...options.users.map(user => ({ value: user.id.toString(), label: user.name }))
  ], [options.users]);

  const actionOptions = useMemo(() => [
    { value: '', label: 'Semua Aksi' },
    ...options.actions.map(action => ({ value: action, label: action.charAt(0).toUpperCase() + action.slice(1) }))
  ], [options.actions]);

  const subjectTypeOptions = useMemo(() => [
    { value: '', label: 'Semua Model' },
    ...options.subject_types.map(type => ({ value: type.value, label: type.label }))
  ], [options.subject_types]);

  // React-select custom styles
  const customSelectStyles = {
    control: (provided, state) => ({
      ...provided,
      borderRadius: '0.75rem',
      borderColor: state.isFocused ? '#10b981' : '#e5e7eb',
      boxShadow: state.isFocused ? '0 0 0 2px rgba(16, 185, 129, 0.2)' : 'none',
      '&:hover': {
        borderColor: state.isFocused ? '#10b981' : '#d1d5db',
      },
      minHeight: '38px',
      fontSize: '0.875rem',
      backgroundColor: 'white',
    }),
    option: (provided, state) => ({
      ...provided,
      backgroundColor: state.isSelected ? '#10b981' : state.isFocused ? '#ecfdf5' : 'white',
      color: state.isSelected ? 'white' : '#374151',
      fontSize: '0.875rem',
      cursor: 'pointer',
      '&:active': {
        backgroundColor: '#10b981',
      },
    }),
    placeholder: (provided) => ({ ...provided, color: '#9ca3af' }),
    singleValue: (provided) => ({ ...provided, color: '#374151' }),
  };

  const applyFilters = (newParams) => {
    router.get(
      route('activity-log.index'),
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
    setParams(newParams);
    applyFilters(newParams);
  };

  const resetFilters = () => {
    const defaultParams = {
      search: '',
      per_page: 10,
      sort: '',
      direction: 'desc',
      user_id: '',
      action: '',
      subject_type: '',
      subject_id: '',
      date_start: '',
      date_end: '',
    };
    setParams(defaultParams);
    setSearchQuery('');
    applyFilters(defaultParams);
  };

  const handlePerPageChange = (perPage) => {
    handleFilterChange('per_page', perPage);
  };

  const handleSort = (field) => {
    let direction = 'desc';
    if (params.sort === field && params.direction === 'desc') {
      direction = 'asc';
    } else if (params.sort === field && params.direction === 'asc') {
      direction = 'desc';
    }
    const newParams = { ...params, sort: field, direction };
    setParams(newParams);
    applyFilters(newParams);
  };

  const SortIcon = ({ field }) => {
    return (
      <div className="w-4 h-4 ml-1 text-gray-500">
        {params.sort === field ? (
          params.direction === 'asc' ? (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
            </svg>
          ) : (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
          )
        ) : (
          <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
          </svg>
        )}
      </div>
    );
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Log Aktivitas</h2>}
    >
      <Head title="Log Aktivitas" />

      <div className="space-y-6">
        {/* Header Section */}
        <div className="bg-gradient-to-r from-primary-800 to-primary-600 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
          <div className="absolute right-0 top-0 h-full w-1/3 bg-white/5 transform skew-x-12 shrink-0"></div>
          <div className="relative z-10">
            <h3 className="text-2xl font-bold font-display">Log Aktivitas</h3>
            <p className="mt-1 text-primary-100 opacity-90 max-w-xl text-sm">
              Memantau seluruh aktivitas pengguna dalam aplikasi.
            </p>
          </div>
        </div>

        {/* Table Section */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="p-6 border-b border-gray-100 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div className="flex items-center gap-4">
              <h3 className="font-bold text-gray-800">Riwayat Aktivitas</h3>
              <div className="h-6 w-px bg-gray-200"></div>
              <div className="text-sm text-gray-400 font-bold bg-gray-50 px-3 py-1 rounded-full border border-gray-100">
                {activities.total} Item
              </div>
            </div>

            {/* Search Input and Advanced Toggle */}
            <div className="flex flex-wrap items-center gap-4 flex-1 justify-end">
              <div className="relative w-full md:w-64">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg className="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
                  </svg>
                </div>
                <input
                  type="text"
                  className="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all"
                  placeholder="Cari aktivitas..."
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
                  className="text-sm font-bold border-gray-200 rounded-lg focus:ring-primary-500 focus:border-primary-500 py-1"
                  value={params.per_page || 10}
                  onChange={(e) => handlePerPageChange(e.target.value)}
                >
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>
            </div>
          </div>

          {/* Advanced Filter Panel */}
          {showAdvanced && (
            <div className="px-6 py-6 bg-gray-50/50 border-b border-gray-100 animate-in fade-in slide-in-from-top-2 duration-300">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                <div>
                  <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">User</label>
                  <Select
                    options={userOptions}
                    styles={customSelectStyles}
                    value={userOptions.find(opt => opt.value === params.user_id.toString())}
                    onChange={(opt) => handleFilterChange('user_id', opt ? opt.value : '')}
                    placeholder="Pilih User"
                    isSearchable
                  />
                </div>

                <div>
                  <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Aksi</label>
                  <Select
                    options={actionOptions}
                    styles={customSelectStyles}
                    value={actionOptions.find(opt => opt.value === params.action)}
                    onChange={(opt) => handleFilterChange('action', opt ? opt.value : '')}
                    placeholder="Pilih Aksi"
                    isSearchable
                  />
                </div>

                <div>
                  <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Tipe Model</label>
                  <Select
                    options={subjectTypeOptions}
                    styles={customSelectStyles}
                    value={subjectTypeOptions.find(opt => opt.value === params.subject_type)}
                    onChange={(opt) => handleFilterChange('subject_type', opt ? opt.value : '')}
                    placeholder="Pilih Model"
                    isSearchable
                  />
                </div>

                <div>
                  <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Subject ID</label>
                  <input
                    type="number"
                    className="block w-full text-sm border-gray-200 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white px-3 py-[7px]"
                    placeholder="Contoh: 123"
                    value={params.subject_id}
                    onChange={(e) => handleFilterChange('subject_id', e.target.value)}
                  />
                </div>

                <div>
                  <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Dari Tanggal</label>
                  <input
                    type="date"
                    className="block w-full text-sm border-gray-200 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white px-3 py-1.5"
                    value={params.date_start}
                    onChange={(e) => handleFilterChange('date_start', e.target.value)}
                  />
                </div>

                <div>
                  <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Sampai Tanggal</label>
                  <input
                    type="date"
                    className="block w-full text-sm border-gray-200 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white px-3 py-1.5"
                    value={params.date_end}
                    onChange={(e) => handleFilterChange('date_end', e.target.value)}
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

          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="border-b border-gray-100 bg-gray-50/50">
                  <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors group" onClick={() => handleSort('created_at')}>
                    <div className="flex items-center gap-1">
                      Waktu <SortIcon field="created_at" />
                    </div>
                  </th>
                  <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors group" onClick={() => handleSort('causer_id')}>
                    <div className="flex items-center gap-1">
                      User <SortIcon field="causer_id" />
                    </div>
                  </th>
                  <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors group" onClick={() => handleSort('description')}>
                    <div className="flex items-center gap-1">
                      Aktivitas <SortIcon field="description" />
                    </div>
                  </th>
                  <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors group" onClick={() => handleSort('subject_type')}>
                    <div className="flex items-center gap-1">
                      Subjek <SortIcon field="subject_type" />
                    </div>
                  </th>
                  <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Perubahan</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 bg-white">
                {activities.data.map((activity) => (
                  <tr key={activity.id} className="hover:bg-gray-50/80 transition-colors">
                    <td className="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                      {formatDate(activity.created_at)}
                    </td>
                    <td className="px-6 py-4">
                      {activity.causer ? (
                        <div className="flex flex-col">
                          <span className="font-bold text-gray-900 text-sm">{activity.causer.name}</span>
                          <span className="text-xs text-gray-500">{activity.causer.email}</span>
                        </div>
                      ) : (
                        <span className="text-gray-400 italic">System</span>
                      )}
                    </td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold capitalize
                                                ${activity.description === 'created' ? 'bg-green-100 text-green-800' :
                          activity.description === 'updated' ? 'bg-blue-100 text-blue-800' :
                            activity.description === 'deleted' ? 'bg-red-100 text-red-800' :
                              'bg-gray-100 text-gray-800'}`}>
                        {activity.description}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {activity.subject_type ? activity.subject_type.split('\\').pop() : '-'} #{activity.subject_id}
                    </td>
                    <td className="px-6 py-4 text-xs text-gray-500 font-mono">
                      <details className="cursor-pointer">
                        <summary className="text-primary-600 hover:text-primary-700">Lihat Detail</summary>
                        <pre className="mt-2 p-2 bg-gray-50 rounded border border-gray-200 overflow-x-auto max-w-xs">
                          {JSON.stringify(activity.properties, null, 2)}
                        </pre>
                      </details>
                    </td>
                  </tr>
                ))}
                {activities.data.length === 0 && (
                  <tr>
                    <td colSpan="5" className="text-center py-20 text-gray-500">
                      Belum ada aktivitas tercatat.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          <div className="px-6 py-4 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4 bg-gray-50/50">
            <Pagination links={activities.links} />
            {activities.total > 0 && (
              <div className="text-sm text-gray-500 text-center md:text-right">
                Menampilkan <span className="font-bold text-gray-900">{activities.from}</span> sampai <span className="font-bold text-gray-900">{activities.to}</span> dari <span className="font-bold text-gray-900">{activities.total}</span> data
              </div>
            )}
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
