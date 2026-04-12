import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { useState, useCallback, useEffect, useRef } from 'react';
import { debounce } from 'lodash';
import LoadingOverlay from '@/Components/LoadingOverlay';
import BulkActionToolbar from '@/Components/BulkActionToolbar';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import {
    Users, AlertTriangle, Bell, FileText,
    ArrowRight, Calendar, Activity,
    Clock, ChevronRight, ChevronDown, ChevronUp, Plus, Download, Upload,
    BarChart2, RefreshCcw, Send, ExternalLink, Eye
} from 'lucide-react';
import Select from 'react-select';



const MySwal = withReactContent(Swal);

const MONTHS = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const statusConfig = {
    draft: { label: 'Draft', bg: 'bg-slate-100', text: 'text-slate-700', border: 'border-slate-200', dot: 'bg-slate-400', pillBg: 'bg-slate-100 hover:bg-slate-200' },
    waiting_kasi: { label: 'Menunggu Kasi', bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500 animate-pulse', pillBg: 'bg-amber-100 hover:bg-amber-200' },
    waiting_cdk: { label: 'Menunggu CDK', bg: 'bg-indigo-50', text: 'text-indigo-700', border: 'border-indigo-200', dot: 'bg-indigo-500 animate-pulse', pillBg: 'bg-indigo-100 hover:bg-indigo-200' },
    final: { label: 'Selesai', bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500', pillBg: 'bg-emerald-100 hover:bg-emerald-200' },
    rejected: { label: 'Ditolak', bg: 'bg-rose-50', text: 'text-rose-700', border: 'border-rose-200', dot: 'bg-rose-500', pillBg: 'bg-rose-100 hover:bg-rose-200' },
};

function StatusChip({ status, size = 'sm' }) {
    const cfg = statusConfig[status] || statusConfig.draft;
    return (
        <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border font-bold uppercase tracking-wider ${size === 'xs' ? 'text-[9px]' : 'text-[10px]'} ${cfg.bg} ${cfg.text} ${cfg.border}`}>
            <span className={`w-1.5 h-1.5 rounded-full shrink-0 ${cfg.dot}`} />
            {cfg.label}
        </span>
    );
}



export default function DemografiIndex({
    auth, pegawais, filters,
    kpi, rekap_terakhir,
    tren_bulanan, alert_pensiun,
    alert_kgb, rekap_pending,
    rekap_total, rekap_timeline,
    available_years
}) {
    const { flash, errors } = usePage().props;
    const [showAllPending, setShowAllPending] = useState(false);

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
    const isStaff = auth.user.roles.includes('pk') || auth.user.roles.includes('peh') || auth.user.roles.includes('pelaksana');
    const userPermissions = auth.user.permissions || [];

    const canCreate = userPermissions.includes('kepegawaian.create') || isAdmin;
    const canEdit = userPermissions.includes('kepegawaian.edit') || isAdmin;
    const canDelete = userPermissions.includes('kepegawaian.delete') || isAdmin;
    const canApprove = userPermissions.includes('kepegawaian.approve') || isAdmin;
    const canExport = userPermissions.includes('kepegawaian.export') || isAdmin;
    const canImport = userPermissions.includes('kepegawaian.import') || isAdmin;
    const canShowBulk = isAdmin || (!isKasi && !isKaCdk);

    const performQuery = (query, field = sortField, dir = sortDir, limit = perPage) => {
        router.get(route('demografi-pegawai.index'), { search: query, sort: field, dir, per_page: limit }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const debouncedSearch = useCallback(debounce((query) => performQuery(query), 500), []);
    const onSearchChange = (e) => { const q = e.target.value; setSearchQuery(q); debouncedSearch(q); };

    const handleSort = (field) => {
        const isAsc = sortField === field && sortDir === 'asc';
        const newDir = isAsc ? 'desc' : 'asc';
        setSortField(field); setSortDir(newDir);
        performQuery(searchQuery, field, newDir, perPage);
    };

    const handlePerPageChange = (e) => { const v = e.target.value; setPerPage(v); performQuery(searchQuery, sortField, sortDir, v); };

    const SortIcon = ({ field }) => {
        if (sortField !== field) return (<svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 ml-1 text-gray-300 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>);
        return sortDir === 'asc'
            ? (<svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 ml-1 text-primary-600 font-bold inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>)
            : (<svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 ml-1 text-primary-600 font-bold inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>);
    };

    const handleSelectAll = (e) => { if (e.target.checked) setSelectedIds(pegawais.data.map(item => item.id)); else setSelectedIds([]); };
    const handleSelect = (id) => { if (selectedIds.includes(id)) setSelectedIds(selectedIds.filter(i => i !== id)); else setSelectedIds([...selectedIds, id]); };

    const handleBulkAction = (action) => {
        if (selectedIds.length === 0) return;
        MySwal.fire({
            title: 'Hapus Data Terpilih?', text: `${selectedIds.length} data terpilih akan dihapus.`,
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal', borderRadius: '1.25rem',
        }).then((result) => {
            if (result.isConfirmed) {
                setLoadingText('Menghapus Data Terpilih...'); setIsLoading(true);
                router.post(route('demografi-pegawai.bulk-delete'), { ids: selectedIds }, { preserveScroll: true, onSuccess: () => setSelectedIds([]), onFinish: () => setIsLoading(false) });
            }
        });
    };

    const handleSingleAction = (id, action) => {
        MySwal.fire({
            title: 'Hapus Data?', text: 'Data yang dihapus tidak bisa dikembalikan!', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal', borderRadius: '1.25rem',
            customClass: { title: 'font-bold text-gray-900', popup: 'rounded-3xl shadow-2xl border-none', confirmButton: 'rounded-xl font-bold px-6 py-2.5', cancelButton: 'rounded-xl font-bold px-6 py-2.5' }
        }).then((result) => {
            if (result.isConfirmed) {
                setLoadingText('Menghapus Data...'); setIsLoading(true);
                router.delete(route('demografi-pegawai.destroy', id), { preserveScroll: true, onFinish: () => setIsLoading(false) });
            }
        });
    };

    const yearOptions = (available_years || []).map(y => ({ value: y, label: `Tahun ${y}` }));
    yearOptions.unshift({ value: '', label: '12 Periode Terakhir' });

    const currentYearOption = yearOptions.find(o => o.value === filters.timeline_year) || yearOptions[0];

    const handleYearChange = (selected) => {
        setLoadingText('Memuat Timeline...');
        setIsLoading(true);
        router.get(route('demografi-pegawai.index'), {
            ...filters,
            timeline_year: selected.value
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsLoading(false)
        });
    };

    const selectStyles = {
        control: (base, state) => ({
            ...base,
            borderRadius: '0.75rem',
            borderColor: state.isFocused ? '#258a55' : '#e5e7eb',
            boxShadow: 'none',
            '&:hover': { borderColor: '#258a55' },
            fontSize: '0.75rem',
            fontWeight: '600',
            minHeight: '36px',
            backgroundColor: '#f9fafb',
        }),
        option: (base, state) => ({
            ...base,
            fontSize: '0.75rem',
            fontWeight: '600',
            backgroundColor: state.isSelected ? '#258a55' : state.isFocused ? '#f0fdf4' : 'transparent',
            color: state.isSelected ? 'white' : '#1f2937',
            cursor: 'pointer',
        }),
    };



    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    };

    const getDaysUntil = (dateStr) => {
        if (!dateStr) return null;
        const diff = Math.ceil((new Date(dateStr) - new Date()) / (1000 * 60 * 60 * 24));
        return diff;
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Kepegawaian</h2>}>
            <Head title="Dashboard Kepegawaian" />
            <LoadingOverlay isLoading={isLoading} text={loadingText} />
            {canShowBulk && (
                <BulkActionToolbar
                    selectedIds={selectedIds}
                    setSelectedIds={setSelectedIds}
                    handleBulkAction={handleBulkAction}
                    canEdit={canEdit}
                    canApprove={canApprove}
                    canDelete={canDelete}
                    isAdmin={isAdmin}
                />
            )}

            <div className={`space-y-6 transition-all duration-700 ease-in-out ${isLoading ? 'opacity-30 blur-md grayscale-[0.5] pointer-events-none' : 'opacity-100 blur-0'} sm:px-6 lg:px-8 max-w-7xl mx-auto pb-12`}>

                {/* ===== SECTION 1: HERO BANNER ===== */}
                <div className="bg-gradient-to-r from-primary-800 via-primary-700 to-primary-600 rounded-2xl p-8 text-white shadow-xl relative overflow-hidden">
                    <div className="absolute right-0 top-0 h-full w-2/5 bg-white/5 transform skew-x-12 shrink-0" />
                    <div className="absolute right-24 top-4 w-32 h-32 rounded-full bg-white/5 blur-2xl" />
                    <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <div className="flex items-center gap-3 mb-2">
                                <div className="p-2 bg-white/15 rounded-xl">
                                    <Activity className="h-6 w-6 text-white" />
                                </div>
                                <h3 className="text-2xl font-bold tracking-tight">Data Demografi Kepegawaian</h3>
                            </div>
                            <p className="text-primary-100 text-sm opacity-90 max-w-md">
                                Dashboard terpadu data demografi pegawai dan rekap bulanan secara real-time.
                            </p>
                            {rekap_terakhir && (
                                <div className="flex items-center gap-3 mt-4">
                                    <span className="text-xs text-primary-200 font-medium">Rekap Terakhir:</span>
                                    <span className="text-xs font-bold text-white">
                                        {MONTHS[rekap_terakhir.periode_bulan - 1]} {rekap_terakhir.periode_tahun}
                                    </span>
                                    <StatusChip status={rekap_terakhir.status} size="xs" />
                                </div>
                            )}
                        </div>
                        <div className="flex flex-wrap gap-2 shrink-0">
                            <Link
                                href={route('rekap-bulanan.index')}
                                className="flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold text-sm transition-all border border-white/20 active:scale-95"
                            >
                                <BarChart2 className="h-4 w-4" />
                                Rekap Bulanan
                            </Link>
                            {canExport && (
                                <button onClick={() => window.location.href = route('demografi-pegawai.export')}
                                    className="flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold text-sm transition-all border border-white/20 active:scale-95">
                                    <Download className="h-4 w-4" /> Export
                                </button>
                            )}
                            {canImport && (
                                <button onClick={() => setShowImportModal(true)}
                                    className="flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold text-sm transition-all border border-white/20 active:scale-95">
                                    <Upload className="h-4 w-4" /> Import
                                </button>
                            )}

                        </div>
                    </div>
                </div>



                {/* ===== SECTION 2: ALERT ROW ===== */}
                {((alert_pensiun?.length > 0 && (isAdmin || isStaff)) || (alert_kgb?.length > 0 && (isAdmin || isStaff)) || (rekap_pending?.length > 0)) && (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                        {/* Alert Pensiun */}
                        {alert_pensiun?.length > 0 && (isAdmin || isStaff) && (
                            <div className="bg-white rounded-2xl border border-rose-100 shadow-sm overflow-hidden">
                                <div className="px-5 py-4 bg-rose-50 border-b border-rose-100 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <AlertTriangle className="h-4 w-4 text-rose-500" />
                                        <h4 className="font-bold text-rose-800 text-sm">Pensiun dalam 90 Hari</h4>
                                    </div>
                                    <span className="text-[10px] font-black bg-rose-500 text-white px-2 py-0.5 rounded-full">{kpi?.pensiun_90_hari || alert_pensiun.length}</span>
                                </div>
                                <div className="divide-y divide-gray-50">
                                    {alert_pensiun.map((p) => {
                                        const days = getDaysUntil(p.proyeksi_pensiun);
                                        return (
                                            <div key={p.id} className="px-5 py-3 hover:bg-rose-50/50 transition-colors">
                                                <p className="text-sm font-bold text-gray-800 truncate">{p.nama_lengkap}</p>
                                                <div className="flex items-center justify-between mt-0.5">
                                                    <p className="text-[10px] text-gray-400">NIP. {p.nip}</p>
                                                    <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ${days <= 30 ? 'bg-rose-100 text-rose-700' : 'bg-amber-50 text-amber-700'}`}>
                                                        {days !== null ? `${days} hari` : '-'}
                                                    </span>
                                                </div>
                                                <p className="text-[10px] text-gray-500 mt-0.5">{formatDate(p.proyeksi_pensiun)}</p>
                                            </div>
                                        );
                                    })}
                                </div>
                                <div className="px-5 py-3 bg-gray-50 border-t border-gray-100">
                                    <Link href={route('demografi-pegawai.index')} className="text-xs text-rose-600 font-bold flex items-center gap-1 hover:gap-2 transition-all">
                                        Lihat semua <ArrowRight className="h-3 w-3" />
                                    </Link>
                                </div>
                            </div>
                        )}

                        {/* Alert KGB */}
                        {alert_kgb?.length > 0 && (isAdmin || isStaff) && (
                            <div className="bg-white rounded-2xl border border-amber-100 shadow-sm overflow-hidden">
                                <div className="px-5 py-4 bg-amber-50 border-b border-amber-100 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Bell className="h-4 w-4 text-amber-500" />
                                        <h4 className="font-bold text-amber-800 text-sm">KGB Bulan Ini</h4>
                                    </div>
                                    <span className="text-[10px] font-black bg-amber-500 text-white px-2 py-0.5 rounded-full">{kpi?.kgb_bulan_ini || alert_kgb.length}</span>
                                </div>
                                <div className="divide-y divide-gray-50">
                                    {alert_kgb.map((p) => (
                                        <div key={p.id} className="px-5 py-3 hover:bg-amber-50/30 transition-colors">
                                            <p className="text-sm font-bold text-gray-800 truncate">{p.nama_lengkap}</p>
                                            <p className="text-[10px] text-gray-400 mt-0.5">NIP. {p.nip}</p>
                                            {p.tmt_kgb_berikutnya && (
                                                <p className="text-[10px] text-amber-600 font-semibold mt-0.5">{formatDate(p.tmt_kgb_berikutnya)}</p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                                <div className="px-5 py-3 bg-gray-50 border-t border-gray-100">
                                    <Link href={route('proyeksi-gaji.index')} className="text-xs text-amber-600 font-bold flex items-center gap-1 hover:gap-2 transition-all">
                                        Lihat proyeksi gaji <ArrowRight className="h-3 w-3" />
                                    </Link>
                                </div>
                            </div>
                        )}

                        {/* Alert Rekap Pending */}
                        {rekap_pending?.length > 0 && (
                            <div className="bg-white rounded-2xl border border-indigo-100 shadow-sm overflow-hidden">
                                <div className="px-5 py-4 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <FileText className="h-4 w-4 text-indigo-500" />
                                        <h4 className="font-bold text-indigo-800 text-sm">Rekap Perlu Tindakan</h4>
                                    </div>
                                    <span className="text-[10px] font-black bg-indigo-500 text-white px-2 py-0.5 rounded-full">{rekap_total.length}</span>
                                </div>
                                <div className="divide-y divide-gray-50 max-h-[400px] overflow-y-auto custom-scrollbar">
                                    {(showAllPending ? rekap_total : rekap_pending).map((r) => (
                                        <div key={r.id} className="px-5 py-3 hover:bg-indigo-50/30 transition-colors">
                                            <div className="flex items-center justify-between">
                                                <p className="text-sm font-bold text-gray-800">{MONTHS[r.periode_bulan - 1]} {r.periode_tahun}</p>
                                                <StatusChip status={r.status} size="xs" />
                                            </div>
                                            {r.rejection_note && (
                                                <p className="text-[10px] text-rose-500 italic mt-1 truncate">"{r.rejection_note}"</p>
                                            )}
                                            <Link
                                                href={route('rekap-bulanan.show', { year: r.periode_tahun, month: r.periode_bulan })}
                                                className={`text-[10px] ${statusConfig[r.status]?.text || 'text-indigo-600'} font-bold mt-1.5 flex items-center gap-1 hover:gap-2 transition-all`}
                                            >
                                                <Eye className="h-3 w-3" /> Buka Detail
                                            </Link>
                                        </div>
                                    ))}
                                </div>

                                {rekap_total.length > 3 && (
                                    <div className="px-5 py-2 border-t border-indigo-50 bg-indigo-50/10">
                                        <button
                                            onClick={() => setShowAllPending(!showAllPending)}
                                            className="w-full flex items-center justify-center gap-1 text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition-colors"
                                        >
                                            {showAllPending ? (
                                                <><ChevronUp className="h-3 w-3" /> Sembunyikan</>
                                            ) : (
                                                <><ChevronDown className="h-3 w-3" /> Lihat Semua ({rekap_total.length})</>
                                            )}
                                        </button>
                                    </div>
                                )}
                                <div className="px-5 py-3 bg-gray-50 border-t border-gray-100">
                                    <Link href={route('rekap-bulanan.index')} className="text-xs text-indigo-600 font-bold flex items-center gap-1 hover:gap-2 transition-all">
                                        Kelola rekap <ArrowRight className="h-3 w-3" />
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* ===== SECTION 3: TIMELINE REKAP ===== */}
                <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div className="flex items-center justify-between mb-5">
                        <div>
                            <h3 className="font-bold text-gray-800 flex items-center gap-2">
                                <Calendar className="h-4 w-4 text-primary-600" />
                                Timeline Rekap Bulanan
                            </h3>
                            <p className="text-xs text-gray-400 mt-0.5">
                                {filters.timeline_year ? `Menampilkan rekap khusus tahun ${filters.timeline_year}` : '12 periode terakhir • klik untuk membuka analisa'}
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            <div className="w-48 text-left">
                                <Select
                                    value={currentYearOption}
                                    options={yearOptions}
                                    onChange={handleYearChange}
                                    styles={selectStyles}
                                    isSearchable={false}
                                    placeholder="Filter Tahun"
                                />
                            </div>
                            <Link href={route('rekap-bulanan.index')} className="text-xs text-primary-600 font-bold hover:underline flex items-center gap-1">
                                Kelola Rekap <ArrowRight className="h-3 w-3" />
                            </Link>
                        </div>
                    </div>
                    <div className="overflow-x-auto pb-2">
                        <div className="flex gap-2 min-w-max">
                            {rekap_timeline?.length > 0 ? rekap_timeline.map((r) => {
                                const cfg = statusConfig[r.status] || statusConfig.draft;
                                return (
                                    <Link
                                        key={r.id}
                                        href={route('rekap-bulanan.show', { year: r.periode_tahun, month: r.periode_bulan })}
                                        className={`flex flex-col items-center gap-2 px-4 py-3 rounded-xl transition-all group cursor-pointer border ${cfg.pillBg} ${cfg.border}`}
                                        title={`${MONTHS[r.periode_bulan - 1]} ${r.periode_tahun} — ${cfg.label}`}
                                    >
                                        <div className={`w-2 h-2 rounded-full ${cfg.dot}`} />
                                        <span className={`text-[10px] font-black uppercase whitespace-nowrap ${cfg.text}`}>
                                            {MONTHS[r.periode_bulan - 1].slice(0, 3)}
                                        </span>
                                        <span className={`text-[9px] font-bold ${cfg.text} opacity-70`}>
                                            {r.periode_tahun}
                                        </span>
                                    </Link>
                                );
                            }) : (
                                <div className="text-center py-4 text-gray-400 text-sm w-full">
                                    <Calendar className="h-8 w-8 mx-auto mb-2 text-gray-200" />
                                    Belum ada rekap bulanan yang digenerate
                                </div>
                            )}
                        </div>
                    </div>
                    {/* Legend */}
                    <div className="flex flex-wrap gap-4 mt-4 pt-4 border-t border-gray-50">
                        {Object.entries(statusConfig).map(([key, cfg]) => (
                            <div key={key} className="flex items-center gap-1.5 text-[10px] text-gray-500 font-semibold">
                                <div className={`w-2 h-2 rounded-full ${cfg.dot.replace(' animate-pulse', '')}`} />
                                {cfg.label}
                            </div>
                        ))}
                    </div>
                </div>

                {/* ===== SECTION 4: TABEL DAFTAR PEGAWAI ===== */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100 flex flex-col md:flex-row md:justify-between md:items-center gap-4 bg-slate-50/30">
                        <div className="flex flex-wrap items-center gap-4 flex-1">
                            <div>
                                <h3 className="font-bold text-gray-800">Daftar Pegawai</h3>
                                <p className="text-xs text-gray-400 mt-0.5">Total {pegawais.total} pegawai terdaftar</p>
                            </div>
                            <div className="h-8 w-px bg-gray-200 hidden md:block" />
                            <div className="relative w-full max-w-xs md:w-64">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg className="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <input type="text" className="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all"
                                    placeholder="Cari NIP, nama..." value={searchQuery} onChange={onSearchChange} />
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            {canCreate && (
                                <Link href={route('demografi-pegawai.create')}
                                    className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-primary-700 transition-all active:scale-95">
                                    <Plus className="h-4 w-4" /> Tambah Pegawai
                                </Link>
                            )}
                            <div className="h-8 w-px bg-gray-200 hidden md:block" />
                            <div className="flex items-center gap-2">
                                <span className="text-xs font-bold text-gray-400 uppercase">Baris:</span>
                                <select className="text-sm font-bold border-gray-200 rounded-lg focus:ring-primary-500 focus:border-primary-500 py-1" value={perPage} onChange={handlePerPageChange}>
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-100 bg-gray-50/50">
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" onClick={() => handleSort('nama_lengkap')}>
                                        <div className="flex items-center gap-4">
                                            {canShowBulk && (
                                                <input type="checkbox" className="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500" onClick={(e) => e.stopPropagation()} onChange={handleSelectAll} checked={pegawais.data.length > 0 && selectedIds.length === pegawais.data.length} />
                                            )}
                                            <span>Identitas Pegawai <SortIcon field="nama_lengkap" /></span>
                                        </div>
                                    </th>
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" onClick={() => handleSort('pangkat_golongan')}>
                                        Jabatan & Kedudukan <SortIcon field="pangkat_golongan" />
                                    </th>
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" onClick={() => handleSort('created_at')}>
                                        Input Oleh <SortIcon field="created_at" />
                                    </th>
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {pegawais.data.map((pegawai) => (
                                    <tr key={pegawai.id} className={`group hover:bg-gray-50/80 transition-all duration-200 ${selectedIds.includes(pegawai.id) ? 'bg-primary-50/30' : ''}`}>
                                        <td className="px-6 py-5">
                                            <div className="flex items-center gap-4">
                                                {canShowBulk && (
                                                    <input type="checkbox" className="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 transform -translate-y-px" onChange={() => handleSelect(pegawai.id)} checked={selectedIds.includes(pegawai.id)} />
                                                )}
                                                <div className="flex items-center gap-3">
                                                    <div className="h-10 w-10 md:h-11 md:w-11 rounded-xl bg-gradient-to-br from-primary-600 to-primary-700 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-primary-900/10 ring-2 ring-white group-hover:scale-105 transition-transform duration-300">
                                                        {pegawai.nama_lengkap.charAt(0).toUpperCase()}
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
                                                    {pegawai.pangkat_golongan && (<span className="text-xs text-gray-500 font-medium">{pegawai.pangkat_golongan}</span>)}
                                                    {pegawai.pangkat_golongan && <span className="w-1 h-1 rounded-full bg-gray-300" />}
                                                    <span className={`inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border ${pegawai.status_kedudukan === 'Aktif' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : pegawai.status_kedudukan === 'Pensiun' ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-rose-50 text-rose-600 border-rose-100'}`}>
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
                                        <td className="px-6 py-5">
                                            <div className="flex items-center justify-center gap-2">
                                                <Link href={route('demografi-pegawai.edit', pegawai.id)} className="p-2 text-primary-600 hover:bg-primary-100 rounded-lg transition-colors shadow-sm bg-primary-50" title="Edit Pegawai">
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                </Link>
                                                {(canDelete || isAdmin) && (
                                                    <button onClick={() => handleSingleAction(pegawai.id, 'delete')} className="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors shadow-sm bg-red-50" title="Hapus Pegawai">
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {pegawais.data.length === 0 && (
                                    <tr>
                                        <td colSpan="4" className="text-center py-20">
                                            <div className="flex flex-col items-center justify-center">
                                                <div className="h-24 w-24 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
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
                                    <Link key={key} href={link.url || '#'}
                                        className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${link.active ? 'bg-primary-600 text-white shadow-sm shadow-primary-500/30' : link.url ? 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' : 'text-gray-400 cursor-not-allowed'}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        preserveScroll preserveState
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Import Modal */}
            <Modal show={showImportModal} onClose={() => setShowImportModal(false)}>
                <form onSubmit={(e) => {
                    e.preventDefault();
                    if (!importFile) return;
                    const formData = new FormData();
                    formData.append('file', importFile);
                    setLoadingText('Mengimport Data...');
                    setIsLoading(true);
                    setShowImportModal(false);
                    router.post(route('demografi-pegawai.import'), formData, {
                        forceFormData: true, preserveScroll: true,
                        onFinish: () => { setIsLoading(false); setImportFile(null); },
                        onError: () => setIsLoading(false)
                    });
                }} className="p-0 overflow-hidden">
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
                        <div className="border-t border-gray-100" />
                        <div className="flex gap-4 items-start">
                            <div className="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold text-sm">2</div>
                            <div className="flex-1">
                                <h3 className="text-sm font-bold text-gray-900 mb-1">Upload Data</h3>
                                <p className="text-xs text-gray-500 mb-3 leading-relaxed">Pilih file yang telah diisi sesuai template (.xlsx, .xls, .csv).</p>
                                <div className={`relative border-2 border-dashed rounded-xl p-6 transition-all duration-200 text-center cursor-pointer ${importFile ? 'border-primary-500 bg-primary-50/50' : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50'}`}>
                                    <input type="file" accept=".xlsx,.xls,.csv" onChange={(e) => setImportFile(e.target.files[0])} className="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                    <div className="space-y-2 pointer-events-none">
                                        <div className={`mx-auto h-12 w-12 rounded-full flex items-center justify-center transition-colors ${importFile ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-400'}`}>
                                            {importFile ? (<svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>) : (<svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>)}
                                        </div>
                                        {importFile ? (<div><p className="text-sm font-bold text-primary-800">{importFile.name}</p><p className="text-xs text-primary-600 mt-1">{(importFile.size / 1024).toFixed(1)} KB</p></div>) : (<p className="text-sm font-medium text-gray-500">Klik untuk pilih file atau drag &amp; drop</p>)}
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
