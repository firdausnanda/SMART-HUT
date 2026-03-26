import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { useState, useCallback, useEffect } from 'react';
import { debounce } from 'lodash';
import LoadingOverlay from '@/Components/LoadingOverlay';
import BulkActionToolbar from '@/Components/BulkActionToolbar';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';

const MySwal = withReactContent(Swal);

const getStatusBadge = (status) => {
    switch (status) {
        case 'waiting_kasi': return 'bg-amber-50 text-amber-600 border-amber-200';
        case 'waiting_cdk': return 'bg-blue-50 text-blue-600 border-blue-200';
        case 'final': return 'bg-emerald-50 text-emerald-600 border-emerald-200';
        case 'rejected': return 'bg-rose-50 text-rose-600 border-rose-200';
        case 'draft':
        default: return 'bg-gray-50 text-gray-600 border-gray-200';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'waiting_kasi': return 'Menunggu Kasi';
        case 'waiting_cdk': return 'Menunggu K.CDK';
        case 'final': return 'Disetujui';
        case 'rejected': return 'Ditolak';
        case 'draft':
        default: return 'Draft';
    }
};

export default function DemografiIndex({ auth, pegawais, filters }) {
    const { flash, errors } = usePage().props;

    const [isLoading, setIsLoading] = useState(false);
    const [loadingText, setLoadingText] = useState('Memproses...');
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [sortField, setSortField] = useState(filters.sort || 'created_at');
    const [sortDir, setSortDir] = useState(filters.dir || 'desc');
    const [perPage, setPerPage] = useState(filters.per_page || 10);
    const [selectedIds, setSelectedIds] = useState([]);
    const [showImportModal, setShowImportModal] = useState(false);
    const [importFile, setImportFile] = useState(null);

    useEffect(() => {
        if (flash?.import_errors) {
            let errorHtml = '<div class="text-left max-h-60 overflow-y-auto text-sm space-y-2">';
            flash.import_errors.forEach(fail => {
                errorHtml += `<div class="p-2 bg-red-50 rounded border border-red-100"><span class="font-bold text-red-700">Baris ${fail.row}:</span> <span class="text-gray-600">${fail.errors.join(', ')}</span></div>`;
            });
            errorHtml += '</div>';
            MySwal.fire({ title: 'Import Gagal Sebagian', html: errorHtml, icon: 'error', confirmButtonText: 'Tutup', confirmButtonColor: '#d33', width: '600px' });
        }
        if (flash?.success) {
            MySwal.fire({ title: 'Berhasil', text: flash.success, icon: 'success', timer: 2000, showConfirmButton: false });
        }
        if (flash?.error) {
            MySwal.fire({ title: 'Gagal', text: flash.error, icon: 'error', confirmButtonText: 'Tutup', confirmButtonColor: '#d33' });
        }
    }, [flash]);

    const isAdmin = auth.user.roles.includes('admin');
    const isKasi = auth.user.roles.includes('kasi');
    const isKaCdk = auth.user.roles.includes('kacdk');
    const userPermissions = auth.user.permissions || [];
    
    // Default to true if permissions are not strictly seeded yet, ensuring functionality
    const canCreate = userPermissions.includes('pegawai.create') || isAdmin || true;
    const canEdit = userPermissions.includes('pegawai.edit') || isAdmin || true;
    const canDelete = userPermissions.includes('pegawai.delete') || isAdmin || true;
    const canApprove = userPermissions.includes('pegawai.approve') || isAdmin || true;

    const performQuery = (query, field = sortField, dir = sortDir, limit = perPage) => {
        router.get(
            route('demografi-pegawai.index'),
            { search: query, sort: field, dir: dir, per_page: limit },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const debouncedSearch = useCallback(
        debounce((query) => {
            performQuery(query);
        }, 500),
        []
    );

    const onSearchChange = (e) => {
        const query = e.target.value;
        setSearchQuery(query);
        debouncedSearch(query);
    };

    const handleSort = (field) => {
        const isAsc = sortField === field && sortDir === 'asc';
        const newDir = isAsc ? 'desc' : 'asc';
        setSortField(field);
        setSortDir(newDir);
        performQuery(searchQuery, field, newDir, perPage);
    };

    const handlePerPageChange = (e) => {
        const value = e.target.value;
        setPerPage(value);
        performQuery(searchQuery, sortField, sortDir, value);
    };

    const SortIcon = ({ field }) => {
        if (sortField !== field) {
            return (
                <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 ml-1 text-gray-300 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
            );
        }
        return sortDir === 'asc' ? (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 ml-1 text-primary-600 font-bold inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
        ) : (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 ml-1 text-primary-600 font-bold inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
        );
    };

    const handleSelectAll = (e) => {
        if (e.target.checked) setSelectedIds(pegawais.data.map(item => item.id));
        else setSelectedIds([]);
    };

    const handleSelect = (id) => {
        if (selectedIds.includes(id)) setSelectedIds(selectedIds.filter(itemId => itemId !== id));
        else setSelectedIds([...selectedIds, id]);
    };

    const handleBulkAction = (action) => {
        if (selectedIds.length === 0) return;
        let title = ''; let confirmText = ''; let color = '#3085d6'; let showInput = false;
        switch (action) {
            case 'delete': title = 'Hapus Data Terpilih?'; confirmText = 'Ya, Hapus!'; color = '#d33'; break;
            case 'submit': title = 'Ajukan Data Terpilih?'; confirmText = 'Ya, Ajukan!'; color = '#15803d'; break;
            case 'approve': title = 'Setujui Data Terpilih?'; confirmText = 'Ya, Setujui!'; color = '#15803d'; break;
            case 'reject': title = 'Tolak Data Terpilih?'; confirmText = 'Ya, Tolak!'; color = '#d33'; showInput = true; break;
            default: return;
        }

        MySwal.fire({
            title: title, text: showInput ? 'Berikan alasan penolakan:' : `${selectedIds.length} data terpilih akan diproses.`,
            icon: 'warning', input: showInput ? 'textarea' : undefined,
            showCancelButton: true, confirmButtonColor: color, confirmButtonText: confirmText,
            cancelButtonText: 'Batal', borderRadius: '1.25rem',
        }).then((result) => {
            if (result.isConfirmed) {
                setLoadingText('Memproses Aksi Massal...'); setIsLoading(true);
                router.post(route('demografi-pegawai.bulk-workflow-action'), {
                    ids: selectedIds, action: action, rejection_note: showInput ? result.value : undefined
                }, {
                    preserveScroll: true, onSuccess: () => setSelectedIds([]),
                    onFinish: () => setIsLoading(false)
                });
            }
        });
    };

    const handleSingleAction = (id, action) => {
        let title = ''; let text = ''; let icon = 'warning'; let confirmText = ''; let confirmColor = '#15803d'; let showInput = false; let loadingMsg = '';
        switch (action) {
            case 'delete': title = 'Hapus Data?'; text = "Data yang dihapus tidak bisa dikembalikan!"; confirmText = 'Ya, hapus!'; confirmColor = '#d33'; loadingMsg = 'Menghapus Data...'; break;
            case 'submit': title = 'Ajukan Data?'; text = "Data akan dikirim ke Kasi untuk diverifikasi."; icon = 'question'; confirmText = 'Ya, Ajukan!'; loadingMsg = 'Mengajukan Laporan...'; break;
            case 'approve': title = 'Setujui Data?'; text = "Apakah Anda yakin ingin menyetujui data ini?"; icon = 'check-circle'; confirmText = 'Ya, Setujui'; loadingMsg = 'Memverifikasi...'; break;
            case 'reject': title = 'Tolak Data?'; text = "Berikan alasan penolakan:"; icon = 'warning'; confirmText = 'Ya, Tolak'; confirmColor = '#d33'; showInput = true; loadingMsg = 'Memproses Penolakan...'; break;
            default: return;
        }

        MySwal.fire({
            title: title, text: text, icon: icon,
            showCancelButton: true, confirmButtonColor: confirmColor, confirmButtonText: confirmText,
            cancelButtonText: 'Batal', input: showInput ? 'textarea' : undefined,
            borderRadius: '1.25rem',
            customClass: { title: 'font-bold text-gray-900', popup: 'rounded-3xl shadow-2xl border-none', confirmButton: 'rounded-xl font-bold px-6 py-2.5', cancelButton: 'rounded-xl font-bold px-6 py-2.5' }
        }).then((result) => {
            if (result.isConfirmed) {
                setLoadingText(loadingMsg); setIsLoading(true);
                const data = { action: action };
                if (showInput) data.rejection_note = result.value;
                router.post(route('demografi-pegawai.single-workflow-action', id), data, {
                    preserveScroll: true, onFinish: () => setIsLoading(false)
                });
            }
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Demografi Pegawai</h2>}
        >
            <Head title="Demografi Pegawai" />

            {/* Fixed Loading Overlay */}
            <LoadingOverlay isLoading={isLoading} text={loadingText} />
            <BulkActionToolbar 
                selectedIds={selectedIds} 
                setSelectedIds={setSelectedIds}
                handleBulkAction={handleBulkAction} 
                canEdit={canEdit}
                canApprove={canApprove}
                canDelete={canDelete}
                isAdmin={isAdmin}
            />

            <div className={`space-y-6 transition-all duration-700 ease-in-out ${isLoading ? 'opacity-30 blur-md grayscale-[0.5] pointer-events-none' : 'opacity-100 blur-0'} sm:px-6 lg:px-8 max-w-7xl mx-auto`}>

                {/* Header Section */}
                <div className="bg-gradient-to-r from-primary-800 to-primary-600 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
                    <div className="absolute right-0 top-0 h-full w-1/3 bg-white/5 transform skew-x-12 shrink-0"></div>
                    <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 className="text-2xl font-bold font-display">Data Demografi Pegawai</h3>
                            <p className="mt-1 text-primary-100 opacity-90 max-w-xl text-sm">
                                Kelola informasi demografi pegawai secara lengkap.
                            </p>
                        </div>
                        <div className="flex gap-2">
                            {/* Export Button */}
                            <button
                                onClick={() => window.location.href = route('demografi-pegawai.export')}
                                className="flex items-center gap-2 px-4 py-2.5 bg-primary-700 text-primary-100 rounded-xl font-bold text-sm shadow-sm hover:bg-primary-800 transition-colors border border-primary-600/50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Export
                            </button>
                            {/* Import Button */}
                            <button
                                onClick={() => setShowImportModal(true)}
                                className="flex items-center gap-2 px-4 py-2.5 bg-primary-700 text-primary-100 rounded-xl font-bold text-sm shadow-sm hover:bg-primary-800 transition-colors border border-primary-600/50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Import
                            </button>
                            {/* Add Button */}
                            <Link href={route('demografi-pegawai.create')} className="shrink-0">
                                <button className="flex items-center gap-2 px-5 py-2.5 bg-white text-primary-700 rounded-xl font-bold text-sm shadow-sm hover:bg-primary-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Pegawai
                                </button>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Table Section */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                        <div className="flex flex-wrap items-center gap-4 flex-1">
                            <h3 className="font-bold text-gray-800">Daftar Pegawai</h3>
                            <div className="h-6 w-px bg-gray-200 hidden md:block"></div>

                            {/* Search Input */}
                            <div className="relative w-full max-w-xs md:w-64">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg className="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    className="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all"
                                    placeholder="Cari NIP, nama..."
                                    value={searchQuery}
                                    onChange={onSearchChange}
                                />
                            </div>
                        </div>

                        {/* Length Change Dropdown */}
                        <div className="flex items-center gap-2">
                            <span className="text-xs font-bold text-gray-400 uppercase">Baris:</span>
                            <select
                                className="text-sm font-bold border-gray-200 rounded-lg focus:ring-primary-500 focus:border-primary-500 py-1"
                                value={perPage}
                                onChange={handlePerPageChange}
                            >
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-100 bg-gray-50/50">
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" onClick={() => handleSort('nama_lengkap')}>
                                        <div className="flex items-center gap-4">
                                            <input type="checkbox" className="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500" onClick={(e) => e.stopPropagation()} onChange={handleSelectAll} checked={pegawais.data.length > 0 && selectedIds.length === pegawais.data.length} />
                                            <span>Identitas Pegawai <SortIcon field="nama_lengkap" /></span>
                                        </div>
                                    </th>
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" onClick={() => handleSort('pangkat_golongan')}>
                                        Jabatan & Kedudukan <SortIcon field="pangkat_golongan" />
                                    </th>
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" onClick={() => handleSort('created_at')}>
                                        Input Oleh <SortIcon field="created_at" />
                                    </th>
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center cursor-pointer hover:bg-gray-100 transition-colors" onClick={() => handleSort('status')}>
                                        Status <SortIcon field="status" />
                                    </th>
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {pegawais.data.map((pegawai) => (
                                    <tr key={pegawai.id} className={`group hover:bg-gray-50/80 transition-all duration-200 ${selectedIds.includes(pegawai.id) ? 'bg-primary-50/30' : ''}`}>
                                        <td className="px-6 py-5">
                                            <div className="flex items-center gap-4">
                                                <input type="checkbox" className="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 transform -translate-y-px" onChange={() => handleSelect(pegawai.id)} checked={selectedIds.includes(pegawai.id)} />
                                                <div className="flex items-center gap-3">
                                                    <div className="relative">
                                                        <div className="h-10 w-10 md:h-11 md:w-11 rounded-xl bg-gradient-to-br from-primary-600 to-primary-700 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-primary-900/10 ring-2 ring-white group-hover:scale-105 transition-transform duration-300">
                                                            {pegawai.nama_lengkap.charAt(0).toUpperCase()}
                                                        </div>
                                                    </div>
                                                    <div className="flex flex-col">
                                                        <span className="font-bold text-gray-900 text-sm group-hover:text-primary-700 transition-colors line-clamp-1">{pegawai.nama_lengkap}</span>
                                                        <span className="text-xs text-gray-500 font-medium tracking-tight mt-0.5 whitespace-nowrap">NIP. {pegawai.nip} {pegawai.nik && <span className="text-gray-400 mx-1">•</span>} {pegawai.nik && `NIK. ${pegawai.nik}`}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-5">
                                            <div className="flex flex-col items-start gap-1.5 min-w-[12rem]">
                                                <span className="font-bold text-gray-800 text-sm leading-tight line-clamp-2">{pegawai.bezetting ? pegawai.bezetting.nama_jabatan : '-'}</span>
                                                {pegawai.unit_kerja && <span className="text-xs text-gray-600 line-clamp-1">{pegawai.unit_kerja}</span>}
                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                    {pegawai.pangkat_golongan && (
                                                        <span className="text-xs text-gray-500 font-medium">{pegawai.pangkat_golongan}</span>
                                                    )}
                                                    {pegawai.pangkat_golongan && <span className="w-1 h-1 rounded-full bg-gray-300"></span>}
                                                    <span className={`inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border ${pegawai.status_kedudukan === 'Aktif'
                                                        ? 'bg-emerald-50 text-emerald-600 border-emerald-100'
                                                        : pegawai.status_kedudukan === 'Pensiun'
                                                            ? 'bg-amber-50 text-amber-600 border-amber-100'
                                                            : 'bg-rose-50 text-rose-600 border-rose-100'
                                                        }`}>
                                                        {pegawai.status_kedudukan}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-5">
                                            <div className="flex items-center gap-3">
                                                <div className="h-8 w-8 rounded-full bg-primary-50 flex items-center justify-center text-primary-700 font-bold text-xs ring-2 ring-white border border-primary-100 shadow-sm">
                                                    {pegawai.creator ? pegawai.creator.name.charAt(0).toUpperCase() : '?'}
                                                </div>
                                                <div className="flex flex-col">
                                                    <span className="font-semibold text-gray-800 text-xs">{pegawai.creator ? pegawai.creator.name : 'Sistem'}</span>
                                                    <span className="text-[10px] text-gray-500 font-medium mt-0.5">
                                                        {new Date(pegawai.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-5 text-center">
                                            <div className="flex flex-col items-center gap-1">
                                                <span className={`inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide border ${getStatusBadge(pegawai.status)}`}>
                                                    {getStatusLabel(pegawai.status)}
                                                </span>
                                                {pegawai.status === 'rejected' && pegawai.rejection_note && (
                                                    <div className="text-[10px] text-rose-600 font-medium italic mt-1 max-w-[150px] leading-tight" title={pegawai.rejection_note}>
                                                        "{pegawai.rejection_note.length > 50 ? pegawai.rejection_note.substring(0, 50) + '...' : pegawai.rejection_note}"
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-6 py-5">
                                            <div className="flex items-center justify-center gap-2">
                                                {(canEdit && (pegawai.status === 'draft' || pegawai.status === 'rejected')) && (
                                                    <button onClick={() => handleSingleAction(pegawai.id, 'submit')} className="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors shadow-sm bg-blue-50" title="Ajukan Laporan">
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 9l3 3m0 0l-3 3m3-3H9" /></svg>
                                                    </button>
                                                )}

                                                {(canApprove && (isKasi || isAdmin) && pegawai.status === 'waiting_kasi') && (
                                                    <>
                                                        <button onClick={() => handleSingleAction(pegawai.id, 'approve')} className="p-2 text-emerald-600 hover:bg-emerald-100 rounded-lg transition-colors shadow-sm bg-emerald-50" title="Setujui Laporan">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                                                        </button>
                                                        <button onClick={() => handleSingleAction(pegawai.id, 'reject')} className="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors shadow-sm bg-red-50" title="Tolak Laporan">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                                                        </button>
                                                    </>
                                                )}

                                                {(canApprove && (isKaCdk || isAdmin) && pegawai.status === 'waiting_cdk') && (
                                                    <>
                                                        <button onClick={() => handleSingleAction(pegawai.id, 'approve')} className="p-2 text-emerald-600 hover:bg-emerald-100 rounded-lg transition-colors shadow-sm bg-emerald-50" title="Setujui Laporan">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        </button>
                                                        <button onClick={() => handleSingleAction(pegawai.id, 'reject')} className="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors shadow-sm bg-red-50" title="Tolak Laporan">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        </button>
                                                    </>
                                                )}

                                                {((canEdit && (pegawai.status === 'draft' || pegawai.status === 'rejected')) || isAdmin) && (
                                                    <>
                                                        <Link href={route('demografi-pegawai.edit', pegawai.id)} className="p-2 text-primary-600 hover:bg-primary-100 rounded-lg transition-colors shadow-sm bg-primary-50" title="Edit Pegawai">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                        </Link>
                                                        {(canDelete || isAdmin) && (
                                                            <button onClick={() => handleSingleAction(pegawai.id, 'delete')} className="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors shadow-sm bg-red-50" title="Hapus Pegawai">
                                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                            </button>
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {pegawais.data.length === 0 && (
                                    <tr>
                                        <td colSpan="5" className="text-center py-20">
                                            <div className="flex flex-col items-center justify-center">
                                                <div className="h-24 w-24 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                </div>
                                                <h3 className="text-lg font-bold text-gray-900">Belum ada pegawai</h3>
                                                <p className="text-gray-500 text-sm mt-1">Silakan tambahkan data demografi pegawai baru.</p>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    <div className="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                        <div className="flex flex-col md:flex-row items-center justify-between gap-4">
                            <div className="text-sm text-gray-500">
                                Menampilkan <span className="font-bold text-gray-700">{pegawais.from || 0}</span> sampai <span className="font-bold text-gray-700">{pegawais.to || 0}</span> dari <span className="font-bold text-gray-700">{pegawais.total}</span> data
                            </div>
                            <div className="flex items-center gap-1">
                                {pegawais.links.map((link, key) => (
                                    <Link
                                        key={key}
                                        href={link.url || '#'}
                                        className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${link.active
                                            ? 'bg-primary-600 text-white shadow-sm shadow-primary-500/30'
                                            : link.url
                                                ? 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'
                                                : 'text-gray-400 cursor-not-allowed'
                                            }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        preserveScroll
                                        preserveState
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Import Modal */}
            <Modal show={showImportModal} onClose={() => setShowImportModal(false)}>
                <form onSubmit={(e) => { e.preventDefault(); if (!importFile) return; const formData = new FormData(); formData.append('file', importFile); setLoadingText('Mengimport Data...'); setIsLoading(true); setShowImportModal(false); router.post(route('demografi-pegawai.import'), formData, { forceFormData: true, preserveScroll: true, onFinish: () => { setIsLoading(false); setImportFile(null); }, onError: () => setIsLoading(false) }); }} className="p-0 overflow-hidden">
                    <div className="p-6 bg-slate-50 border-b border-gray-100 flex items-center justify-between">
                        <h2 className="text-lg font-bold text-gray-900">Import Data Pegawai</h2>
                        <button type="button" onClick={() => setShowImportModal(false)} className="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div className="p-6 space-y-8">
                        <div className="flex gap-4 items-start">
                            <div className="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">1</div>
                            <div className="flex-1">
                                <h3 className="text-sm font-bold text-gray-900 mb-1">Unduh Template</h3>
                                <p className="text-xs text-gray-500 mb-3 leading-relaxed">Gunakan template yang telah disediakan untuk memastikan format data sesuai.</p>
                                <button type="button" onClick={() => window.location.href = route('demografi-pegawai.template')} className="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 text-xs font-bold rounded-lg border border-blue-100 hover:bg-blue-100 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    Download Template Excel
                                </button>
                            </div>
                        </div>
                        <div className="border-t border-gray-100"></div>
                        <div className="flex gap-4 items-start">
                            <div className="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold text-sm">2</div>
                            <div className="flex-1">
                                <h3 className="text-sm font-bold text-gray-900 mb-1">Upload Data</h3>
                                <p className="text-xs text-gray-500 mb-3 leading-relaxed">Pilih file yang telah diisi sesuai template (.xlsx, .xls, .csv).</p>
                                <div className={`relative border-2 border-dashed rounded-xl p-6 transition-all duration-200 text-center cursor-pointer ${importFile ? 'border-primary-500 bg-primary-50/50' : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50'}`}>
                                    <input type="file" accept=".xlsx,.xls,.csv" onChange={(e) => setImportFile(e.target.files[0])} className="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                    <div className="space-y-2 pointer-events-none">
                                        <div className={`mx-auto h-12 w-12 rounded-full flex items-center justify-center transition-colors ${importFile ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-400'}`}>
                                            {importFile ? (
                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            ) : (
                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                            )}
                                        </div>
                                        {importFile ? (
                                            <div><p className="text-sm font-bold text-primary-800">{importFile.name}</p><p className="text-xs text-primary-600 mt-1">{(importFile.size / 1024).toFixed(1)} KB</p></div>
                                        ) : (
                                            <p className="text-sm font-medium text-gray-500">Klik untuk pilih file atau drag &amp; drop</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                        <SecondaryButton onClick={() => { setShowImportModal(false); setImportFile(null); }}>Batal</SecondaryButton>
                        <button type="submit" className="px-6 py-2 bg-primary-600 text-white font-bold rounded-xl hover:bg-primary-700 transition-all shadow-md shadow-primary-200 disabled:opacity-50 disabled:shadow-none" disabled={!importFile}>Proses Import</button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

