import React from 'react';
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
    Info
} from 'lucide-react';
import StatusBadge from '@/Components/StatusBadge';
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

export default function Show({ auth, rekap }) {
    const months = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];

    const chartOptions = {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            },
        },
    };

    const isAdmin = auth.user.roles.includes('admin');
    const isKasi = auth.user.roles.includes('kasi');
    const isKaCdk = auth.user.roles.includes('kacdk');
    const userPermissions = auth.user.permissions || [];

    const canApprove = userPermissions.includes('kepegawaian.approve') || isAdmin;

    const handleSingleAction = (id, action) => {
        let title = '';
        let text = '';
        let icon = 'warning';
        let confirmText = '';
        let confirmColor = '#258a55';
        let showInput = false;
        let loadingMsg = '';

        switch (action) {
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
                icon = 'check-circle';
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
                    }
                });
            }
        });
    };

    // 1. Chart Status Pegawai
    const statusData = {
        labels: Object.keys(rekap.statistik_status_pegawai || {}),
        datasets: [{
            data: Object.values(rekap.statistik_status_pegawai || {}),
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)', // Blue
                'rgba(16, 185, 129, 0.8)', // Green
                'rgba(245, 158, 11, 0.8)', // Amber
                'rgba(107, 114, 128, 0.8)', // Gray
            ],
            borderColor: '#fff',
            borderWidth: 2,
        }],
    };

    // 2. Chart Pendidikan
    const eduData = {
        labels: Object.keys(rekap.statistik_pendidikan || {}),
        datasets: [{
            label: 'Jumlah Pegawai',
            data: Object.values(rekap.statistik_pendidikan || {}),
            backgroundColor: 'rgba(99, 102, 241, 0.8)', // Indigo
        }],
    };

    // 3. Chart Golongan
    const golData = {
        labels: Object.keys(rekap.statistik_golongan || {}),
        datasets: [{
            label: 'Jumlah Pegawai',
            data: Object.values(rekap.statistik_golongan || {}),
            backgroundColor: 'rgba(139, 92, 246, 0.8)', // Violet
        }],
    };

    // 4. Chart Generasi
    const genData = {
        labels: Object.keys(rekap.statistik_generasi || {}),
        datasets: [{
            data: Object.values(rekap.statistik_generasi || {}),
            backgroundColor: [
                'rgba(244, 63, 94, 0.8)', // Rose
                'rgba(217, 70, 239, 0.8)', // Fuchsia
                'rgba(168, 85, 247, 0.8)', // Purple
                'rgba(99, 102, 241, 0.8)', // Indigo
            ],
        }],
    };

    // 5. Chart Masa Kerja
    const tenureData = {
        labels: Object.keys(rekap.statistik_masa_kerja || {}),
        datasets: [{
            label: 'Jumlah Pegawai',
            data: Object.values(rekap.statistik_masa_kerja || {}),
            backgroundColor: 'rgba(20, 184, 166, 0.8)', // Teal
        }],
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <Link href={route('rekap-bulanan.index', { year: rekap.periode_tahun })} className="mr-4 text-gray-500 hover:text-gray-700">
                            <ArrowLeft className="h-6 w-6" />
                        </Link>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight underline decoration-indigo-500 decoration-4 underline-offset-8">
                            Analisa Kepegawaian — {months[rekap.periode_bulan - 1]} {rekap.periode_tahun}
                        </h2>
                    </div>

                    <div className="flex items-center gap-3">
                        <StatusBadge status={rekap.status} />
                        
                        <div className="flex gap-2 border-l pl-4 ml-1 border-gray-200">
                            {(rekap.status === 'draft' || rekap.status === 'rejected') && (
                                <button
                                    onClick={() => handleSingleAction(rekap.id, 'submit')}
                                    className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-blue-700 transition-all gap-2"
                                >
                                    <Send className="h-4 w-4" />
                                    Ajukan Verifikasi
                                </button>
                            )}

                            {canApprove && isKasi && rekap.status === 'waiting_kasi' && (
                                <>
                                    <button
                                        onClick={() => handleSingleAction(rekap.id, 'approve')}
                                        className="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-emerald-700 transition-all gap-2"
                                    >
                                        <CheckCircle2 className="h-4 w-4" />
                                        Setujui
                                    </button>
                                    <button
                                        onClick={() => handleSingleAction(rekap.id, 'reject')}
                                        className="inline-flex items-center px-4 py-2 bg-rose-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-rose-700 transition-all gap-2"
                                    >
                                        <XCircle className="h-4 w-4" />
                                        Tolak
                                    </button>
                                </>
                            )}

                            {canApprove && isKaCdk && rekap.status === 'waiting_cdk' && (
                                <>
                                    <button
                                        onClick={() => handleSingleAction(rekap.id, 'approve')}
                                        className="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-emerald-700 transition-all gap-2"
                                    >
                                        <CheckCircle2 className="h-4 w-4" />
                                        Setujui Akhir
                                    </button>
                                    <button
                                        onClick={() => handleSingleAction(rekap.id, 'reject')}
                                        className="inline-flex items-center px-4 py-2 bg-rose-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-rose-700 transition-all gap-2"
                                    >
                                        <XCircle className="h-4 w-4" />
                                        Tolak
                                    </button>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            }
        >
            <Head title={`Analisa Rekap ${months[rekap.periode_bulan - 1]} ${rekap.periode_tahun}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {rekap.status === 'rejected' && rekap.rejection_note && (
                        <div className="bg-rose-50 border border-rose-200 p-4 rounded-xl flex items-start gap-3 animate-pulse">
                            <Info className="h-5 w-5 text-rose-500 mt-0.5" />
                            <div>
                                <h4 className="font-bold text-rose-800 text-sm">Alasan Penolakan:</h4>
                                <p className="text-rose-700 text-sm mt-1">"{rekap.rejection_note}"</p>
                            </div>
                        </div>
                    )}
                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Total Pegawai Aktif</p>
                                    <p className="text-2xl font-bold text-gray-900">{rekap.total_pegawai_aktif}</p>
                                </div>
                                <Users className="h-10 w-10 text-blue-100" />
                            </div>
                        </div>
                        <div className="bg-white p-6 rounded-lg shadow-sm border-l-4 border-emerald-500">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">PNS / PPPK</p>
                                    <p className="text-2xl font-bold text-gray-900">{rekap.total_pns} / {rekap.total_pppk}</p>
                                </div>
                                <IdCard className="h-10 w-10 text-emerald-100" />
                            </div>
                        </div>
                        <div className="bg-white p-6 rounded-lg shadow-sm border-l-4 border-orange-500">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Pensiun (Tahun Ini)</p>
                                    <p className="text-2xl font-bold text-gray-900">{rekap.total_pensiun_tahun_ini}</p>
                                </div>
                                <AlertTriangle className="h-10 w-10 text-orange-100" />
                            </div>
                        </div>
                        <div className="bg-white p-6 rounded-lg shadow-sm border-l-4 border-indigo-500">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">KGB Bulan Ini</p>
                                    <p className="text-2xl font-bold text-gray-900">{rekap.kgb_jatuh_bulan_ini}</p>
                                </div>
                                <GraduationCap className="h-10 w-10 text-indigo-100" />
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Status Pegawai */}
                        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                            <h3 className="text-lg font-semibold mb-6 flex items-center">
                                <span className="w-2 h-6 bg-blue-500 rounded mr-2"></span>
                                Distribusi Status Pegawai
                            </h3>
                            <div className="h-64 flex justify-center">
                                <Doughnut data={statusData} options={chartOptions} />
                            </div>
                        </div>

                        {/* Generasi */}
                        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                            <h3 className="text-lg font-semibold mb-6 flex items-center">
                                <span className="w-2 h-6 bg-rose-500 rounded mr-2"></span>
                                Distribusi Generasi
                            </h3>
                            <div className="h-64 flex justify-center">
                                <Doughnut data={genData} options={chartOptions} />
                            </div>
                        </div>

                        {/* Pendidikan */}
                        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                            <h3 className="text-lg font-semibold mb-6 flex items-center">
                                <span className="w-2 h-6 bg-indigo-500 rounded mr-2"></span>
                                Pendidikan Terakhir
                            </h3>
                            <div className="h-64">
                                <Bar data={eduData} options={{...chartOptions, indexAxis: 'y'}} />
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Golongan */}
                        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                            <h3 className="text-lg font-semibold mb-6 flex items-center">
                                <span className="w-2 h-6 bg-violet-500 rounded mr-2"></span>
                                Distribusi Golongan
                            </h3>
                            <div className="h-80">
                                <Bar data={golData} options={chartOptions} />
                            </div>
                        </div>

                        {/* Masa Kerja */}
                        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                            <h3 className="text-lg font-semibold mb-6 flex items-center">
                                <span className="w-2 h-6 bg-teal-500 rounded mr-2"></span>
                                Distribusi Masa Kerja
                            </h3>
                            <div className="h-80">
                                <Bar data={tenureData} options={chartOptions} />
                            </div>
                        </div>
                    </div>

                    {/* Analysis Section */}
                    <div className="bg-slate-900 text-white p-8 rounded-2xl shadow-xl overflow-hidden relative">
                        <div className="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-indigo-500 opacity-10 rounded-full"></div>
                        <div className="relative z-10">
                            <h3 className="text-xl font-bold mb-6 flex items-center">
                                <BarChart3 className="h-7 w-7 mr-3 text-indigo-400" />
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
                                                    {s.selisih > 0 ? (
                                                        <span className="text-red-400 font-bold">Kurang {s.selisih}</span>
                                                    ) : s.selisih < 0 ? (
                                                        <span className="text-blue-400 font-bold">Kelebihan {Math.abs(s.selisih)}</span>
                                                    ) : (
                                                        <span className="text-emerald-400 font-bold">Ideal</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
