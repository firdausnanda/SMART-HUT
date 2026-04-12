import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import {
    Calendar,
    RefreshCcw,
    Table,
    BarChart3,
    Download,
    ChevronDown,
    Send,
    CheckCircle2,
    XCircle,
    Trash2,
    ArrowLeft
} from 'lucide-react';
import Select from 'react-select';
import StatusBadge from '@/Components/StatusBadge';
import LoadingOverlay from '@/Components/LoadingOverlay';
import BulkActionToolbar from '@/Components/BulkActionToolbar';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';

const MySwal = withReactContent(Swal);

export default function Index({ auth, rekaps, filters }) {
    const [isLoading, setIsLoading] = useState(false);
    const [loadingText, setLoadingText] = useState('Memproses...');
    const [selectedIds, setSelectedIds] = useState([]);

    const toggleSelectAll = () => {
        if (selectedIds.length === rekaps.length && rekaps.length > 0) {
            setSelectedIds([]);
        } else {
            setSelectedIds(rekaps.map(r => r.id));
        }
    };

    const toggleSelect = (id) => {
        setSelectedIds(prev =>
            prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
        );
    };

    const years = [];
    const currentYear = new Date().getFullYear();
    for (let i = currentYear; i >= 2021; i--) {
        years.push(i);
    }

    const months = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];

    const { data, setData, post, processing } = useForm({
        year: filters.year || currentYear,
        month: new Date().getMonth() + 1,
    });

    const isAdmin = auth.user.roles.includes('admin');
    const isKasi = auth.user.roles.includes('kasi');
    const isKaCdk = auth.user.roles.includes('kacdk');
    const userPermissions = auth.user.permissions || [];

    const canApprove = userPermissions.includes('kepegawaian.approve') || isAdmin;
    const canEdit = userPermissions.includes('kepegawaian.edit') || isAdmin;
    const canDelete = userPermissions.includes('kepegawaian.delete') || isAdmin;

    // Check if the selected month/year is already protected (has waiting or final status)
    const existingRekap = rekaps.find(r => r.periode_bulan === parseInt(data.month) && r.periode_tahun === parseInt(data.year));
    const isProtected = existingRekap && ['waiting_kasi', 'waiting_cdk', 'final'].includes(existingRekap.status);

    const handleSingleAction = (id, action) => {
        let title = '';
        let text = '';
        let icon = 'warning';
        let confirmText = '';
        let confirmColor = '#258a55';
        let showInput = false;
        let loadingMsg = '';

        switch (action) {
            case 'delete':
                title = 'Apakah Anda yakin?';
                text = "Seluruh data pegawai dalam rekap ini akan ikut terhapus!";
                icon = 'warning';
                confirmText = 'Ya, hapus!';
                confirmColor = '#d33';
                loadingMsg = 'Menghapus Data...';
                break;
            case 'submit':
                title = 'Ajukan Rekap?';
                text = "Data akan dikirim ke Kasi untuk diverifikasi.";
                icon = 'question';
                confirmText = 'Ya, Ajukan!';
                loadingMsg = 'Mengajukan Rekap...';
                break;
            case 'approve':
                title = 'Setujui Rekap?';
                text = "Apakah Anda yakin ingin menyetujui rekap ini?";
                icon = 'success';
                confirmText = 'Ya, Setujui';
                loadingMsg = 'Memverifikasi...';
                break;
            case 'reject':
                title = 'Tolak Rekap?';
                text = "Berikan alasan penolakan:";
                icon = 'warning';
                confirmText = 'Ya, Tolak';
                confirmColor = '#d33';
                showInput = true;
                loadingMsg = 'Memproses Penolakan...';
                break;
            default:
                return;
        }

        MySwal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal',
            cancelButtonColor: '#6B7280',
            input: showInput ? 'textarea' : undefined,
            inputPlaceholder: showInput ? 'Tuliskan catatan penolakan di sini...' : undefined,
            inputValidator: showInput ? (value) => {
                if (!value) {
                    return 'Alasan penolakan harus diisi!'
                }
            } : undefined,
            background: '#ffffff',
            borderRadius: '1.25rem',
            customClass: {
                title: 'font-bold text-gray-900',
                popup: 'rounded-3xl shadow-2xl border-none',
                confirmButton: 'rounded-xl font-bold px-6 py-2.5',
                cancelButton: 'rounded-xl font-bold px-6 py-2.5'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const actionData = {
                    action: action,
                    model_type: 'statistik'
                };
                if (showInput) {
                    actionData.rejection_note = result.value;
                }

                setLoadingText(loadingMsg || 'Memproses...');
                setIsLoading(true);

                if (action === 'delete') {
                    router.delete(route('rekap-bulanan.destroy', id), {
                        preserveScroll: true,
                        onSuccess: () => {
                            MySwal.fire({
                                title: 'Terhapus!',
                                text: 'Data rekap telah berhasil dihapus.',
                                icon: 'success',
                                confirmButtonColor: '#258a55',
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false,
                            });
                        },
                        onFinish: () => setIsLoading(false)
                    });
                } else {
                    router.post(route('rekap-bulanan.single-workflow-action', id), actionData, {
                        preserveScroll: true,
                        onSuccess: () => {
                            MySwal.fire({
                                title: 'Berhasil!',
                                text: 'Status rekap berhasil diperbarui.',
                                icon: 'success',
                                confirmButtonColor: '#258a55',
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false,
                            });
                        },
                        onFinish: () => setIsLoading(false)
                    });
                }
            }
        });
    };

    const handleBulkAction = (action) => {
        let title = '';
        let text = '';
        let icon = 'warning';
        let confirmText = '';
        let confirmColor = '#258a55';
        let showInput = false;

        switch (action) {
            case 'submit':
                title = 'Ajukan Rekap Terpilih?';
                text = `Anda akan mengajukan ${selectedIds.length} rekap periode ke pimpinan untuk diverifikasi.`;
                icon = 'question';
                confirmText = 'Ya, Ajukan!';
                break;
            case 'approve':
                title = 'Setujui Rekap?';
                text = `Apakah Anda yakin ingin menyetujui ${selectedIds.length} rekap ini secara massal?`;
                icon = 'success';
                confirmText = 'Ya, Setujui';
                break;
            case 'reject':
                title = 'Tolak Rekap Terpilih?';
                text = `Berikan alasan penolakan untuk ${selectedIds.length} rekap ini:`;
                icon = 'warning';
                confirmText = 'Ya, Tolak';
                confirmColor = '#d33';
                showInput = true;
                break;
            case 'delete':
                title = 'Hapus Rekap Terpilih?';
                text = `Tindakan ini akan menghapus ${selectedIds.length} data rekap dan snapshot pegawai di dalamnya secara permanen!`;
                icon = 'error';
                confirmText = 'Ya, Hapus Permanen!';
                confirmColor = '#d33';
                break;
            default:
                return;
        }

        MySwal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal',
            cancelButtonColor: '#6B7280',
            input: showInput ? 'textarea' : undefined,
            inputPlaceholder: showInput ? 'Tuliskan catatan penolakan di sini...' : undefined,
            inputValidator: showInput ? (value) => {
                if (!value) {
                    return 'Alasan penolakan harus diisi!'
                }
            } : undefined,
            background: '#ffffff',
            borderRadius: '1.25rem',
            customClass: {
                title: 'font-bold text-gray-900',
                popup: 'rounded-3xl shadow-2xl border-none',
                confirmButton: 'rounded-xl font-bold px-6 py-2.5',
                cancelButton: 'rounded-xl font-bold px-6 py-2.5'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const actionData = {
                    ids: selectedIds,
                    action: action,
                    model_type: 'statistik'
                };
                if (showInput) {
                    actionData.rejection_note = result.value;
                }

                const msg = action === 'delete' ? 'dihapus' : (action === 'submit' ? 'diajukan' : (action === 'approve' ? 'disetujui' : 'ditolak'));
                const lMsg = action === 'delete' ? 'Menghapus Data...' : (action === 'submit' ? 'Mengajukan Rekap...' : (action === 'approve' ? 'Memverifikasi...' : 'Memproses Penolakan...'));

                setLoadingText(lMsg);
                setIsLoading(true);

                router.post(route('rekap-bulanan.bulk-workflow-action'), actionData, {
                    preserveScroll: true,
                    onSuccess: () => {
                        const count = selectedIds.length;
                        setSelectedIds([]);
                        MySwal.fire({
                            title: 'Berhasil!',
                            text: `${count} data berhasil ${msg}.`,
                            icon: 'success',
                            confirmButtonColor: '#258a55',
                            timer: 2000,
                            timerProgressBar: true,
                            showConfirmButton: false,
                        });
                    },
                    onFinish: () => setIsLoading(false)
                });
            }
        });
    };

    const handleGenerate = (e) => {
        e.preventDefault();

        if (isProtected) {
            MySwal.fire({
                title: 'Data Dilindungi',
                text: 'Data rekap untuk bulan ini sedang dalam proses persetujuan atau sudah selesai. Tidak dapat generate ulang.',
                icon: 'error',
                confirmButtonColor: '#258a55',
                borderRadius: '1rem',
            });
            return;
        }

        setLoadingText('Sedang Menghasilkan Data Rekap...');
        setIsLoading(true);
        post(route('rekap-bulanan.generate'), {
            onSuccess: () => {
                setIsLoading(false);
            },
            onError: () => {
                setIsLoading(false);
            },
            onFinish: () => {
                setIsLoading(false);
            }
        });
    };

    const selectStyles = {
        control: (base, state) => ({
            ...base,
            borderRadius: '0.75rem',
            padding: '1px',
            backgroundColor: '#ffffff',
            borderColor: state.isFocused ? '#258a55' : '#e5e7eb',
            boxShadow: state.isFocused ? '0 0 0 1px #258a55' : 'none',
            '&:hover': {
                borderColor: '#258a55',
            },
            transition: 'all 0.2s',
            fontSize: '0.875rem',
            minHeight: '38px',
        }),
        option: (base, state) => ({
            ...base,
            backgroundColor: state.isSelected ? '#258a55' : state.isFocused ? '#f0fdf4' : 'white',
            color: state.isSelected ? 'white' : state.isFocused ? '#258a55' : '#374151',
            '&:active': {
                backgroundColor: '#258a55',
                color: 'white',
            },
            fontSize: '0.875rem',
            fontWeight: '500',
            cursor: 'pointer',
        }),
        singleValue: (base) => ({
            ...base,
            color: '#111827',
            fontWeight: '600',
        }),
        menuPortal: base => ({ ...base, zIndex: 9999 }),
    };

    const yearOptions = years.map(y => ({ value: y, label: y.toString() }));
    const monthOptions = months.map((m, i) => ({ value: i + 1, label: m }));

    const handleYearFilter = (opt) => {
        router.get(route('rekap-bulanan.index'), { year: opt.value }, { preserveState: true });
    };

    React.useEffect(() => {
        setSelectedIds([]);
    }, [rekaps]);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('demografi-pegawai.index')}
                            className="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-all"
                            title="Kembali ke Dashboard Kepegawaian"
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">Rekap Kepegawaian Bulanan</h2>
                    </div>
                </div>
            }
        >
            <Head title="Rekap Kepegawaian Bulanan" />

            <LoadingOverlay isLoading={isLoading} text={loadingText} />

            <div className={`py-12 transition-all duration-700 ease-in-out ${isLoading ? 'opacity-30 blur-md grayscale-[0.5] pointer-events-none' : 'opacity-100 blur-0'}`}>
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Filter & Action Card */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div className="flex items-center gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Filter Tahun</label>
                                    <div className="w-32">
                                        <Select
                                            options={yearOptions}
                                            value={yearOptions.find(o => o.value === filters.year)}
                                            onChange={handleYearFilter}
                                            styles={selectStyles}
                                            isSearchable={false}
                                            menuPortalTarget={document.body}
                                        />
                                    </div>
                                </div>
                            </div>

                            <form onSubmit={handleGenerate} className="flex items-end gap-4 border-l pl-6 border-gray-100">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Generate Rekap Baru</label>
                                    <div className="flex gap-2">
                                        <div className="w-40">
                                            <Select
                                                options={monthOptions}
                                                value={monthOptions.find(o => o.value === parseInt(data.month))}
                                                onChange={opt => setData('month', opt.value)}
                                                styles={selectStyles}
                                                isSearchable={false}
                                                placeholder="Pilih Bulan"
                                                menuPortalTarget={document.body}
                                            />
                                        </div>
                                        <div className="w-32">
                                            <Select
                                                options={yearOptions}
                                                value={yearOptions.find(o => o.value === parseInt(data.year))}
                                                onChange={opt => setData('year', opt.value)}
                                                styles={selectStyles}
                                                isSearchable={false}
                                                placeholder="Tahun"
                                                menuPortalTarget={document.body}
                                            />
                                        </div>
                                        <button
                                            type="submit"
                                            disabled={processing || isProtected}
                                            className={`inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white transition-colors ${isProtected
                                                    ? 'bg-gray-400 cursor-not-allowed'
                                                    : 'bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500'
                                                } disabled:opacity-50`}
                                            title={isProtected ? 'Status rekap saat ini tidak memungkinkan untuk generate ulang' : ''}
                                        >
                                            {processing ? (
                                                <>
                                                    <RefreshCcw className="animate-spin h-5 w-5 mr-2" />
                                                    Memproses...
                                                </>
                                            ) : isProtected ? (
                                                <>
                                                    <XCircle className="h-5 w-5 mr-2" />
                                                    Sudah Ada
                                                </>
                                            ) : (
                                                <>
                                                    <RefreshCcw className="h-5 w-5 mr-2" />
                                                    Generate
                                                </>
                                            )}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* Table Card */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50 text-slate-500 uppercase tracking-wider text-[11px] font-bold">
                                        <tr>
                                            <th className="px-4 py-3 text-center w-10">
                                                <input
                                                    type="checkbox"
                                                    className="rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                                                    checked={selectedIds.length === rekaps.length && rekaps.length > 0}
                                                    onChange={toggleSelectAll}
                                                />
                                            </th>
                                            <th className="px-6 py-3 text-left">Bulan</th>
                                            <th className="px-6 py-3 text-left">Total Pegawai</th>
                                            <th className="px-6 py-3 text-left">PNS/PPPK</th>
                                            <th className="px-6 py-3 text-center">Status</th>
                                            <th className="px-6 py-3 text-center">Aksi</th>
                                            <th className="px-6 py-3 text-center">Proses</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {rekaps.length === 0 ? (
                                            <tr>
                                                <td colSpan="7" className="px-6 py-10 text-center text-gray-500">
                                                    Belum ada data rekap untuk tahun {filters.year}. Silakan generate rekap.
                                                </td>
                                            </tr>
                                        ) : (
                                            rekaps.map((rekap) => (
                                                <tr key={rekap.id} className={`hover:bg-gray-50 transition-colors ${selectedIds.includes(rekap.id) ? 'bg-primary-50/30' : ''}`}>
                                                    <td className="px-4 py-4 text-center">
                                                        <input
                                                            type="checkbox"
                                                            className="rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer transition-all"
                                                            checked={selectedIds.includes(rekap.id)}
                                                            onChange={() => toggleSelect(rekap.id)}
                                                        />
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <Calendar className="h-5 w-5 text-gray-400 mr-2" />
                                                            <span className="font-medium text-gray-900">{months[rekap.periode_bulan - 1]} {rekap.periode_tahun}</span>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                        {rekap.total_pegawai_aktif} Orang
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                        <span className="text-blue-600 font-semibold">{rekap.total_pns}</span> / <span className="text-emerald-600 font-semibold">{rekap.total_pppk}</span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex flex-col items-center gap-1.5">
                                                            <StatusBadge status={rekap.status} />
                                                            {rekap.status === 'rejected' && rekap.rejection_note && (
                                                                <span className="text-[10px] text-rose-500 font-medium italic max-w-[120px] truncate leading-tight" title={rekap.rejection_note}>
                                                                    "{rekap.rejection_note}"
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                        <div className="flex justify-center gap-1.5">
                                                            <Link
                                                                href={route('rekap-bulanan.show', { year: rekap.periode_tahun, month: rekap.periode_bulan })}
                                                                className="inline-flex items-center px-2 py-1 bg-white border border-gray-200 rounded-lg font-bold text-[9px] text-gray-600 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition-all hover:border-indigo-300 hover:text-indigo-600 hover:-translate-y-0.5"
                                                            >
                                                                <BarChart3 className="h-3.5 w-3.5 mr-1 text-indigo-500" />
                                                                Analisa
                                                            </Link>
                                                            <Link
                                                                href={route('rekap-bulanan.show-pegawai', { year: rekap.periode_tahun, month: rekap.periode_bulan })}
                                                                className="inline-flex items-center px-2 py-1 bg-white border border-gray-200 rounded-lg font-bold text-[9px] text-gray-600 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition-all hover:border-emerald-300 hover:text-emerald-600 hover:-translate-y-0.5"
                                                            >
                                                                <Table className="h-3.5 w-3.5 mr-1 text-emerald-500" />
                                                                Data
                                                            </Link>
                                                            <a
                                                                href={route('rekap-bulanan.export', { year: rekap.periode_tahun, month: rekap.periode_bulan })}
                                                                target="_blank"
                                                                className="inline-flex items-center px-2 py-1 bg-white border border-gray-200 rounded-lg font-bold text-[9px] text-gray-600 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition-all hover:border-rose-300 hover:text-rose-600 hover:-translate-y-0.5"
                                                            >
                                                                <Download className="h-3.5 w-3.5 mr-1 text-rose-500" />
                                                                Excel
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                        <div className="flex justify-center gap-1.5">
                                                            {(rekap.status === 'draft' || rekap.status === 'rejected') && (
                                                                <button
                                                                    onClick={() => handleSingleAction(rekap.id, 'submit')}
                                                                    className="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all border border-transparent shadow-sm hover:border-blue-100 font-bold uppercase tracking-tighter"
                                                                    title="Ajukan ke Pimpinan"
                                                                >
                                                                    <Send className="h-4 w-4" />
                                                                </button>
                                                            )}

                                                            {canApprove && isKasi && rekap.status === 'waiting_kasi' && (
                                                                <>
                                                                    <button
                                                                        onClick={() => handleSingleAction(rekap.id, 'approve')}
                                                                        className="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all border border-transparent shadow-sm hover:border-emerald-100"
                                                                        title="Setujui Rekap"
                                                                    >
                                                                        <CheckCircle2 className="h-4 w-4" />
                                                                    </button>
                                                                    <button
                                                                        onClick={() => handleSingleAction(rekap.id, 'reject')}
                                                                        className="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all border border-transparent shadow-sm hover:border-rose-100"
                                                                        title="Tolak Rekap"
                                                                    >
                                                                        <XCircle className="h-4 w-4" />
                                                                    </button>
                                                                </>
                                                            )}

                                                            {canApprove && isKaCdk && rekap.status === 'waiting_cdk' && (
                                                                <>
                                                                    <button
                                                                        onClick={() => handleSingleAction(rekap.id, 'approve')}
                                                                        className="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all border border-transparent shadow-sm hover:border-emerald-100"
                                                                        title="Setujui Akhir"
                                                                    >
                                                                        <CheckCircle2 className="h-4 w-4" />
                                                                    </button>
                                                                    <button
                                                                        onClick={() => handleSingleAction(rekap.id, 'reject')}
                                                                        className="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all border border-transparent shadow-sm hover:border-rose-100"
                                                                        title="Tolak Rekap"
                                                                    >
                                                                        <XCircle className="h-4 w-4" />
                                                                    </button>
                                                                </>
                                                            )}

                                                            {((rekap.status === 'draft' && !isAdmin) || isAdmin) && (
                                                                <button
                                                                    onClick={() => handleSingleAction(rekap.id, 'delete')}
                                                                    className="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all border border-transparent shadow-sm hover:border-rose-100"
                                                                    title="Hapus Rekap"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Floating Bulk Action Bar */}
            <BulkActionToolbar
                selectedIds={selectedIds}
                setSelectedIds={setSelectedIds}
                handleBulkAction={handleBulkAction}
                canEdit={canEdit}
                canApprove={canApprove}
                canDelete={canDelete}
                isAdmin={isAdmin}
            />
        </AuthenticatedLayout>
    );
}
