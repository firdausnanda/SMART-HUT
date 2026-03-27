import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage, useForm } from '@inertiajs/react';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { useState, useCallback, useEffect } from 'react';
import { debounce } from 'lodash';
import LoadingOverlay from '@/Components/LoadingOverlay';
import BulkActionToolbar from '@/Components/BulkActionToolbar';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

const MySwal = withReactContent(Swal);

const getStatusBadge = (status) => {
    switch (status) {
        case 'waiting_kasi': return 'bg-amber-50/50 text-amber-700 border-amber-200/60 shadow-sm shadow-amber-500/5';
        case 'waiting_cdk': return 'bg-blue-50/50 text-blue-700 border-blue-200/60 shadow-sm shadow-blue-500/5';
        case 'final': return 'bg-emerald-50/50 text-emerald-700 border-emerald-200/60 shadow-sm shadow-emerald-500/5';
        case 'rejected': return 'bg-rose-50/50 text-rose-700 border-rose-200/60 shadow-sm shadow-rose-500/5';
        case 'draft':
        default: return 'bg-slate-50/50 text-slate-600 border-slate-200/60 shadow-sm shadow-slate-500/5';
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

export default function Bezetting({ auth, bezettings, filters }) {
    const { flash } = usePage().props;

    const [isLoading, setIsLoading] = useState(false);
    const [loadingText, setLoadingText] = useState('Memproses...');
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [sortField, setSortField] = useState(filters.sort || 'nama_jabatan');
    const [sortDir, setSortDir] = useState(filters.dir || 'asc');
    const [perPage, setPerPage] = useState(filters.per_page || 10);
    const [selectedIds, setSelectedIds] = useState([]);

    // Create/Edit Modal State
    const [showModal, setShowModal] = useState(false);
    const [editData, setEditData] = useState(null);

    const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm({
        nama_jabatan: '',
        kebutuhan: '',
    });

    useEffect(() => {
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

    const canCreate = userPermissions.includes('kepegawaian.create') || isAdmin;
    const canEdit = userPermissions.includes('kepegawaian.edit') || isAdmin;
    const canDelete = userPermissions.includes('kepegawaian.delete') || isAdmin;
    const canApprove = userPermissions.includes('kepegawaian.approve') || isAdmin;

    const performQuery = (query, field = sortField, dir = sortDir, limit = perPage) => {
        router.get(
            route('bezetting-jabatan.index'),
            { search: query, sort: field, dir: dir, per_page: limit },
            { 
                preserveState: true, 
                preserveScroll: true, 
                replace: true,
                onStart: () => setIsLoading(true),
                onFinish: () => setIsLoading(false)
            }
        );
    };

    const debouncedSearch = useCallback(
        debounce((query) => { performQuery(query); }, 500),
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
        setLoadingText('Menyiapkan Data...');
        performQuery(searchQuery, sortField, sortDir, value);
    };

    const SortIcon = ({ field }) => {
        if (sortField !== field) return <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 ml-1 text-gray-300 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>;
        return sortDir === 'asc' ? <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 ml-1 text-primary-600 font-bold inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg> : <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 ml-1 text-primary-600 font-bold inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>;
    };

    const handleSelectAll = (e) => {
        if (e.target.checked) setSelectedIds(bezettings.data.map(item => item.id));
        else setSelectedIds([]);
    };

    const handleSelect = (id) => {
        if (selectedIds.includes(id)) setSelectedIds(selectedIds.filter(itemId => itemId !== id));
        else setSelectedIds([...selectedIds, id]);
    };

    const openCreateModal = () => {
        setEditData(null);
        reset();
        clearErrors();
        setShowModal(true);
    };

    const openEditModal = (bezetting) => {
        setEditData(bezetting);
        setData({
            nama_jabatan: bezetting.nama_jabatan,
            kebutuhan: bezetting.kebutuhan,
        });
        clearErrors();
        setShowModal(true);
    };

    const closeModal = () => {
        setShowModal(false);
        setEditData(null);
        reset();
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editData) {
            patch(route('bezetting-jabatan.update', editData.id), {
                onSuccess: () => closeModal(),
            });
        } else {
            post(route('bezetting-jabatan.store'), {
                onSuccess: () => closeModal(),
            });
        }
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
                router.post(route('bezetting-jabatan.bulk-workflow-action'), {
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
            case 'submit': title = 'Ajukan Data?'; text = "Data akan dikirim ke Kasi untuk diverifikasi."; icon = 'question'; confirmText = 'Ya, Ajukan!'; loadingMsg = 'Mengajukan...'; break;
            case 'approve': title = 'Setujui Data?'; text = "Apakah Anda yakin ingin menyetujui data ini?"; icon = 'check-circle'; confirmText = 'Ya, Setujui'; loadingMsg = 'Memverifikasi...'; break;
            case 'reject': title = 'Tolak Data?'; text = "Berikan alasan penolakan:"; icon = 'warning'; confirmText = 'Ya, Tolak'; confirmColor = '#d33'; showInput = true; loadingMsg = 'Memproses Penolakan...'; break;
            default: return;
        }

        MySwal.fire({
            title: title, text: text, icon: icon, showCancelButton: true, confirmButtonColor: confirmColor, confirmButtonText: confirmText,
            cancelButtonText: 'Batal', input: showInput ? 'textarea' : undefined, borderRadius: '1.25rem',
        }).then((result) => {
            if (result.isConfirmed) {
                setLoadingText(loadingMsg); setIsLoading(true);
                const actionData = { action: action };
                if (showInput) actionData.rejection_note = result.value;
                router.post(route('bezetting-jabatan.single-workflow-action', id), actionData, {
                    preserveScroll: true, onFinish: () => setIsLoading(false)
                });
            }
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Bezetting Jabatan</h2>}
        >
            <Head title="Bezetting Jabatan" />

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
                            <h3 className="text-2xl font-bold font-display">Bezetting Jabatan</h3>
                            <p className="mt-1 text-primary-100 opacity-90 max-w-xl text-sm">
                                Perbandingan antara kebutuhan (ABK) dengan realitas jumlah pegawai.
                            </p>
                        </div>
                        <div className="flex gap-2">
                            {canCreate && (
                                <button
                                    onClick={openCreateModal}
                                    className="flex items-center gap-2 px-5 py-2.5 bg-white text-primary-700 rounded-xl font-bold text-sm shadow-sm hover:bg-primary-50 transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Jabatan
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Table Section */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100 flex flex-col md:flex-row md:justify-between md:items-center gap-6 bg-slate-50/20">
                        <div className="flex flex-wrap items-center gap-4 flex-1">
                            <h3 className="font-bold text-gray-800">Daftar Jabatan</h3>
                            <div className="h-6 w-px bg-gray-200 hidden md:block"></div>

                            <div className="relative w-full max-w-xs md:w-64">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg className="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" /></svg>
                                </div>
                                <input
                                    type="text"
                                    className="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all"
                                    placeholder="Cari nama jabatan..."
                                    value={searchQuery}
                                    onChange={onSearchChange}
                                />
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <span className="text-xs font-bold text-gray-400 uppercase">Baris:</span>
                            <select className="text-sm font-bold border-gray-200 rounded-lg focus:ring-primary-500 focus:border-primary-500 py-1" value={perPage} onChange={handlePerPageChange}>
                                <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="border-b border-gray-100 bg-slate-50/40">
                                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100/50 transition-colors group/th" onClick={() => handleSort('nama_jabatan')}>
                                    <div className="flex items-center gap-4">
                                        <div className="relative flex items-center">
                                            <input type="checkbox" className="w-4 h-4 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 cursor-pointer transition-all hover:border-primary-400" onClick={(e) => e.stopPropagation()} onChange={handleSelectAll} checked={bezettings.data.length > 0 && selectedIds.length === bezettings.data.length} />
                                        </div>
                                        <span className="flex items-center gap-1 group-hover/th:text-primary-700 transition-colors">Nama Jabatan <SortIcon field="nama_jabatan" /></span>
                                    </div>
                                </th>
                                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center cursor-pointer hover:bg-slate-100/50 transition-colors group/th" onClick={() => handleSort('kebutuhan')}>
                                    <div className="flex items-center justify-center gap-1 group-hover/th:text-primary-700 transition-colors">
                                        Kebutuhan <SortIcon field="kebutuhan" />
                                    </div>
                                </th>
                                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center cursor-pointer hover:bg-slate-100/50 transition-colors group/th" onClick={() => handleSort('realitas')}>
                                    <div className="flex items-center justify-center gap-1 group-hover/th:text-primary-700 transition-colors">
                                        Realitas <SortIcon field="realitas" />
                                    </div>
                                </th>
                                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">
                                    Selisih
                                </th>
                                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center cursor-pointer hover:bg-slate-100/50 transition-colors group/th" onClick={() => handleSort('status')}>
                                    <div className="flex items-center justify-center gap-1 group-hover/th:text-primary-700 transition-colors">
                                        Status <SortIcon field="status" />
                                    </div>
                                </th>
                                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 bg-white">
                            {bezettings.data.map((item) => {
                                const selisih = item.kebutuhan - item.realitas;
                                return (
                                    <tr key={item.id} className={`group hover:bg-slate-50/80 transition-all duration-300 relative ${selectedIds.includes(item.id) ? 'bg-primary-50/40' : ''}`}>
                                        <td className="px-6 py-5 relative">
                                            {selectedIds.includes(item.id) && <div className="absolute left-0 top-0 bottom-0 w-1 bg-primary-500 rounded-r-full shadow-[0_0_8px_rgba(59,130,246,0.3)]"></div>}
                                            <div className="flex items-center gap-4">
                                                <div className="relative flex items-center">
                                                    <input type="checkbox" className="w-4 h-4 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 cursor-pointer transition-all hover:border-primary-400 transform hover:scale-110" onChange={() => handleSelect(item.id)} checked={selectedIds.includes(item.id)} />
                                                </div>
                                                <div className="flex flex-col min-w-0">
                                                    <span className="font-bold text-slate-900 text-[14px] group-hover:text-primary-700 transition-colors truncate">{item.nama_jabatan}</span>
                                                    <div className="flex items-center gap-1.5 mt-0.5">
                                                        <div className="w-1 h-1 rounded-full bg-slate-300"></div>
                                                        <span className="text-[11px] text-slate-500 font-medium">Input Oleh: <span className="text-slate-700">{item.creator ? item.creator.name : 'Sistem'}</span></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-5 text-center">
                                            <span className="inline-block px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg font-bold text-xs ring-1 ring-inset ring-slate-200/50">
                                                {item.kebutuhan}
                                            </span>
                                        </td>
                                        <td className="px-6 py-5 text-center">
                                            <span className="inline-block px-2.5 py-1 bg-primary-50 text-primary-700 rounded-lg font-bold text-xs ring-1 ring-inset ring-primary-200/50">
                                                {item.realitas}
                                            </span>
                                        </td>
                                        <td className="px-6 py-5 text-center">
                                            <span className={`inline-flex items-center justify-center font-bold px-3 py-1.5 rounded-xl text-[10px] uppercase tracking-wider relative overflow-hidden transition-all duration-300 ${selisih === 0 ? 'bg-slate-50 text-slate-500 border border-slate-200' : selisih > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200/60 shadow-sm shadow-rose-500/5' : 'bg-emerald-50 text-emerald-700 border border-emerald-200/60 shadow-sm shadow-emerald-500/5'}`}>
                                                {selisih > 0 ? (
                                                    <>
                                                        <span className="relative z-10 flex items-center gap-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fillRule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clipRule="evenodd" /></svg>
                                                            Kurang {selisih}
                                                        </span>
                                                    </>
                                                ) : selisih < 0 ? (
                                                    <>
                                                        <span className="relative z-10 flex items-center gap-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fillRule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clipRule="evenodd" /></svg>
                                                            Lebih {Math.abs(selisih)}
                                                        </span>
                                                    </>
                                                ) : (
                                                    <span className="relative z-10 flex items-center gap-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" /></svg>
                                                        Sesuai
                                                    </span>
                                                )}
                                            </span>
                                        </td>
                                        <td className="px-6 py-5 text-center">
                                            <span className={`inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest border transition-all duration-300 shadow-sm ${getStatusBadge(item.status)}`}>
                                                <div className="w-1.5 h-1.5 rounded-full mr-2 opacity-80 animate-pulse bg-current"></div>
                                                {getStatusLabel(item.status)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-5 text-center">
                                            <div className="flex items-center justify-center gap-1.5">
                                                {(canEdit && (item.status === 'draft' || item.status === 'rejected')) && (
                                                    <button
                                                        onClick={(e) => { e.stopPropagation(); handleSingleAction(item.id, 'submit'); }}
                                                        className="p-2.5 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all duration-200 shadow-sm hover:shadow-md bg-blue-50 border border-blue-100/50"
                                                        title="Ajukan Verifikasi"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 9l3 3m0 0l-3 3m3-3H9" /></svg>
                                                    </button>
                                                )}
                                                {(canApprove && (isKasi || isAdmin) && item.status === 'waiting_kasi') && (
                                                    <>
                                                        <button
                                                            onClick={(e) => { e.stopPropagation(); handleSingleAction(item.id, 'approve'); }}
                                                            className="p-2.5 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-xl transition-all duration-200 shadow-sm hover:shadow-md bg-emerald-50 border border-emerald-100/50"
                                                            title="Setujui Data"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7" /></svg>
                                                        </button>
                                                        <button
                                                            onClick={(e) => { e.stopPropagation(); handleSingleAction(item.id, 'reject'); }}
                                                            className="p-2.5 text-rose-600 hover:bg-rose-600 hover:text-white rounded-xl transition-all duration-200 shadow-sm hover:shadow-md bg-rose-50 border border-rose-100/50"
                                                            title="Tolak Data"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M6 18L18 6M6 6l12 12" /></svg>
                                                        </button>
                                                    </>
                                                )}
                                                {(canApprove && (isKaCdk || isAdmin) && item.status === 'waiting_cdk') && (
                                                    <>
                                                        <button
                                                            onClick={(e) => { e.stopPropagation(); handleSingleAction(item.id, 'approve'); }}
                                                            className="p-2.5 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-xl transition-all duration-200 shadow-sm hover:shadow-md bg-emerald-50 border border-emerald-100/50"
                                                            title="Setujui Akhir"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        </button>
                                                        <button
                                                            onClick={(e) => { e.stopPropagation(); handleSingleAction(item.id, 'reject'); }}
                                                            className="p-2.5 text-rose-600 hover:bg-rose-600 hover:text-white rounded-xl transition-all duration-200 shadow-sm hover:shadow-md bg-rose-50 border border-rose-100/50"
                                                            title="Tolak Data"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        </button>
                                                    </>
                                                )}
                                                {((canEdit && (item.status === 'draft' || item.status === 'rejected')) || isAdmin) && (
                                                    <>
                                                        <button
                                                            onClick={(e) => { e.stopPropagation(); openEditModal(item); }}
                                                            className="p-2.5 text-primary-600 hover:bg-primary-600 hover:text-white rounded-xl transition-all duration-200 shadow-sm hover:shadow-md bg-primary-50 border border-primary-100/50"
                                                            title="Edit Jabatan"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                        </button>
                                                        {(canDelete || isAdmin) && (
                                                            <button
                                                                onClick={(e) => { e.stopPropagation(); handleSingleAction(item.id, 'delete'); }}
                                                                className="p-2.5 text-slate-500 hover:bg-rose-600 hover:text-white rounded-xl transition-all duration-200 shadow-sm hover:shadow-md bg-slate-50 border border-slate-200/50"
                                                                title="Hapus Jabatan"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                            </button>
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                            {bezettings.data.length === 0 && (
                                <tr>
                                    <td colSpan="6" className="text-center py-24">
                                        <div className="flex flex-col items-center justify-center">
                                            <div className="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 ring-8 ring-slate-50/50">
                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                </svg>
                                            </div>
                                            <h3 className="text-slate-900 font-bold text-lg">Belum Ada Data</h3>
                                            <p className="text-slate-500 text-sm max-w-xs mx-auto mt-1">
                                                Silakan tambah data jabatan baru untuk mulai mengelola bezetting pegawai.
                                            </p>
                                            <button onClick={openCreateModal} className="mt-6 px-4 py-2 bg-primary-50 text-primary-700 rounded-xl font-bold text-sm hover:bg-primary-100 transition-colors flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>
                                                Tambah Sekarang
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div className="text-sm text-gray-500">Menampilkan <span className="font-bold text-gray-700">{bezettings.from || 0}</span> - <span className="font-bold text-gray-700">{bezettings.to || 0}</span> dari <span className="font-bold text-gray-700">{bezettings.total}</span> data</div>
                    <div className="flex items-center gap-1">
                        {bezettings.links.map((link, key) => (
                            <Link key={key} href={link.url || '#'} className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${link.active ? 'bg-primary-600 text-white shadow-sm' : link.url ? 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' : 'text-gray-400 cursor-not-allowed'}`} dangerouslySetInnerHTML={{ __html: link.label }} preserveScroll preserveState />
                        ))}
                    </div>
                </div>
            </div>

            {/* Create/Edit Modal */}

            <Modal show={showModal} onClose={closeModal} maxWidth="md">
                <form onSubmit={handleSubmit} className="p-0 overflow-hidden">
                    <div className="p-6 bg-slate-50 border-b border-gray-100 flex items-center justify-between">
                        <h2 className="text-lg font-bold text-gray-900">{editData ? 'Edit Jabatan' : 'Tambah Jabatan Baru'}</h2>
                        <button type="button" onClick={closeModal} className="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div className="p-6 space-y-6">
                        <div>
                            <InputLabel htmlFor="nama_jabatan" value="Nama Jabatan" />
                            <TextInput
                                id="nama_jabatan"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.nama_jabatan}
                                onChange={(e) => setData('nama_jabatan', e.target.value)}
                                placeholder="Contoh: Polisi Kehutanan Ahli Pertama"
                                required
                            />
                            <InputError message={errors.nama_jabatan} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="kebutuhan" value="Kebutuhan (Kuota/ABK)" />
                            <TextInput
                                id="kebutuhan"
                                type="number"
                                className="mt-1 block w-full"
                                value={data.kebutuhan}
                                onChange={(e) => setData('kebutuhan', e.target.value)}
                                placeholder="0"
                                required
                            />
                            <InputError message={errors.kebutuhan} className="mt-2" />
                        </div>
                    </div>

                    <div className="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                        <SecondaryButton
                            onClick={closeModal}
                            className="rounded-xl px-6 py-3 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-all duration-200 shadow-sm font-bold normal-case"
                        >
                            Batal
                        </SecondaryButton>
                        <PrimaryButton
                            disabled={processing}
                            loading={processing}
                            className="bg-gradient-to-r from-primary-700 to-primary-600 hover:from-primary-600 hover:to-primary-500 text-white shadow-lg shadow-primary-900/20 transition-all transform active:scale-95 normal-case"
                        >
                            {editData ? 'Simpan Perubahan' : 'Tambah Jabatan'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );

}
