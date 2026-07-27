import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link, router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { useState, useCallback, useEffect } from 'react';
import { debounce } from 'lodash';
import LoadingOverlay from '@/Components/LoadingOverlay';

const MySwal = withReactContent(Swal);

export default function Index({ auth, users, filters }) {
    const [isLoading, setIsLoading] = useState(false);
    const [loadingText, setLoadingText] = useState('Memproses...');
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [roleFilter, setRoleFilter] = useState(filters.role_filter || 'with_role');
    const [isImportModalOpen, setIsImportModalOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);

    const isAdmin = auth.user.roles.includes('admin');
    const userPermissions = auth.user.permissions || [];
    const canCreate = userPermissions.includes('users.create') || isAdmin;
    const canEdit = userPermissions.includes('users.edit') || isAdmin;
    const canDelete = userPermissions.includes('users.delete') || isAdmin;

    // Unified filter handler
    const performQuery = (query, filter) => {
        router.get(
            route('users.index'),
            { search: query, role_filter: filter },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const debouncedSearch = useCallback(
        debounce((query, filter) => {
            performQuery(query, filter);
        }, 500),
        []
    );

    const onSearchChange = (e) => {
        const query = e.target.value;
        setSearchQuery(query);
        debouncedSearch(query, roleFilter);
    };

    const onRoleFilterChange = (filter) => {
        setRoleFilter(filter);
        performQuery(searchQuery, filter);
    };

    const handleDelete = (id) => {
        MySwal.fire({
            title: 'Apakah Anda yakin?',
            text: "User yang dihapus akan tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#15803d',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal',
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
                setLoadingText('Menghapus User...');
                setIsLoading(true);
                router.delete(route('users.destroy', id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        setIsLoading(false);
                        MySwal.fire({
                            title: 'Terhapus!',
                            text: 'User telah berhasil dihapus.',
                            icon: 'success',
                            confirmButtonColor: '#15803d',
                            timer: 2000,
                            timerProgressBar: true,
                            showConfirmButton: false,
                        });
                    },
                    onError: () => setIsLoading(false),
                    onFinish: () => setIsLoading(false)
                });
            }
        });
    };

    const handleImpersonate = (userId, userName) => {
        MySwal.fire({
            title: 'Konfirmasi Login',
            text: `Anda akan login sebagai ${userName}. Lanjutkan?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Login',
            cancelButtonText: 'Batal',
            customClass: {
                title: 'font-bold text-gray-900',
                popup: 'rounded-3xl shadow-2xl border-none',
                confirmButton: 'rounded-xl font-bold px-6 py-2.5',
                cancelButton: 'rounded-xl font-bold px-6 py-2.5'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = route('impersonate', userId);
            }
        });
    };

    const handleExport = () => {
        window.location.href = route('users.export', { search: searchQuery, role_filter: roleFilter });
    };

    const handleDownloadTemplate = () => {
        window.location.href = route('users.template');
    };

    const handleImport = (e) => {
        e.preventDefault();
        if (!importFile) {
            MySwal.fire('Error', 'Silakan pilih file terlebih dahulu.', 'error');
            return;
        }

        setLoadingText('Mengimport Data...');
        setIsLoading(true);
        setIsImportModalOpen(false);

        router.post(route('users.import'), {
            file: importFile,
        }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setIsLoading(false);
                setImportFile(null);
                MySwal.fire('Berhasil!', 'Data user berhasil diimport.', 'success');
            },
            onError: (errors) => {
                setIsLoading(false);
                MySwal.fire('Gagal!', 'Terjadi kesalahan saat import data.', 'error');
            },
            onFinish: () => setIsLoading(false)
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Manajemen User</h2>}
        >
            <Head title="Manajemen User" />

            {/* Fixed Loading Overlay */}
            <LoadingOverlay isLoading={isLoading} text={loadingText} />

            <div className={`space-y-6 transition-all duration-700 ease-in-out ${isLoading ? 'opacity-30 blur-md grayscale-[0.5] pointer-events-none' : 'opacity-100 blur-0'}`}>
                {/* Header Section */}
                <div className="bg-gradient-to-r from-primary-800 to-primary-600 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
                    <div className="absolute right-0 top-0 h-full w-1/3 bg-white/5 transform skew-x-12 shrink-0"></div>
                    <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 className="text-2xl font-bold font-display">Data Pengguna</h3>
                            <p className="mt-1 text-primary-100 opacity-90 max-w-xl text-sm">
                                Kelola data pengguna aplikasi, termasuk peran dan hak akses.
                            </p>
                        </div>
                        <div className="flex gap-2 shrink-0">
                            <button onClick={handleExport} className="flex items-center gap-2 px-4 py-3 bg-emerald-700 text-emerald-100 rounded-xl font-bold text-sm shadow-sm hover:bg-emerald-800 transition-colors border border-emerald-600/50" title="Export Data User">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Export
                            </button>
                            <button onClick={() => setIsImportModalOpen(true)} className="flex items-center gap-2 px-4 py-3 bg-emerald-700 text-emerald-100 rounded-xl font-bold text-sm shadow-sm hover:bg-emerald-800 transition-colors border border-emerald-600/50" title="Import Data User">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Import
                            </button>
                            {canCreate && (
                                <Link href={route('users.create')}>
                                    <button className="flex items-center gap-2 px-5 py-3 bg-white text-primary-700 rounded-xl font-bold text-sm shadow-sm hover:bg-primary-50 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                        </svg>
                                        Tambah Data
                                    </button>
                                </Link>
                            )}
                        </div>
                    </div>
                </div>

                {/* Table Section */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                        <div className="flex flex-wrap items-center gap-4">
                            <h3 className="font-bold text-gray-800">Daftar User</h3>
                            <div className="h-6 w-px bg-gray-200 hidden md:block"></div>

                            {/* Role Filter Tabs */}
                            <div className="flex bg-gray-50 p-1 rounded-xl border border-gray-100">
                                <button
                                    onClick={() => onRoleFilterChange('with_role')}
                                    className={`px-4 py-1.5 rounded-lg text-xs font-bold transition-all ${roleFilter === 'with_role'
                                        ? 'bg-white text-primary-700 shadow-sm border border-gray-100'
                                        : 'text-gray-400 hover:text-gray-600'
                                        }`}
                                >
                                    Punya Role
                                </button>
                                {isAdmin && (
                                    <>
                                        <button
                                            onClick={() => onRoleFilterChange('without_role')}
                                            className={`px-4 py-1.5 rounded-lg text-xs font-bold transition-all ${roleFilter === 'without_role'
                                                ? 'bg-white text-primary-700 shadow-sm border border-gray-100'
                                                : 'text-gray-400 hover:text-gray-600'
                                                }`}
                                        >
                                            Tanpa Role
                                        </button>
                                        <button
                                            onClick={() => onRoleFilterChange('all')}
                                            className={`px-4 py-1.5 rounded-lg text-xs font-bold transition-all ${roleFilter === 'all'
                                                ? 'bg-white text-primary-700 shadow-sm border border-gray-100'
                                                : 'text-gray-400 hover:text-gray-600'
                                                }`}
                                        >
                                            Semua
                                        </button>
                                    </>
                                )}
                            </div>

                            <div className="text-sm text-gray-400 font-bold bg-white px-3 py-1 rounded-full border border-gray-100 shadow-sm">
                                {users.total} User
                            </div>
                        </div>

                        {/* Search Input */}
                        <div className="relative w-full md:w-64">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg className="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                className="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all"
                                placeholder="Cari nama, email..."
                                value={searchQuery}
                                onChange={onSearchChange}
                            />
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-100 bg-gray-50/50">
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider">User Info</th>
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Username</th>
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Role & Hak Akses</th>
                                    {isAdmin && (
                                        <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider">CDK</th>
                                    )}
                                    <th className="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {users.data.map((user) => (
                                    <tr key={user.id} className="group hover:bg-gray-50/80 transition-all duration-200">
                                        <td className="px-6 py-5">
                                            <div className="flex items-center gap-4">
                                                <div className="relative">
                                                    <div className="h-11 w-11 rounded-xl bg-gradient-to-br from-primary-600 to-primary-700 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-primary-900/10 ring-2 ring-white group-hover:scale-105 transition-transform duration-300">
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="absolute -bottom-1 -right-1 h-4 w-4 bg-green-500 rounded-full border-2 border-white shadow-sm"></div>
                                                </div>
                                                <div className="flex flex-col">
                                                    <span className="font-bold text-gray-900 text-sm group-hover:text-primary-700 transition-colors">{user.name}</span>
                                                    <span className="text-xs text-gray-500 font-medium">{user.email}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-5">
                                            <div className="inline-flex items-center px-3 py-1 rounded-lg bg-gray-50 border border-gray-100 text-gray-600 font-mono text-xs font-medium">
                                                {user.username}
                                            </div>
                                        </td>
                                        <td className="px-6 py-5">
                                            {user.roles.length > 0 ? user.roles.map((role, index) => {
                                                let badgeClass = "bg-gray-100 text-gray-600 border-gray-200";
                                                if (role.name === 'admin' || role.name === 'super-admin') {
                                                    badgeClass = "bg-rose-50 text-rose-600 border-rose-100";
                                                } else if (role.name === 'viewer' || role.name === 'guest') {
                                                    badgeClass = "bg-blue-50 text-blue-600 border-blue-100";
                                                } else {
                                                    badgeClass = "bg-emerald-50 text-emerald-600 border-emerald-100";
                                                }

                                                return (
                                                    <span key={index} className={`inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide border ${badgeClass}`}>
                                                        {role.description}
                                                    </span>
                                                );
                                            }) : (
                                                <span className="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide border bg-gray-50 text-gray-400 border-gray-100 italic">
                                                    Tanpa Role
                                                </span>
                                            )}
                                        </td>
                                        {isAdmin && (
                                            <td className="px-6 py-5">
                                                {user.cdk ? (
                                                    <span className="inline-flex items-center px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-600 font-bold text-xs tracking-wide">
                                                        {user.cdk.nama}
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center px-3 py-1 rounded-lg bg-gray-50 border border-gray-100 text-gray-400 font-bold text-xs tracking-wide italic">
                                                        Provinsi
                                                    </span>
                                                )}
                                            </td>
                                        )}
                                        <td className="px-6 py-5">
                                            <div className="flex items-center justify-center gap-2">
                                                <Link
                                                    href={route('users.edit', user.id)}
                                                    className="p-2 bg-white text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all duration-200 border border-transparent hover:border-primary-100 shadow-sm hover:shadow-md"
                                                    title="Edit User"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </Link>
                                                {!(user.roles?.some(role => role.name === 'admin') && !isAdmin) && (
                                                    <button
                                                        onClick={() => handleDelete(user.id)}
                                                        className="p-2 bg-white text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200 border border-transparent hover:border-red-100 shadow-sm hover:shadow-md"
                                                        title="Hapus User"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                )}

                                                {isAdmin && auth.user.id !== user.id && (
                                                    <button
                                                        onClick={() => handleImpersonate(user.id, user.name)}
                                                        className="p-2 bg-white text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all duration-200 border border-transparent hover:border-amber-100 shadow-sm hover:shadow-md"
                                                        title="Login Sebagai User Ini"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                                        </svg>
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {users.data.length === 0 && (
                                    <tr>
                                        <td colSpan={isAdmin ? "5" : "4"} className="text-center py-20">
                                            <div className="flex flex-col items-center justify-center">
                                                <div className="h-24 w-24 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                </div>
                                                <h3 className="text-lg font-bold text-gray-900">Belum ada user</h3>
                                                <p className="text-gray-500 text-sm mt-1">Silakan tambahkan user baru untuk memulai.</p>
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
                                Menampilkan <span className="font-bold text-gray-700">{users.from || 0}</span> sampai <span className="font-bold text-gray-700">{users.to || 0}</span> dari <span className="font-bold text-gray-700">{users.total}</span> data
                            </div>
                            <div className="flex items-center gap-1">
                                {users.links.map((link, key) => (
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

            <Modal show={isImportModalOpen} onClose={() => setIsImportModalOpen(false)}>
                <form onSubmit={handleImport} className="p-0 overflow-hidden">
                    <div className="p-6 bg-slate-50 border-b border-gray-100 flex items-center justify-between">
                        <h2 className="text-lg font-bold text-gray-900">Import Data</h2>
                        <button type="button" onClick={() => setIsImportModalOpen(false)} className="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div className="p-6 space-y-8">
                        <div className="flex gap-4 items-start">
                            <div className="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">1</div>
                            <div className="flex-1">
                                <h3 className="text-sm font-bold text-gray-900 mb-1">Unduh Template</h3>
                                <p className="text-xs text-gray-500 mb-3 leading-relaxed">Gunakan template yang telah disediakan untuk memastikan format data sesuai.</p>
                                <button type="button" onClick={handleDownloadTemplate} className="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 text-xs font-bold rounded-lg border border-blue-100 hover:bg-blue-100 transition-colors">
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
                                            <p className="text-sm font-medium text-gray-500">Klik untuk pilih file atau drag & drop</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                        <SecondaryButton onClick={() => setIsImportModalOpen(false)}>Batal</SecondaryButton>
                        <button type="submit" className="px-6 py-2 bg-primary-600 text-white font-bold rounded-xl hover:bg-primary-700 transition-all shadow-md shadow-primary-200 disabled:opacity-50 disabled:shadow-none" disabled={!importFile}>Proses Import</button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
