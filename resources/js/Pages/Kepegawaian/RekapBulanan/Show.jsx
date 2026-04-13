import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    PointElement,
    LineElement
} from 'chart.js';
import { Doughnut, Bar } from 'react-chartjs-2';
import {
    ArrowLeft,
    Users,
    IdCard,
    AlertTriangle,
    GraduationCap,
    BarChart3,
    Send,
    CheckCircle2,
    XCircle,
    Info,
    TrendingUp,
    TrendingDown,
    Minus,
    ExternalLink,
    Table2,
    GitCompare,
} from 'lucide-react';
import StatusBadge from '@/Components/StatusBadge';
import LoadingOverlay from '@/Components/LoadingOverlay';
import { router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';

const MySwal = withReactContent(Swal);

ChartJS.register(
    ArcElement,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    PointElement,
    LineElement
);

const MONTHS = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export default function Show({ auth, rekap, rekap_sebelumnya, pendidikanLabels, golonganLabels }) {
    const [activeTab, setActiveTab] = useState('statistik');
    const [isLoading, setIsLoading] = useState(false);
    const [loadingText, setLoadingText] = useState('Memproses...');

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
    };

    const eduChartOptions = {
        ...chartOptions,
        indexAxis: 'y',
        scales: {
            y: {
                ticks: {
                    autoSkip: false,
                    font: {
                        size: 9,
                        weight: 'bold'
                    }
                }
            },
            x: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    };

    const golChartOptions = {
        ...eduChartOptions,
        scales: {
            ...eduChartOptions.scales,
            y: {
                ...eduChartOptions.scales.y,
                ticks: {
                    ...eduChartOptions.scales.y.ticks,
                    font: {
                        ...eduChartOptions.scales.y.ticks.font,
                        size: 8
                    }
                }
            }
        }
    };

    const isAdmin = auth.user.roles.includes('admin');
    const isKasi = auth.user.roles.includes('kasi');
    const isKaCdk = auth.user.roles.includes('kacdk');
    const userPermissions = auth.user.permissions || [];
    const canCreate = userPermissions.includes('kepegawaian.create') || isAdmin;
    const canEdit = userPermissions.includes('kepegawaian.edit') || isAdmin;
    const canApprove = userPermissions.includes('kepegawaian.approve') || isAdmin;

    const handleSingleAction = (id, action) => {
        let title = '', text = '', icon = 'warning', confirmText = '', confirmColor = '#258a55', showInput = false;

        switch (action) {
            case 'submit': title = 'Ajukan Rekap?'; text = 'Data akan dikirim ke Kasi untuk diverifikasi.'; icon = 'question'; confirmText = 'Ya, Ajukan!'; break;
            case 'approve': title = 'Setujui Rekap?'; text = 'Apakah Anda yakin ingin menyetujui rekap ini?'; icon = 'question'; confirmText = 'Ya, Setujui'; break;
            case 'reject': title = 'Tolak Rekap?'; text = 'Berikan alasan penolakan:'; icon = 'warning'; confirmText = 'Ya, Tolak'; confirmColor = '#d33'; showInput = true; break;
            default: return;
        }

        MySwal.fire({
            title: `<span class="text-2xl font-black tracking-tight text-gray-900">${title}</span>`,
            html: `<div class="text-sm font-medium text-gray-500 mt-2 leading-relaxed px-4">${text}</div>`,
            icon,
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal',
            cancelButtonColor: '#6B7280',
            input: showInput ? 'textarea' : undefined,
            inputPlaceholder: showInput ? 'Tuliskan catatan penolakan di sini...' : undefined,
            inputValidator: showInput ? (value) => { if (!value) return 'Alasan penolakan harus diisi!' } : undefined,
            background: '#ffffff',
            borderRadius: '1.5rem',
            padding: '2rem',
            showClass: { popup: 'animate__animated animate__fadeInUp animate__faster' },
            hideClass: { popup: 'animate__animated animate__fadeOutDown animate__faster' },
            customClass: {
                popup: 'rounded-[2.5rem] shadow-[0_20px_60px_-15px_rgba(0,0,0,0.1)] border-none',
                title: 'font-black pt-4',
                confirmButton: 'rounded-2xl font-bold px-8 py-3 text-sm transition-all duration-200 hover:scale-105 active:scale-95 shadow-lg shadow-primary-500/20',
                cancelButton: 'rounded-2xl font-bold px-8 py-3 text-sm transition-all duration-200 hover:scale-105 active:scale-95'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const actionData = { action, model_type: 'statistik' };
                if (showInput) actionData.rejection_note = result.value;

                const lMsg = action === 'submit' ? 'Mengajukan Rekap...' : (action === 'approve' ? 'Memverifikasi...' : 'Memproses Penolakan...');
                setLoadingText(lMsg);
                setIsLoading(true);

                router.post(route('rekap-bulanan.single-workflow-action', id), actionData, {
                    preserveScroll: true,
                    onSuccess: () => MySwal.fire({
                        title: '<span class="text-2xl font-black text-emerald-600">Berhasil!</span>',
                        html: '<div class="text-sm font-bold text-gray-500">Status rekap telah berhasil diperbarui.</div>',
                        icon: 'success',
                        confirmButtonColor: '#258a55',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        borderRadius: '1.5rem',
                        customClass: {
                            popup: 'rounded-[2rem] shadow-2xl border-none',
                            title: 'font-black pt-2'
                        }
                    }),
                    onFinish: () => setIsLoading(false)
                });
            }
        });
    };

    // ===== CHART DATA (Tab 1) =====
    const statusData = {
        labels: Object.keys(rekap.statistik_status_pegawai || {}),
        datasets: [{ data: Object.values(rekap.statistik_status_pegawai || {}), backgroundColor: ['rgba(59,130,246,0.8)', 'rgba(16,185,129,0.8)', 'rgba(245,158,11,0.8)', 'rgba(107,114,128,0.8)'], borderColor: '#fff', borderWidth: 2 }],
    };
    const eduLabels = (pendidikanLabels || Object.keys(rekap.statistik_pendidikan || {}))
        .filter(label => (rekap.statistik_pendidikan?.[label] || 0) > 0);
    const eduData = {
        labels: eduLabels,
        datasets: [{
            label: 'Jumlah Pegawai',
            data: eduLabels.map(label => rekap.statistik_pendidikan[label] || 0),
            backgroundColor: 'rgba(99,102,241,0.8)'
        }],
    };
    const golLabels = (golonganLabels || Object.keys(rekap.statistik_golongan || {}))
        .filter(label => (rekap.statistik_golongan?.[label] || 0) > 0);
    const golData = {
        labels: golLabels,
        datasets: [{
            label: 'Jumlah Pegawai',
            data: golLabels.map(label => rekap.statistik_golongan[label] || 0),
            backgroundColor: 'rgba(139,92,246,0.8)'
        }],
    };
    const genData = {
        labels: Object.keys(rekap.statistik_generasi || {}),
        datasets: [{ data: Object.values(rekap.statistik_generasi || {}), backgroundColor: ['rgba(244,63,94,0.8)', 'rgba(217,70,239,0.8)', 'rgba(168,85,247,0.8)', 'rgba(99,102,241,0.8)'] }],
    };
    const tenureData = {
        labels: Object.keys(rekap.statistik_masa_kerja || {}),
        datasets: [{ label: 'Jumlah Pegawai', data: Object.values(rekap.statistik_masa_kerja || {}), backgroundColor: 'rgba(20,184,166,0.8)' }],
    };

    // ===== DELTA COMPARISON (Tab 3) =====
    const comparisonMetrics = [
        {
            label: 'Total Pegawai Aktif',
            icon: Users,
            curr: rekap.total_pegawai_aktif,
            prev: rekap_sebelumnya?.total_pegawai_aktif,
            color: 'blue',
        },
        {
            label: 'Pegawai PNS',
            icon: IdCard,
            curr: rekap.total_pns,
            prev: rekap_sebelumnya?.total_pns,
            color: 'indigo',
        },
        {
            label: 'Pegawai PPPK',
            icon: IdCard,
            curr: rekap.total_pppk,
            prev: rekap_sebelumnya?.total_pppk,
            color: 'emerald',
        },
        {
            label: 'Pensiun Tahun Ini',
            icon: AlertTriangle,
            curr: rekap.total_pensiun_tahun_ini,
            prev: rekap_sebelumnya?.total_pensiun_tahun_ini,
            color: 'amber',
            invertDelta: true, // lebih tinggi = lebih buruk
        },
        {
            label: 'KGB Bulan Ini',
            icon: GraduationCap,
            curr: rekap.kgb_jatuh_bulan_ini,
            prev: rekap_sebelumnya?.kgb_jatuh_bulan_ini,
            color: 'violet',
        },
    ];

    const DeltaChip = ({ curr, prev, invertDelta = false }) => {
        if (prev === null || prev === undefined) return <span className="text-xs text-gray-400 font-semibold">—</span>;
        const delta = (curr || 0) - (prev || 0);
        if (delta === 0) return (
            <span className="inline-flex items-center gap-1 text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                <Minus className="h-3 w-3" /> Sama
            </span>
        );
        const isPositive = invertDelta ? delta < 0 : delta > 0;
        return (
            <span className={`inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full ${isPositive ? 'text-emerald-700 bg-emerald-50' : 'text-rose-700 bg-rose-50'}`}>
                {delta > 0 ? <TrendingUp className="h-3 w-3" /> : <TrendingDown className="h-3 w-3" />}
                {delta > 0 ? '+' : ''}{delta}
            </span>
        );
    };

    const tabs = [
        { key: 'statistik', label: 'Statistik & Chart', icon: BarChart3 },
        { key: 'pegawai', label: 'Daftar Snapshot', icon: Table2 },
        { key: 'perbandingan', label: 'Perbandingan', icon: GitCompare },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('demografi-pegawai.index')}
                            className="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-all"
                            title="Kembali ke Dashboard Demografi"
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Analisa Kepegawaian
                            </h2>
                            <p className="text-sm text-gray-500 font-medium">
                                {MONTHS[rekap.periode_bulan - 1]} {rekap.periode_tahun}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <StatusBadge status={rekap.status} />
                        <div className="flex gap-2 border-l pl-4 ml-1 border-gray-200">
                            {(canEdit || canCreate) && (rekap.status === 'draft' || rekap.status === 'rejected') && (
                                <button onClick={() => handleSingleAction(rekap.id, 'submit')}
                                    className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-blue-700 transition-all gap-2 active:scale-95">
                                    <Send className="h-4 w-4" /> Ajukan Verifikasi
                                </button>
                            )}
                            {canApprove && (isKasi || isAdmin) && rekap.status === 'waiting_kasi' && (
                                <>
                                    <button onClick={() => handleSingleAction(rekap.id, 'approve')} className="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-emerald-700 transition-all gap-2 active:scale-95">
                                        <CheckCircle2 className="h-4 w-4" /> Setujui
                                    </button>
                                    <button onClick={() => handleSingleAction(rekap.id, 'reject')} className="inline-flex items-center px-4 py-2 bg-rose-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-rose-700 transition-all gap-2 active:scale-95">
                                        <XCircle className="h-4 w-4" /> Tolak
                                    </button>
                                </>
                            )}
                            {canApprove && (isKaCdk || isAdmin) && rekap.status === 'waiting_cdk' && (
                                <>
                                    <button onClick={() => handleSingleAction(rekap.id, 'approve')} className="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-emerald-700 transition-all gap-2 active:scale-95">
                                        <CheckCircle2 className="h-4 w-4" /> Setujui Akhir
                                    </button>
                                    <button onClick={() => handleSingleAction(rekap.id, 'reject')} className="inline-flex items-center px-4 py-2 bg-rose-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-rose-700 transition-all gap-2 active:scale-95">
                                        <XCircle className="h-4 w-4" /> Tolak
                                    </button>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            }
        >
            <Head title={`Analisa Rekap ${MONTHS[rekap.periode_bulan - 1]} ${rekap.periode_tahun}`} />

            <LoadingOverlay isLoading={isLoading} text={loadingText} />

            <div className={`py-8 transition-all duration-700 ease-in-out ${isLoading ? 'opacity-30 blur-md grayscale-[0.5] pointer-events-none' : 'opacity-100 blur-0'}`}>
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Rejection note banner */}
                    {rekap.status === 'rejected' && rekap.rejection_note && (
                        <div className="bg-rose-50 border border-rose-200 p-4 rounded-xl flex items-start gap-3">
                            <Info className="h-5 w-5 text-rose-500 mt-0.5 shrink-0" />
                            <div>
                                <h4 className="font-bold text-rose-800 text-sm">Alasan Penolakan:</h4>
                                <p className="text-rose-700 text-sm mt-1">"{rekap.rejection_note}"</p>
                            </div>
                        </div>
                    )}

                    {/* Summary Cards */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                            <div className="flex items-center justify-between">
                                <div><p className="text-sm font-medium text-gray-500">Total Pegawai Aktif</p><p className="text-2xl font-bold text-gray-900 mt-1">{rekap.total_pegawai_aktif}</p></div>
                                <Users className="h-10 w-10 text-blue-100" />
                            </div>
                        </div>
                        <div className="bg-white p-6 rounded-xl shadow-sm border-l-4 border-emerald-500">
                            <div className="flex items-center justify-between">
                                <div><p className="text-sm font-medium text-gray-500">Jenis Kelamin (L/P)</p><p className="text-2xl font-bold text-gray-900 mt-1">{rekap.total_laki} / {rekap.total_perempuan}</p></div>
                                <Users className="h-10 w-10 text-emerald-100" />
                            </div>
                        </div>
                        <div className="bg-white p-6 rounded-xl shadow-sm border-l-4 border-orange-500">
                            <div className="flex items-center justify-between">
                                <div><p className="text-sm font-medium text-gray-500">Pensiun (Tahun Ini)</p><p className="text-2xl font-bold text-gray-900 mt-1">{rekap.total_pensiun_tahun_ini}</p></div>
                                <AlertTriangle className="h-10 w-10 text-orange-100" />
                            </div>
                        </div>
                        <div className="bg-white p-6 rounded-xl shadow-sm border-l-4 border-indigo-500">
                            <div className="flex items-center justify-between">
                                <div><p className="text-sm font-medium text-gray-500">KGB Bulan Ini</p><p className="text-2xl font-bold text-gray-900 mt-1">{rekap.kgb_jatuh_bulan_ini}</p></div>
                                <GraduationCap className="h-10 w-10 text-indigo-100" />
                            </div>
                        </div>
                    </div>

                    {/* Tab Panel */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        {/* Tab Bar */}
                        <div className="flex border-b border-gray-100 bg-gray-50/50 overflow-x-auto">
                            {tabs.map((tab) => {
                                const Icon = tab.icon;
                                return (
                                    <button
                                        key={tab.key}
                                        onClick={() => setActiveTab(tab.key)}
                                        className={`flex items-center gap-2 px-6 py-4 text-sm font-bold whitespace-nowrap border-b-2 transition-all shrink-0 ${activeTab === tab.key
                                            ? 'border-primary-600 text-primary-700 bg-white'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-white/60'
                                            }`}
                                    >
                                        <Icon className="h-4 w-4" />
                                        {tab.label}
                                    </button>
                                );
                            })}
                        </div>

                        {/* Tab 1: Statistik & Chart */}
                        {activeTab === 'statistik' && (
                            <div className="p-6 space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div className="bg-gray-50 p-5 rounded-xl border border-gray-100">
                                        <h3 className="text-sm font-bold mb-4 flex items-center gap-2 text-gray-700">
                                            <span className="w-2 h-4 bg-blue-500 rounded" /> Distribusi Status Pegawai
                                        </h3>
                                        <div className="h-52 flex justify-center">
                                            <Doughnut data={statusData} options={chartOptions} />
                                        </div>
                                    </div>
                                    <div className="bg-gray-50 p-5 rounded-xl border border-gray-100">
                                        <h3 className="text-sm font-bold mb-4 flex items-center gap-2 text-gray-700">
                                            <span className="w-2 h-4 bg-rose-500 rounded" /> Distribusi Generasi
                                        </h3>
                                        <div className="h-52 flex justify-center">
                                            <Doughnut data={genData} options={chartOptions} />
                                        </div>
                                    </div>
                                    <div className="bg-gray-50 p-5 rounded-xl border border-gray-100">
                                        <h3 className="text-sm font-bold mb-4 flex items-center gap-2 text-gray-700">
                                            <span className="w-2 h-4 bg-indigo-500 rounded" /> Pendidikan Terakhir
                                        </h3>
                                        <div className="h-72">
                                            <Bar data={eduData} options={eduChartOptions} />
                                        </div>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="bg-gray-50 p-5 rounded-xl border border-gray-100">
                                        <h3 className="text-sm font-bold mb-4 flex items-center gap-2 text-gray-700">
                                            <span className="w-2 h-4 bg-violet-500 rounded" /> Distribusi Golongan
                                        </h3>
                                        <div className="h-96">
                                            <Bar data={golData} options={golChartOptions} />
                                        </div>
                                    </div>
                                    <div className="bg-gray-50 p-5 rounded-xl border border-gray-100">
                                        <h3 className="text-sm font-bold mb-4 flex items-center gap-2 text-gray-700">
                                            <span className="w-2 h-4 bg-teal-500 rounded" /> Distribusi Masa Kerja
                                        </h3>
                                        <div className="h-64">
                                            <Bar data={tenureData} options={chartOptions} />
                                        </div>
                                    </div>
                                </div>

                                {/* Bezetting Table */}
                                <div className="bg-slate-900 text-white p-6 rounded-2xl shadow-xl overflow-hidden relative">
                                    <div className="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-indigo-500 opacity-10 rounded-full" />
                                    <div className="relative z-10">
                                        <h3 className="text-base font-bold mb-5 flex items-center gap-2">
                                            <BarChart3 className="h-5 w-5 text-indigo-400" />
                                            Analisa Bezetting (Isi vs Kebutuhan)
                                        </h3>
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead>
                                                    <tr className="border-b border-slate-700">
                                                        <th className="text-left py-3 px-4 font-semibold text-slate-400">Nama Jabatan</th>
                                                        <th className="text-center py-3 px-4 font-semibold text-slate-400">Kebutuhan (ABK)</th>
                                                        <th className="text-center py-3 px-4 font-semibold text-slate-400">Terisi Saat Ini</th>
                                                        <th className="text-center py-3 px-4 font-semibold text-slate-400">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-800">
                                                    {Object.entries(rekap.statistik_bezetting || {}).map(([jabatan, s]) => (
                                                        <tr key={jabatan} className="hover:bg-slate-800 transition-colors">
                                                            <td className="py-4 px-4 font-medium">{jabatan}</td>
                                                            <td className="py-4 px-4 text-center">{s.kebutuhan}</td>
                                                            <td className="py-4 px-4 text-center">{s.terisi}</td>
                                                            <td className="py-4 px-4 text-center">
                                                                {s.selisih > 0 ? (<span className="text-red-400 font-bold">Kurang {s.selisih}</span>)
                                                                    : s.selisih < 0 ? (<span className="text-blue-400 font-bold">Kelebihan {Math.abs(s.selisih)}</span>)
                                                                        : (<span className="text-emerald-400 font-bold">Ideal</span>)}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Tab 2: Daftar Snapshot */}
                        {activeTab === 'pegawai' && (
                            <div className="p-8 flex flex-col items-center justify-center text-center gap-5 min-h-[320px]">
                                <div className="p-5 bg-primary-50 rounded-2xl">
                                    <Users className="h-14 w-14 text-primary-500" />
                                </div>
                                <div>
                                    <h3 className="text-xl font-bold text-gray-800">Data Snapshot Pegawai</h3>
                                    <p className="text-gray-500 mt-2 text-sm max-w-md">
                                        <span className="font-bold text-gray-700">{rekap.total_pegawai_aktif} pegawai</span> tercatat dalam rekap periode{' '}
                                        <span className="font-bold text-primary-700">{MONTHS[rekap.periode_bulan - 1]} {rekap.periode_tahun}</span>.
                                        Data ini merupakan snapshot pada saat rekap digenerate.
                                    </p>
                                </div>
                                <Link
                                    href={route('rekap-bulanan.show-pegawai', { year: rekap.periode_tahun, month: rekap.periode_bulan })}
                                    className="inline-flex items-center gap-2.5 px-6 py-3 bg-primary-600 text-white font-bold text-sm rounded-xl shadow-md shadow-primary-200 hover:bg-primary-700 transition-all active:scale-95"
                                >
                                    <Table2 className="h-4 w-4" />
                                    Lihat Daftar Lengkap
                                    <ExternalLink className="h-3.5 w-3.5 opacity-70" />
                                </Link>
                                <p className="text-xs text-gray-400">Tabel dapat dicari, difilter, dan diekspor ke Excel</p>
                            </div>
                        )}

                        {/* Tab 3: Perbandingan */}
                        {activeTab === 'perbandingan' && (
                            <div className="p-6">
                                {rekap_sebelumnya ? (
                                    <>
                                        <div className="flex items-center gap-3 mb-6 p-4 bg-blue-50 rounded-xl border border-blue-100">
                                            <GitCompare className="h-5 w-5 text-blue-600 shrink-0" />
                                            <p className="text-sm text-blue-700">
                                                Membandingkan <span className="font-bold">{MONTHS[rekap.periode_bulan - 1]} {rekap.periode_tahun}</span>{' '}
                                                dengan bulan sebelumnya:{' '}
                                                <span className="font-bold">{MONTHS[rekap_sebelumnya.periode_bulan - 1]} {rekap_sebelumnya.periode_tahun}</span>
                                            </p>
                                        </div>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                            {comparisonMetrics.map((metric) => {
                                                const Icon = metric.icon;
                                                const colorMap = {
                                                    blue: 'bg-blue-50 text-blue-600 border-blue-100',
                                                    indigo: 'bg-indigo-50 text-indigo-600 border-indigo-100',
                                                    emerald: 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                                    amber: 'bg-amber-50 text-amber-600 border-amber-100',
                                                    violet: 'bg-violet-50 text-violet-600 border-violet-100',
                                                };
                                                return (
                                                    <div key={metric.label} className="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                                                        <div className="flex items-start justify-between mb-4">
                                                            <div className={`p-2 rounded-lg border ${colorMap[metric.color]}`}>
                                                                <Icon className="h-4 w-4" />
                                                            </div>
                                                            <DeltaChip curr={metric.curr} prev={metric.prev} invertDelta={metric.invertDelta} />
                                                        </div>
                                                        <p className="text-3xl font-black text-gray-900">{metric.curr ?? '—'}</p>
                                                        <p className="text-xs font-bold text-gray-500 mt-1.5 uppercase tracking-wider">{metric.label}</p>
                                                        {metric.prev !== null && metric.prev !== undefined && (
                                                            <p className="text-xs text-gray-400 mt-1">
                                                                Sebelumnya: <span className="font-semibold text-gray-600">{metric.prev}</span>
                                                            </p>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-16 text-center gap-4">
                                        <div className="p-4 bg-gray-50 rounded-2xl">
                                            <GitCompare className="h-12 w-12 text-gray-300" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-bold text-gray-800">Data Bulan Sebelumnya Tidak Tersedia</h3>
                                            <p className="text-gray-500 text-sm mt-2 max-w-xs">
                                                Tidak ada rekap untuk{' '}
                                                {(() => {
                                                    const d = new Date(rekap.periode_tahun, rekap.periode_bulan - 2, 1);
                                                    return `${MONTHS[d.getMonth()]} ${d.getFullYear()}`;
                                                })()}.
                                                Generate rekap bulan tersebut untuk melihat perbandingan.
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
