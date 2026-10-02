import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import LoadingOverlay from '@/Components/LoadingOverlay';
import Select from 'react-select';

export default function Dashboard({ auth, stats, filters, availableYears, recentActivities }) {
    const [isLoading, setIsLoading] = useState(false);

    const handleYearChange = (year) => {
        setIsLoading(true);
        router.get(route('dashboard'), { year }, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsLoading(false)
        });
    };

    // Helper for formatting currency
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value);
    };

    // Helper for formatting number
    const formatNumber = (value) => {
        return new Intl.NumberFormat('id-ID').format(value);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard Overview</h2>}
        >
            <Head title="Dashboard" />

            <LoadingOverlay isLoading={isLoading} />

            <div className="space-y-4 md:space-y-6">
                {/* Welcome Section */}
                <div className="bg-gradient-to-r from-primary-800 to-primary-600 rounded-2xl p-6 md:p-10 text-white shadow-lg relative overflow-hidden">
                    <div className="absolute right-0 top-0 h-full w-1/2 bg-white/5 transform skew-x-12 shrink-0"></div>
                    <div className="relative z-10">
                        <h3 className="text-3xl font-bold font-display">Selamat Datang, {auth.user.name}!</h3>
                        <p className="mt-2 text-primary-100 max-w-xl">
                            SMART-HUT (Sistem Monitoring Analisis Data Real Time – Kehutanan). <br />
                            Pantau data statistik kehutanan secara realtime dan akurat.
                        </p>
                    </div>
                </div>

                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-2">
                    <h3 className="text-xl font-bold text-gray-800">Ringkasan Statistik {filters.year}</h3>
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm">
                            <span className="text-xs font-bold text-gray-500">Tahun:</span>
                            <Select
                                value={{ value: filters.year, label: filters.year }}
                                onChange={(selectedOption) => handleYearChange(selectedOption.value)}
                                options={availableYears.map(year => ({ value: year, label: year }))}
                                isDisabled={isLoading}
                                styles={{
                                    control: (base, state) => ({
                                        ...base,
                                        minHeight: 'auto',
                                        backgroundColor: 'transparent',
                                        borderColor: 'transparent',
                                        boxShadow: 'none',
                                        '&:hover': {
                                            borderColor: 'transparent'
                                        },
                                        fontSize: '0.875rem',
                                        fontWeight: '700',
                                        color: '#374151',
                                        cursor: 'pointer',
                                        border: 'none',
                                    }),
                                    valueContainer: (base) => ({
                                        ...base,
                                        padding: '0',
                                    }),
                                    input: (base) => ({
                                        ...base,
                                        margin: 0,
                                        padding: 0,
                                    }),
                                    singleValue: (base) => ({
                                        ...base,
                                        color: '#374151',
                                    }),
                                    menu: (base) => ({
                                        ...base,
                                        borderRadius: '0.5rem',
                                        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                                        overflow: 'hidden',
                                        zIndex: 50,
                                        width: 'max-content',
                                        minWidth: '100%',
                                        right: 0
                                    }),
                                    option: (base, state) => ({
                                        ...base,
                                        backgroundColor: state.isSelected ? '#10b981' : state.isFocused ? '#d1fae5' : 'white',
                                        color: state.isSelected ? 'white' : '#4b5563',
                                        fontSize: '0.875rem',
                                        fontWeight: '600',
                                        cursor: 'pointer',
                                        textAlign: 'center',
                                        '&:active': {
                                            backgroundColor: '#059669',
                                            color: 'white',
                                        }
                                    }),
                                    dropdownIndicator: (base) => ({
                                        ...base,
                                        color: '#6b7280',
                                        padding: '0 0 0 4px',
                                        '&:hover': {
                                            color: '#374151'
                                        }
                                    }),
                                    indicatorSeparator: () => ({
                                        display: 'none',
                                    }),
                                }}
                                isSearchable={false}
                            />
                        </div>
                    </div>
                </div>

                {/* Main Stats Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">

                    {/* Stat Card 1: Rehabilitasi Lahan */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Rehabilitasi Lahan</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">{formatNumber(stats.rehabilitation.total)} <span className="text-sm font-normal text-gray-400">Ha</span></p>
                            </div>
                            <div className="p-3 bg-primary-50 rounded-lg text-primary-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                        </div>
                        <div className={`mt-4 flex items-center text-sm ${stats.rehabilitation.growth > 0 ? 'text-green-600' : stats.rehabilitation.growth < 0 ? 'text-red-600' : 'text-gray-500'}`}>
                            {stats.rehabilitation.growth > 0 ? (
                                <svg className="h-4 w-4 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            ) : stats.rehabilitation.growth < 0 ? (
                                <svg className="h-4 w-4 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
                                </svg>
                            ) : (
                                <svg className="h-4 w-4 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 12H6" />
                                </svg>
                            )}
                            <span className="font-bold">
                                {stats.rehabilitation.growth > 0 ? `+${stats.rehabilitation.growth}%` : `${stats.rehabilitation.growth ?? 0}%`}
                            </span>
                            <span className="ml-1 opacity-70">dari tahun lalu</span>
                        </div>
                    </div>

                    {/* Stat Card: Penghijauan Lingkungan */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Penghijauan Ling.</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">
                                    {formatNumber(stats.penghijauan_lingkungan?.total || 0)} <span className="text-xs font-normal text-gray-400">Ha</span>
                                </p>
                            </div>
                            <div className="p-3 bg-green-50 rounded-lg text-green-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            </div>
                        </div>
                        <div className={`mt-4 flex items-center text-xs ${stats.penghijauan_lingkungan?.growth > 0 ? 'text-green-600' : stats.penghijauan_lingkungan?.growth < 0 ? 'text-red-600' : 'text-gray-500'}`}>
                            <span className="font-bold">
                                {stats.penghijauan_lingkungan?.growth > 0 ? `+${stats.penghijauan_lingkungan.growth}%` : `${stats.penghijauan_lingkungan?.growth ?? 0}%`}
                            </span>
                            <span className="ml-1 opacity-70">dari tahun lalu</span>
                        </div>
                    </div>

                    {/* Stat Card: Reboisasi PS */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Reboisasi PS</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">
                                    {formatNumber(stats.reboisasi_ps?.total || 0)} <span className="text-xs font-normal text-gray-400">Ha</span>
                                </p>
                            </div>
                            <div className="p-3 bg-teal-50 rounded-lg text-teal-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <div className={`mt-4 flex items-center text-xs ${stats.reboisasi_ps?.growth > 0 ? 'text-green-600' : stats.reboisasi_ps?.growth < 0 ? 'text-red-600' : 'text-gray-500'}`}>
                            <span className="font-bold">
                                {stats.reboisasi_ps?.growth > 0 ? `+${stats.reboisasi_ps.growth}%` : `${stats.reboisasi_ps?.growth ?? 0}%`}
                            </span>
                            <span className="ml-1 opacity-70">dari tahun lalu</span>
                        </div>
                    </div>

                    {/* Stat Card: Kebakaran Hutan */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Kebakaran Hutan</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">
                                    {formatNumber(stats.fires?.total || 0)} <span className="text-xs font-normal text-gray-400">Kali</span>
                                </p>
                            </div>
                            <div className="p-3 bg-orange-50 rounded-lg text-orange-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.99 7.99 0 0120 13a7.99 7.99 0 01-2.343 5.657z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.879 16.121A3 3 0 1012.015 11L11 14l-0.657 2.121z" />
                                </svg>
                            </div>
                        </div>
                        <div className={`mt-4 flex items-center text-xs ${stats.fires?.growth < 0 ? 'text-green-600' : stats.fires?.growth > 0 ? 'text-red-600' : 'text-gray-500'}`}>
                            <span className="font-bold">
                                {stats.fires?.growth > 0 ? `+${stats.fires.growth}%` : `${stats.fires?.growth ?? 0}%`}
                            </span>
                            <span className="ml-1 opacity-70">dari tahun lalu</span>
                        </div>
                    </div>

                </div>

                {/* Secondary Stats Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">

                    {/* Stat Card 3: Realisasi PNBP */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Realisasi PNBP</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">{formatCurrency(stats.economy.total)}</p>
                            </div>
                            <div className="p-3 bg-primary-50 rounded-lg text-primary-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                        <div className={`mt-4 flex items-center text-sm ${stats.economy.growth > 0 ? 'text-green-600' : stats.economy.growth < 0 ? 'text-red-600' : 'text-gray-500'}`}>
                            {(stats.economy.growth || 0) > 0 ? (
                                <svg className="h-4 w-4 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            ) : (stats.economy.growth || 0) < 0 ? (
                                <svg className="h-4 w-4 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
                                </svg>
                            ) : (
                                <svg className="h-4 w-4 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 12H6" />
                                </svg>
                            )}
                            <span className="font-bold">
                                {(stats.economy.growth || 0) > 0 ? `+${stats.economy.growth}%` : `${stats.economy.growth || 0}%`}
                            </span>
                            <span className="ml-1 opacity-70">dari tahun lalu</span>
                        </div>
                    </div>

                    {/* Stat Card 1: Produksi Kayu */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Produksi Kayu</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">{formatNumber(stats.wood_production.total)} <span className="text-sm font-normal text-gray-400">m3</span></p>
                            </div>
                            <div className="p-3 bg-primary-50 rounded-lg text-primary-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div className={`mt-4 flex items-center text-sm ${stats.wood_production.growth > 0 ? 'text-green-600' : stats.wood_production.growth < 0 ? 'text-red-600' : 'text-gray-500'}`}>
                            {(stats.wood_production.growth || 0) > 0 ? (
                                <svg className="h-4 w-4 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            ) : (stats.wood_production.growth || 0) < 0 ? (
                                <svg className="h-4 w-4 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
                                </svg>
                            ) : (
                                <svg className="h-4 w-4 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 12H6" />
                                </svg>
                            )}
                            <span className="font-bold">
                                {(stats.wood_production.growth || 0) > 0 ? `+${stats.wood_production.growth}%` : `${stats.wood_production.growth || 0}%`}
                            </span>
                            <span className="ml-1 opacity-70">dari tahun lalu</span>
                        </div>
                    </div>

                    {/* Stat Card: Produksi HHBK */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Produksi HHBK</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">
                                    {formatNumber(stats.produksi_hhbk?.total || 0)} <span className="text-xs font-normal text-gray-400">Vol</span>
                                </p>
                            </div>
                            <div className="p-3 bg-amber-50 rounded-lg text-amber-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                        <div className={`mt-4 flex items-center text-xs ${stats.produksi_hhbk?.growth > 0 ? 'text-green-600' : stats.produksi_hhbk?.growth < 0 ? 'text-red-600' : 'text-gray-500'}`}>
                            <span className="font-bold">
                                {stats.produksi_hhbk?.growth > 0 ? `+${stats.produksi_hhbk.growth}%` : `${stats.produksi_hhbk?.growth ?? 0}%`}
                            </span>
                            <span className="ml-1 opacity-70">dari tahun lalu</span>
                        </div>
                    </div>

                    {/* Stat Card: Jasa Lingkungan */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Jasa Lingkungan</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">{formatCurrency(stats.jasa_lingkungan?.total || 0)}</p>
                            </div>
                            <div className="p-3 bg-purple-50 rounded-lg text-purple-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div className={`mt-4 flex items-center text-xs ${stats.jasa_lingkungan?.growth > 0 ? 'text-green-600' : stats.jasa_lingkungan?.growth < 0 ? 'text-red-600' : 'text-gray-500'}`}>
                            <span className="font-bold">
                                {stats.jasa_lingkungan?.growth > 0 ? `+${stats.jasa_lingkungan.growth}%` : `${stats.jasa_lingkungan?.growth ?? 0}%`}
                            </span>
                            <span className="ml-1 opacity-70">dari tahun lalu</span>
                        </div>
                    </div>

                    {/* Stat Card: Hutan Kemasyarakatan */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Hutan Kemasyarakatan</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">
                                    {formatNumber(stats.hkm?.total || 0)} <span className="text-xs font-normal text-gray-400">Kelompok</span>
                                </p>
                            </div>
                            <div className="p-3 bg-indigo-50 rounded-lg text-indigo-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div className="mt-4 flex items-center text-xs text-indigo-600">
                            <span className="font-semibold">{stats.hkm?.percentage || 0}% dari total {stats.hkm?.skps_total || 0} SKPS</span>
                        </div>
                    </div>

                    {/* Stat Card 4: KUPS */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">KUPS</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">{formatNumber(stats.kups.total)} <span className="text-sm font-normal text-gray-400">Unit</span></p>
                            </div>
                            <div className="p-3 bg-primary-50 rounded-lg text-primary-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div className="mt-4 flex items-center text-sm text-green-600">
                            <span className="font-semibold text-green-700">Terdata & Aktif</span>
                        </div>
                    </div>

                    {/* Stat Card: Nekon */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Nekon Perhutanan Sosial</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">{formatCurrency(stats.nekon?.total || 0)}</p>
                            </div>
                            <div className="p-3 bg-emerald-50 rounded-lg text-emerald-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                        <div className={`mt-4 flex items-center text-xs ${stats.nekon?.growth > 0 ? 'text-green-600' : stats.nekon?.growth < 0 ? 'text-red-600' : 'text-gray-500'}`}>
                            <span className="font-bold">
                                {stats.nekon?.growth > 0 ? `+${stats.nekon.growth}%` : `${stats.nekon?.growth ?? 0}%`}
                            </span>
                            <span className="ml-1 opacity-70">dari tahun lalu</span>
                        </div>
                    </div>

                    {/* Stat Card: NTE */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">NTE Hutan Rakyat</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">{formatCurrency(stats.nte?.total || 0)}</p>
                            </div>
                            <div className="p-3 bg-blue-50 rounded-lg text-blue-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div className={`mt-4 flex items-center text-xs ${stats.nte?.growth > 0 ? 'text-green-600' : stats.nte?.growth < 0 ? 'text-red-600' : 'text-gray-500'}`}>
                            <span className="font-bold">
                                {stats.nte?.growth > 0 ? `+${stats.nte.growth}%` : `${stats.nte?.growth ?? 0}%`}
                            </span>
                            <span className="ml-1 opacity-70">dari tahun lalu</span>
                        </div>
                    </div>

                </div>

                {/* Recent Activity Table */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100 bg-gray-50/50">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-primary-100 rounded-lg text-primary-600">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 className="font-bold text-gray-800 text-lg">Aktivitas Terbaru</h3>
                        </div>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-gray-500 min-w-[600px] sm:min-w-0">
                            <thead className="bg-gray-50 text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-4 font-semibold tracking-wider">User</th>
                                    <th className="px-6 py-4 font-semibold tracking-wider">Aktivitas</th>
                                    <th className="px-6 py-4 font-semibold tracking-wider hidden sm:table-cell">Waktu</th>
                                    <th className="px-6 py-4 font-semibold tracking-wider">Modul</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {recentActivities && recentActivities.length > 0 ? (
                                    recentActivities.map((activity) => {
                                        // Helper for avatar initials
                                        const initials = activity.causer.split(' ').map((n) => n[0]).join('').substring(0, 2).toUpperCase();

                                        // Helper for activity icon/color
                                        let icon;
                                        let iconBg;
                                        let iconColor;

                                        if (activity.event === 'created') {
                                            iconBg = 'bg-blue-100';
                                            iconColor = 'text-blue-600';
                                            icon = (
                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4"></path></svg>
                                            );
                                        } else if (activity.event === 'updated') {
                                            iconBg = 'bg-yellow-100';
                                            iconColor = 'text-yellow-600';
                                            icon = (
                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            );
                                        } else if (activity.event === 'deleted') {
                                            iconBg = 'bg-red-100';
                                            iconColor = 'text-red-600';
                                            icon = (
                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            );
                                        } else {
                                            iconBg = 'bg-gray-100';
                                            iconColor = 'text-gray-600';
                                            icon = (
                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            );
                                        }

                                        return (
                                            <tr key={activity.id} className="hover:bg-gray-50/80 transition-colors group">
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center">
                                                        <div className="h-10 w-10 rounded-full bg-gradient-to-tr from-primary-600 to-primary-400 flex items-center justify-center text-white font-bold text-xs shadow-sm mr-3">
                                                            {initials}
                                                        </div>
                                                        <div>
                                                            <div className="font-semibold text-gray-900">{activity.causer}</div>
                                                            <div className="text-xs text-gray-400 capitalize">{activity.role}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex items-start gap-3">
                                                        <div className={`p-1.5 rounded-full ${iconBg} ${iconColor} shrink-0 mt-0.5`}>
                                                            {icon}
                                                        </div>
                                                        <div>
                                                            <div className="font-medium text-gray-800">{activity.description}</div>
                                                            <div className="text-xs text-gray-500 mt-0.5 max-w-[200px] truncate">{activity.subject_type}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 hidden sm:table-cell">
                                                    <div className="flex items-center text-gray-500 text-sm">
                                                        <svg className="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        {activity.created_at}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`px-3 py-1 text-xs font-semibold rounded-full border ${activity.subject_type === 'HasilHutanKayu' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-green-50 text-green-700 border-green-200'}`}>
                                                        {activity.subject_type}
                                                    </span>
                                                </td>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr className="hover:bg-gray-50">
                                        <td colSpan="4" className="text-center py-12">
                                            <div className="flex flex-col items-center">
                                                <div className="p-4 bg-gray-50 rounded-full mb-3 text-gray-300">
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                                    </svg>
                                                </div>
                                                <p className="text-gray-400 font-medium tracking-tight whitespace-nowrap">Belum ada data aktivitas tersedia</p>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </AuthenticatedLayout>
    );
}
