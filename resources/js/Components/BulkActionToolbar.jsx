import React from 'react';

export default function BulkActionToolbar({
  selectedIds,
  setSelectedIds,
  handleBulkAction,
  canEdit,
  canApprove,
  canDelete,
  isAdmin
}) {
  if (selectedIds.length === 0) return null;

  return (
    <div className="fixed bottom-4 left-4 right-4 md:bottom-6 md:left-1/2 md:right-auto md:w-auto md:transform md:-translate-x-1/2 bg-white rounded-2xl shadow-2xl border border-gray-100 p-3 md:p-4 z-50 flex flex-col sm:flex-row items-center justify-between md:justify-center gap-3 md:gap-4 animate-in slide-in-from-bottom-5 duration-300">
      <div className="flex items-center justify-between w-full sm:w-auto gap-4">
        <div className="flex items-center gap-2 px-3 py-1 bg-gray-100 rounded-lg whitespace-nowrap">
          <span className="font-bold text-gray-700">{selectedIds.length}</span>
          <span className="text-xs font-semibold text-gray-500 uppercase">Dipilih</span>
        </div>

        <button
          onClick={() => setSelectedIds([])}
          className="sm:hidden p-2 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-gray-600 transition-colors"
          title="Batalkan Pilihan"
        >
          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div className="hidden sm:block h-8 w-px bg-gray-200"></div>

      <div className="flex items-center justify-center gap-2 w-full sm:w-auto overflow-x-auto sm:overflow-visible pb-1 sm:pb-0">
        {/* Bulk Submit - Only for Creator/Admin and Draft/Rejected */}
        {(canEdit || isAdmin) && (
          <button
            onClick={() => handleBulkAction('submit')}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm transition-colors shadow-sm shadow-blue-200 whitespace-nowrap flex-shrink-0"
          >
            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Ajukan
          </button>
        )}

        {/* Bulk Approve - Only for Kasi/KaCDK */}
        {(canApprove || isAdmin) && (
          <button
            onClick={() => handleBulkAction('approve')}
            className="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm transition-colors shadow-sm shadow-emerald-200 whitespace-nowrap flex-shrink-0"
          >
            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
            Setujui
          </button>
        )}

        {/* Bulk Reject - Only for Kasi/KaCDK */}
        {(canApprove || isAdmin) && (
          <button
            onClick={() => handleBulkAction('reject')}
            className="flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-sm transition-colors shadow-sm shadow-amber-200 whitespace-nowrap flex-shrink-0"
          >
            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Tolak
          </button>
        )}

        {/* Bulk Delete - Only for Creator/Admin */}
        {(canDelete || isAdmin) && (
          <button
            onClick={() => handleBulkAction('delete')}
            className="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-sm transition-colors shadow-sm shadow-red-200 whitespace-nowrap flex-shrink-0"
          >
            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Hapus
          </button>
        )}
      </div>

      <button
        onClick={() => setSelectedIds([])}
        className="hidden sm:block p-2 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-gray-600 transition-colors"
        title="Batalkan Pilihan"
      >
        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  );
}
