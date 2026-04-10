import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import {
    Calendar,
    ChevronDown,
    Filter,
    Download,
    Search,
    User,
    Building2,
    BadgeCheck,
    AlertCircle,
    Clock,
    UserMinus,
    TrendingUp,
    Users
} from 'lucide-react';
import TextInput from '@/Components/TextInput';
import Select from 'react-select';
import LoadingOverlay from '@/Components/LoadingOverlay';

export default function Proyeksi({ auth, proyeksiKgb, proyeksiPensiun, filters, unitList }) {
    const [activeTab, setActiveTab] = useState('kgb');
    const [searchTerm, setSearchTerm] = useState('');
    const [isLoading, setIsLoading] = useState(false);

    const isAdmin = auth.user.roles.includes('admin');
    const userPermissions = auth.user.permissions || [];
    const canExport = userPermissions.includes('kepegawaian.export') || isAdmin;

    const months = [
        { value: 'all', label: 'Semua Bulan' },
        { value: 1, label: 'Januari' },
        { value: 2, label: 'Februari' },
        { value: 3, label: 'Maret' },
        { value: 4, label: 'April' },
        { value: 5, label: 'Mei' },
        { value: 6, label: 'Juni' },
        { value: 7, label: 'Juli' },
        { value: 8, label: 'Agustus' },
        { value: 9, label: 'September' },
        { value: 10, label: 'Oktober' },
        { value: 11, label: 'November' },
        { value: 12, label: 'Desember' },
    ];

    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 11 }, (_, i) => currentYear - 2 + i).map(y => ({ value: y, label: y.toString() }));

    const unitOptions = [
        { value: '', label: 'Semua Unit Kerja' },
        ...(unitList || []).map(unit => ({ value: unit, label: unit }))
    ];

    const handleFilterChange = (key, value) => {
        const newFilters = {
            year: key === 'year' ? value : filters.year,
            month: key === 'month' ? value : filters.month,
            unit_kerja: key === 'unit_kerja' ? value : filters.unit_kerja,
        };

        router.get(route('proyeksi-gaji.index'), newFilters, {
            preserveState: true,
            preserveScroll: true,
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    };

    const handleExport = () => {
        const queryParams = new URLSearchParams({
            year: filters.year,
            month: filters.month,
            unit_kerja: filters.unit_kerja || '',
        }).toString();

        window.location.href = route('proyeksi-gaji.export') + '?' + queryParams;
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
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'Sudah Waktunya':
            case 'Waktunya Pensiun':
                return <span className="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-700 border border-rose-200 shadow-sm"><AlertCircle className="w-3 h-3 mr-1" /> {status}</span>;
            case 'Bulan Ini':
                return <span className="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200 shadow-sm"><Clock className="w-3 h-3 mr-1" /> {status}</span>;
            case 'Akan Datang':
                return <span className="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200 shadow-sm"><Calendar className="w-3 h-3 mr-1" /> {status}</span>;
            default:
                return <span className="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200 shadow-sm">{status}</span>;
        }
    };

    const filteredData = (activeTab === 'kgb' ? proyeksiKgb : proyeksiPensiun).filter(item =>
        item.nama?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.nip?.includes(searchTerm)
    );

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Proyeksi Gaji Berkala dan Pensiun</h2>}
        >
            <Head title="Proyeksi Gaji Berkala dan Pensiun" />
            <LoadingOverlay isLoading={isLoading} />

            <div className="py-6 space-y-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Modern Header Section */}
                    <div className="bg-gradient-to-r from-primary-800 to-primary-600 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
                        <div className="absolute right-0 top-0 h-full w-1/3 bg-white/5 transform skew-x-12 shrink-0"></div>
                        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h3 className="text-2xl font-bold font-display">Proyeksi Gaji Berkala dan Pensiun</h3>
                                <p className="mt-1 text-primary-100 opacity-90 max-w-xl text-sm">
                                    Pantau dan kelola jadwal kenaikan gaji berkala serta masa pensiun pegawai di lingkungan CDK Trenggalek.
                                </p>
                            </div>
                            <div className="flex gap-2">
                                {canExport && (
                                    <button
                                        onClick={handleExport}
                                        className="flex items-center gap-2 px-4 py-2.5 bg-primary-700 text-primary-100 rounded-xl font-bold text-sm shadow-sm hover:bg-primary-800 transition-colors border border-primary-600/50"
                                    >
                                        <Download className="w-4 h-4" />
                                        Export Laporan
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-bold text-gray-400 uppercase tracking-wider font-display">Sudah Waktunya KGB</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-1">
                                        {proyeksiKgb.filter(p => p.status === 'Sudah Waktunya' || p.status === 'Bulan Ini').length}
                                        <span className="text-sm font-normal text-gray-400 ml-2">Orang</span>
                                    </p>
                                </div>
                                <div className="p-3 bg-rose-50 rounded-xl text-rose-600 shrink-0">
                                    <TrendingUp className="w-6 h-6" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-bold text-gray-400 uppercase tracking-wider font-display">Akan Pensiun ({filters.year})</p>
                                    <p className="text-3xl font-bold text-primary-600 mt-1">
                                        {proyeksiPensiun.length}
                                        <span className="text-sm font-normal text-gray-400 ml-2">Orang</span>
                                    </p>
                                </div>
                                <div className="p-3 bg-primary-50 rounded-xl text-primary-600 shrink-0">
                                    <Users className="w-6 h-6" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-bold text-gray-400 uppercase tracking-wider font-display">Tahun Proyeksi</p>
                                    <p className="text-3xl font-bold text-blue-600 mt-1">{filters.year}</p>
                                </div>
                                <div className="p-3 bg-blue-50 rounded-xl text-blue-600 shrink-0">
                                    <Calendar className="w-6 h-6" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Table Section Card */}
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative">
                        {/* Table Tool Bar with React Select */}
                        <div className="p-6 border-b border-gray-100 flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                            <div className="flex flex-col md:flex-row md:items-center gap-4 flex-1">
                                <div className="flex items-center gap-3">
                                    <h3 className="font-bold text-gray-800 whitespace-nowrap">Daftar Proyeksi</h3>
                                    <div className="h-6 w-px bg-gray-200 hidden md:block"></div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 flex-1">
                                    <div className="flex flex-col gap-1.5 min-w-[120px]">
                                        <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Tahun</span>
                                        <Select
                                            options={years}
                                            value={years.find(y => y.value === filters.year)}
                                            onChange={(opt) => handleFilterChange('year', opt?.value)}
                                            styles={selectStyles}
                                            isSearchable={false}
                                        />
                                    </div>

                                    <div className="flex flex-col gap-1.5 min-w-[160px]">
                                        <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Bulan</span>
                                        <Select
                                            options={months}
                                            value={months.find(m => m.value.toString() === filters.month.toString())}
                                            onChange={(opt) => handleFilterChange('month', opt?.value)}
                                            styles={selectStyles}
                                            isSearchable={false}
                                        />
                                    </div>

                                    <div className="flex flex-col gap-1.5 min-w-[200px]">
                                        <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Unit Kerja</span>
                                        <Select
                                            options={unitOptions}
                                            value={unitOptions.find(u => u.value === (filters.unit_kerja || '')) || unitOptions[0]}
                                            onChange={(opt) => handleFilterChange('unit_kerja', opt?.value)}
                                            styles={selectStyles}
                                            placeholder="Pilih Unit Kerja..."
                                        />
                                    </div>
                                </div>

                                <div className="flex flex-col gap-1.5 max-w-xs w-full lg:ml-4">
                                    <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Cari Pegawai</span>
                                    <div className="relative">
                                        <TextInput
                                            className="w-full text-sm pr-10 pl-4 py-2"
                                            placeholder="NIP atau Nama..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                        <div className="absolute right-3 top-1/2 -translate-y-1/2">
                                            <Search className="h-4 w-4 text-gray-400" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Tabs within the white card */}
                        <div className="flex px-6 pt-6 gap-4 border-b border-gray-50 flex-none pb-4 overflow-x-auto no-scrollbar">
                            <button
                                onClick={() => setActiveTab('kgb')}
                                className={`flex items-center space-x-2 pb-2 text-sm font-bold transition-all border-b-2 whitespace-nowrap px-1 ${activeTab === 'kgb'
                                    ? 'text-primary-700 border-primary-600 font-black'
                                    : 'text-gray-400 border-transparent hover:text-gray-600'
                                    }`}
                            >
                                <BadgeCheck className={`w-4 h-4 ${activeTab === 'kgb' ? 'text-primary-600' : ''}`} />
                                <span>Proyeksi KGB</span>
                                <span className={`ml-2 px-2 py-0.5 rounded-lg text-[10px] font-black ${activeTab === 'kgb' ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-400'
                                    }`}>
                                    {proyeksiKgb.length}
                                </span>
                            </button>
                            <button
                                onClick={() => setActiveTab('pensiun')}
                                className={`flex items-center space-x-2 pb-2 text-sm font-bold transition-all border-b-2 whitespace-nowrap px-1 ${activeTab === 'pensiun'
                                    ? 'text-primary-700 border-primary-600 font-black'
                                    : 'text-gray-400 border-transparent hover:text-gray-600'
                                    }`}
                            >
                                <UserMinus className={`w-4 h-4 ${activeTab === 'pensiun' ? 'text-primary-600' : ''}`} />
                                <span>Proyeksi Pensiun</span>
                                <span className={`ml-2 px-2 py-0.5 rounded-lg text-[10px] font-black ${activeTab === 'pensiun' ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-400'
                                    }`}>
                                    {proyeksiPensiun.length}
                                </span>
                            </button>
                        </div>

                        {/* Data Table */}
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-gray-500">
                                <thead className="bg-gray-50/50 text-gray-700 uppercase tracking-wider text-[11px] font-bold">
                                    <tr>
                                        <th className="px-6 py-4 w-4">No</th>
                                        <th className="px-6 py-4">Pegawai</th>
                                        <th className="px-6 py-4">Golongan / Unit</th>
                                        {activeTab === 'kgb' ? (
                                            <>
                                                <th className="px-6 py-4">TMT Terakhir</th>
                                                <th className="px-6 py-4">TMT Berikutnya</th>
                                            </>
                                        ) : (
                                            <>
                                                <th className="px-6 py-4">Tgl Lahir / BUP</th>
                                                <th className="px-6 py-4">TMT Pensiun</th>
                                            </>
                                        )}
                                        <th className="px-6 py-4 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {filteredData.length > 0 ? (
                                        filteredData.map((item, index) => (
                                            <tr key={item.id} className="hover:bg-primary-50/30 transition-colors group">
                                                <td className="px-6 py-4 text-gray-400 font-medium">
                                                    {index + 1}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center">
                                                        <div className="h-9 w-9 bg-slate-100 rounded-full flex items-center justify-center text-[10px] font-black text-slate-500 border border-slate-200 shrink-0 shadow-sm">
                                                            {item.nama?.substring(0, 2).toUpperCase()}
                                                        </div>
                                                        <div className="ml-3">
                                                            <div className="text-sm font-bold text-gray-900 leading-none">{item.nama}</div>
                                                            <div className="text-[10px] text-gray-400 font-bold mt-1 uppercase tracking-tight">NIP. {item.nip}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="font-bold text-gray-900">{item.pangkat_golongan || '-'}</div>
                                                    <div className="text-xs text-primary-600 font-bold uppercase tracking-tighter truncate max-w-[200px]">{item.unit_kerja || '-'}</div>
                                                </td>
                                                {activeTab === 'kgb' ? (
                                                    <>
                                                        <td className="px-6 py-4 text-gray-600 font-medium whitespace-nowrap">
                                                            {item.tmt_kgb_terakhir || <span className="text-gray-300 italic text-[10px]">Belum ada data</span>}
                                                        </td>
                                                        <td className="px-6 py-4 text-primary-700 font-black text-base whitespace-nowrap">
                                                            {item.tmt_kgb_berikutnya}
                                                        </td>
                                                    </>
                                                ) : (
                                                    <>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div className="text-gray-900 font-bold">{item.tanggal_lahir}</div>
                                                            <div className="text-[10px] text-gray-400 font-bold uppercase tracking-wider">BUP: {item.bup} Tahun</div>
                                                        </td>
                                                        <td className="px-6 py-4 text-rose-600 font-black text-base whitespace-nowrap">
                                                            {item.tmt_pensiun}
                                                        </td>
                                                    </>
                                                )}
                                                <td className="px-6 py-4 text-center">
                                                    {getStatusBadge(item.status)}
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={7} className="text-center py-24">
                                                <div className="flex flex-col items-center">
                                                    <div className="p-5 bg-gray-50 rounded-full mb-4 text-gray-200">
                                                        <Search className="w-12 h-12" />
                                                    </div>
                                                    <p className="text-gray-500 font-bold tracking-tight">Tidak ada data proyeksi ditemukan</p>
                                                    <p className="text-gray-400 text-xs mt-1 italic">Silakan gunakan filter atau kata kunci lain</p>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
