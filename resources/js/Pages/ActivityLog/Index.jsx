import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState, useCallback } from 'react';
import { debounce } from 'lodash';
import Pagination from '@/Components/Pagination';

export default function Index({ auth, activities, filters }) {
  const [searchQuery, setSearchQuery] = useState(filters.search || '');

  const handleSearch = useCallback(
    debounce((query) => {
      router.get(
        route('activity-log.index'),
        { search: query },
        {
          preserveState: true,
          preserveScroll: true,
          replace: true,
        }
      );
    }, 500),
    []
  );

  const onSearchChange = (e) => {
    const query = e.target.value;
    setSearchQuery(query);
    handleSearch(query);
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

            {/* Search Input */}
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
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="border-b border-gray-100 bg-gray-50/50">
                  <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu</th>
                  <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">User</th>
                  <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Aktivitas</th>
                  <th className="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Subjek</th>
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

          <div className="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            <Pagination links={activities.links} />
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
