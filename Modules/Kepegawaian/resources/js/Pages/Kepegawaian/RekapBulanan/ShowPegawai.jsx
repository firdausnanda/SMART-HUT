import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Search,
    Download,
    Clock,
    User,
    Users,
    BadgeCheck
} from 'lucide-react';
import Pagination from '@/Components/Pagination';
import TextInput from '@/Components/TextInput';
import LoadingOverlay from '@/Components/LoadingOverlay';
import { router } from '@inertiajs/react';
import { pickBy, debounce } from 'lodash';

export default function ShowPegawai({ auth, pegawais, periode, filters, rekap }) {
    const months = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];

    const handleSearch = debounce((e) => {
        const value = e.target.value;
        const params = pickBy({ ...filters, search: value });

        router.get(route('rekap-bulanan.show-pegawai', periode), params, {
            preserveState: true,
            replace: true,
        });
    }, 300);

    const [isLoading, setIsLoading] = React.useState(false);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Data Detail Rekap Pegawai</h2>}
        >
            <Head title={`Data Pegawai ${months[periode.month - 1]} ${periode.year}`} />

            <LoadingOverlay isLoading={isLoading} />

            <div className="py-6 space-y-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Modern Header Section */}
                    <div className="bg-gradient-to-r from-primary-800 to-primary-600 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
                        <div className="absolute right-0 top-0 h-full w-1/3 bg-white/5 transform skew-x-12 shrink-0"></div>
                        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div className="flex items-center gap-4">
                                <Link
                                    href={route('rekap-bulanan.index', { year: periode.year })}
                                    className="p-2 bg-white/10 hover:bg-white/20 rounded-xl transition-colors shrink-0"
                                    title="Kembali ke Daftar Rekap"
                                >
                                    <ArrowLeft className="h-6 w-6 text-white" />
                                </Link>
                                <div>
                                    <h3 className="text-2xl font-bold font-display tracking-tight">Data Pegawai Bulanan</h3>
                                    <p className="mt-1 text-primary-100 opacity-90 max-w-xl text-sm font-medium">
                                        Periode {months[periode.month - 1]} {periode.year} — Detail snapshot data kepegawaian.
                                    </p>
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <a
                                    href={route('rekap-bulanan.export', periode)}
                                    target="_blank"
                                    className="flex items-center gap-2 px-5 py-2.5 bg-primary-700 text-primary-100 rounded-xl font-bold text-sm shadow-sm hover:bg-primary-800 transition-all border border-primary-600/50 active:scale-95"
                                >
                                    <Download className="h-5 w-5" />
                                    Excel
                                </a>
                            </div>
                        </div>
                    </div>


                    {/* Table Section Card */}
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative">

                        {/* Table Tool Bar */}
                        <div className="p-6 border-b border-gray-100 flex flex-col md:flex-row md:justify-between md:items-center gap-6 bg-slate-50/20">
                            <div className="flex flex-wrap items-center gap-4 flex-1">
                                <h3 className="font-bold text-gray-800 whitespace-nowrap">Daftar Pegawai</h3>
                                <div className="h-6 w-px bg-gray-200 hidden md:block"></div>

                                <div className="relative w-full max-w-sm">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <Search className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <TextInput
                                        className="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all shadow-none"
                                        placeholder="Cari NIP atau Nama..."
                                        defaultValue={filters.search}
                                        onChange={handleSearch}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Data Table */}
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead className="bg-slate-50/40 text-slate-500 uppercase tracking-wider text-[11px] font-bold">
                                    <tr className="border-b border-gray-100">
                                        <th className="px-6 py-4 w-10">No</th>
                                        <th className="px-6 py-4">Pegawai</th>
                                        <th className="px-6 py-4">Status</th>
                                        <th className="px-6 py-4">Gol / Jabatan</th>
                                        <th className="px-6 py-4">Pendidikan</th>
                                        <th className="px-6 py-4">Masa Kerja</th>
                                        <th className="px-6 py-4 text-center">Proyeksi Pensiun</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 bg-white">
                                    {pegawais.data.length === 0 ? (
                                        <tr>
                                            <td colSpan="7" className="text-center py-24">
                                                <div className="flex flex-col items-center">
                                                    <div className="p-5 bg-gray-50 rounded-full mb-4 text-gray-200">
                                                        <Search className="w-12 h-12" />
                                                    </div>
                                                    <p className="text-gray-500 font-bold tracking-tight">Tidak ada data pegawai ditemukan</p>
                                                    <p className="text-gray-400 text-xs mt-1 italic">Silakan gunakan filter atau kata kunci lain</p>
                                                </div>
                                            </td>
                                        </tr>
                                    ) : (
                                        pegawais.data.map((pegawai, index) => (
                                            <tr key={pegawai.id} className="hover:bg-primary-50/30 transition-colors group">
                                                <td className="px-6 py-4 text-gray-400 font-medium">
                                                    {(pegawais.current_page - 1) * pegawais.per_page + index + 1}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center">
                                                        <div className="ml-3">
                                                            <div className="text-sm font-bold text-gray-900 leading-none group-hover:text-primary-700 transition-colors">{pegawai.nama_lengkap}</div>
                                                            <div className="text-[10px] text-gray-400 font-bold mt-1 uppercase tracking-tight">NIP. {pegawai.nip}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest border transition-all duration-300 shadow-sm ${pegawai.status_pegawai === 'PNS' ? 'bg-blue-50/50 text-blue-700 border-blue-200/60 shadow-blue-500/5' :
                                                            pegawai.status_pegawai === 'PPPK' ? 'bg-emerald-50/50 text-emerald-700 border-emerald-200/60 shadow-emerald-500/5' :
                                                                'bg-slate-50/50 text-slate-600 border-slate-200/60 shadow-slate-500/5'
                                                        }`}>
                                                        <div className={`w-1.5 h-1.5 rounded-full mr-2 opacity-80 ${pegawai.status_pegawai === 'PNS' ? 'bg-blue-600' :
                                                                pegawai.status_pegawai === 'PPPK' ? 'bg-emerald-600' :
                                                                    'bg-slate-400'
                                                            }`}></div>
                                                        {pegawai.status_pegawai}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="font-bold text-gray-900 text-xs">{pegawai.pangkat_golongan || '-'}</div>
                                                    <div className="text-[10px] text-primary-600 font-bold uppercase tracking-tighter truncate max-w-[180px]" title={pegawai.nama_jabatan_snapshot}>
                                                        {pegawai.nama_jabatan_snapshot || '-'}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-xs font-semibold text-gray-600">
                                                    {pegawai.pendidikan_terakhir}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-xs font-bold text-gray-500">
                                                    {pegawai.masa_kerja_tahun} <span className="font-normal text-gray-400 capitalize">Tahun</span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                                    <div className="text-[11px] text-gray-900 font-black">{pegawai.proyeksi_pensiun ? new Date(pegawai.proyeksi_pensiun).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'}</div>
                                                    <div className={`text-[10px] font-bold uppercase mt-0.5 ${pegawai.bulan_pensiun_tersisa <= 6 ? 'text-rose-500 animate-pulse' : 'text-slate-400'}`}>
                                                        {pegawai.bulan_pensiun_tersisa} bln lagi
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination Footer */}
                        <div className="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex flex-col md:flex-row items-center justify-between gap-4">
                            <div className="text-sm text-gray-500">
                                Menampilkan <span className="font-bold text-gray-700">{pegawais.from || 0}</span> - <span className="font-bold text-gray-700">{pegawais.to || 0}</span> dari <span className="font-bold text-gray-700">{pegawais.total}</span> data pegawai
                            </div>
                            <div className="flex items-center gap-1">
                                <Pagination links={pegawais.links} />
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </AuthenticatedLayout>
    );
}
