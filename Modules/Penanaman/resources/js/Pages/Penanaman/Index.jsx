import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ExportFilterModal from '@/Components/ExportFilterModal';
import Modal from '@/Components/Modal';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CircleAlert, Download, FilePlus2, Filter, RotateCcw, Search, Send, Trash2, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { FlashMessage, heroButton, heroSecondaryButton, PageHero, StatusBadge } from '../../Components/PenanamanUi';
import RegencySelect from '../../Components/RegencySelect';
import useSubmitPenanaman from '../../Components/useSubmitPenanaman';
import useDeletePenanaman from '../../Components/useDeletePenanaman';

const area = (value) => new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0));
const filterField = 'block w-full rounded-xl border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:border-primary-600 focus:bg-white focus:ring-primary-600';
const filterLabel = 'block text-[11px] font-bold uppercase tracking-wider text-gray-500';

export default function Index({ auth, reports, stats, regencies, filters, isAdmin, targetMissing, canExport, canImport, canDelete, availableYears, exportFundSources, exportColumns }) {
    const { flash } = usePage().props;
    const { submit, processing: submitting } = useSubmitPenanaman();
    const { remove, processing: deleting } = useDeletePenanaman();
    const [query, setQuery] = useState({
        year: filters.year || new Date().getFullYear(),
        month: filters.month || '',
        regency_id: filters.regency_id || '',
        status: filters.status || '',
        search: filters.search || '',
    });
    const currentYear = new Date().getFullYear();
    const [advancedOpen, setAdvancedOpen] = useState(Boolean(
        filters.month || filters.regency_id || filters.status ||
        (filters.year && String(filters.year) !== String(currentYear))
    ));
    const advancedCount = Number(String(query.year) !== String(currentYear))
        + Number(Boolean(query.month))
        + Number(Boolean(isAdmin && query.regency_id))
        + Number(Boolean(query.status));
    const firstRender = useRef(true);
    const [showExportModal, setShowExportModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importError, setImportError] = useState('');
    const [importing, setImporting] = useState(false);
    const change = (key, value) => setQuery((current) => ({ ...current, [key]: value }));
    const resetFilters = () => setQuery({
        year: new Date().getFullYear(),
        month: '',
        regency_id: '',
        status: '',
        search: '',
    });
    const previewImport = (event) => {
        event.preventDefault();
        if (!importFile || importing) return;
        setImporting(true);
        setImportError('');
        router.post(route('penanaman.preview-import'), { file: importFile }, {
            forceFormData: true,
            onError: (errors) => setImportError(errors.file || 'File gagal diproses.'),
            onFinish: () => setImporting(false),
        });
    };

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        const delay = query.search !== (filters.search || '') ? 450 : 250;
        const timer = window.setTimeout(() => {
            router.get(route('penanaman.index'), query, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, delay);
        return () => window.clearTimeout(timer);
    }, [query]);
    const summary = [
        ['Target luas', area(stats.target_ha), 'Ha', 'Target tahunan'],
        ['Realisasi luas', area(stats.realization_ha), 'Ha', 'Laporan final'],
    ];

    return <AuthenticatedLayout user={auth.user} header={<h2 className="text-xl font-semibold text-gray-800">Data Penanaman</h2>}>
        <Head title="Data Penanaman" />
        <div className="mx-auto max-w-7xl space-y-6">
            <PageHero title="Data Penanaman" description="Laporan penanaman kabupaten dan kota di Jawa Timur."
                actions={<>
                    <Link href={route('penanaman.targets.index', { year: filters.year || query.year })}
                        className={targetMissing
                            ? 'inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-red-700 bg-red-700 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-red-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white'
                            : heroSecondaryButton}
                        aria-label={targetMissing ? 'Target tahunan belum lengkap' : 'Target tahunan'}
                        title={targetMissing ? 'Masih ada target tahunan yang belum diisi' : undefined}>
                        {targetMissing && <CircleAlert className="h-4 w-4" aria-hidden="true" />}
                        Target tahunan
                    </Link>
                    {isAdmin && <Link href={route('penanaman.accounts.index')} className={heroSecondaryButton}>Akun wilayah</Link>}
                    {canExport && <button type="button" onClick={() => setShowExportModal(true)} className={heroSecondaryButton}><Download className="mr-2 h-4 w-4" aria-hidden="true" />Export</button>}
                    {canImport && <button type="button" onClick={() => { setImportError(''); setShowImportModal(true); }} className={heroSecondaryButton}><Upload className="mr-2 h-4 w-4" aria-hidden="true" />Import</button>}
                    <Link href={route('penanaman.create')} className={`${heroButton} gap-2`}><FilePlus2 className="h-4 w-4 shrink-0" aria-hidden="true" />Tambah laporan</Link>
                </>} />
            <FlashMessage success={flash?.success} error={flash?.error} />

            <section aria-label="Ringkasan tahun terpilih" className="grid gap-4 sm:grid-cols-2">
                {summary.map(([label, value, unit, note]) => <div key={label} className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <p className="text-sm font-medium text-gray-600">{label}</p>
                    <p className="mt-3 font-display text-2xl font-bold text-primary-800">{value} <span className="font-sans text-sm font-semibold text-gray-600">{unit}</span></p>
                    <p className="mt-2 text-xs text-gray-500">{note} · {filters.year || query.year}</p>
                </div>)}
            </section>

            <section className="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm" aria-label="Laporan penanaman">
                <div className="flex flex-col gap-4 border-b border-gray-100 p-5 sm:p-6 md:flex-row md:items-center md:justify-between">
                    <div className="flex items-center gap-3">
                        <h2 className="font-display text-lg font-bold text-gray-900">Laporan penanaman</h2>
                        <span className="rounded-full border border-gray-100 bg-gray-50 px-3 py-1 text-xs font-bold text-gray-500">{reports.total} laporan</span>
                    </div>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center" aria-label="Filter laporan">
                        <div className="relative w-full sm:w-64">
                            <label htmlFor="filter-search" className="sr-only">Cari laporan</label>
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                            <input id="filter-search" type="search" value={query.search} onChange={(e) => change('search', e.target.value)}
                                placeholder="Cari lokasi atau sumber dana..." className={`${filterField} pl-10`} />
                        </div>
                        <button type="button" aria-expanded={advancedOpen} aria-controls="penanaman-filter-lanjutan"
                            onClick={() => setAdvancedOpen((open) => !open)}
                            className={`inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-bold transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 ${advancedOpen ? 'border-primary-100 bg-primary-50 text-primary-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'}`}>
                            <Filter className="h-4 w-4" aria-hidden="true" />
                            Filter lainnya
                            {advancedCount > 0 && <span className="rounded-full bg-primary-100 px-2 py-0.5 text-xs text-primary-800">{advancedCount}</span>}
                        </button>
                    </div>
                </div>
                {advancedOpen && <div id="penanaman-filter-lanjutan" className="border-b border-gray-100 bg-gray-50/50 px-5 py-5 sm:px-6">
                    <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label htmlFor="filter-year" className={filterLabel}>Tahun</label>
                            <input id="filter-year" type="number" min="2000" max="2100" value={query.year} onChange={(e) => change('year', e.target.value)} className={`${filterField} mt-2 bg-white`} />
                        </div>
                        <div>
                            <label htmlFor="filter-month" className={filterLabel}>Bulan</label>
                            <select id="filter-month" value={query.month} onChange={(e) => change('month', e.target.value)} className={`${filterField} mt-2 bg-white`}>
                                <option value="">Semua bulan</option>
                                {Array.from({ length: 12 }, (_, i) => <option key={i + 1} value={i + 1}>{new Date(2020, i).toLocaleString('id-ID', { month: 'long' })}</option>)}
                            </select>
                        </div>
                        {isAdmin && <div>
                            <label htmlFor="filter-regency" className={filterLabel}>Kabupaten/Kota</label>
                            <RegencySelect id="filter-regency" regencies={regencies} value={query.regency_id} onChange={(value) => change('regency_id', value)} placeholder="Semua wilayah" />
                        </div>}
                        <div>
                            <label htmlFor="filter-status" className={filterLabel}>Status</label>
                            <select id="filter-status" value={query.status} onChange={(e) => change('status', e.target.value)} className={`${filterField} mt-2 bg-white`}>
                                <option value="">Semua status</option><option value="draft">Draft</option><option value="final">Final</option>
                            </select>
                        </div>
                    </div>
                    <div className="mt-5 flex justify-end">
                        <button type="button" onClick={resetFilters} className="inline-flex min-h-10 items-center gap-2 rounded-xl px-3 text-sm font-bold text-red-700 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                            <RotateCcw className="h-4 w-4" aria-hidden="true" /> Reset filter
                        </button>
                    </div>
                </div>}
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-100 text-sm">
                        <thead className="bg-gray-50/70 text-left text-xs font-bold uppercase tracking-wide text-gray-600"><tr><th scope="col" className="px-5 py-4 sm:px-7">Periode</th><th scope="col" className="px-5 py-4">Wilayah dan lokasi</th><th scope="col" className="px-5 py-4">Sumber dana</th><th scope="col" className="px-5 py-4">Realisasi</th><th scope="col" className="px-5 py-4">Status</th><th scope="col" className="px-5 py-4">Aksi</th></tr></thead>
                        <tbody className="divide-y divide-gray-100">
                            {reports.data.map((item) => <tr key={item.id} className="hover:bg-primary-50/30">
                                <td className="whitespace-nowrap px-5 py-4 font-semibold text-gray-700 sm:px-7">{item.month ? `${item.month}/` : ''}{item.year}</td>
                                <td className="min-w-48 px-5 py-4"><span className="font-bold text-gray-900">{item.location_name || 'Lokasi belum diisi'}</span><span className="mt-1 block text-gray-600">{item.regency?.name}</span></td>
                                <td className="px-5 py-4 text-gray-700">{item.fund_source || '-'}</td>
                                <td className="whitespace-nowrap px-5 py-4 text-gray-700">{area(item.realization_ha)} Ha <span className="text-gray-300">·</span></td>
                                <td className="px-5 py-4"><StatusBadge status={item.status} /></td>
                                <td className="px-5 py-4">
                                    <div className="flex items-center gap-3">
                                        <Link href={route('penanaman.show', item.id)} className="whitespace-nowrap font-bold text-primary-700 hover:text-primary-900 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-700">Lihat detail</Link>
                                        {item.status === 'draft' && <button type="button" onClick={() => submit(item.id)} disabled={submitting}
                                            className="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 font-bold text-blue-700 hover:bg-blue-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700 disabled:opacity-60">
                                            <Send className="h-4 w-4" aria-hidden="true" /> Ajukan
                                        </button>}
                                        {canDelete && item.status === 'draft' && <button type="button" onClick={() => remove(item)} disabled={deleting}
                                            className="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-2 text-sm font-bold text-red-700 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-700 disabled:opacity-60"
                                            aria-label={`Hapus draft ${item.location_name || 'tanpa nama lokasi'}`}>
                                            <Trash2 className="h-4 w-4" aria-hidden="true" /> Hapus
                                        </button>}
                                    </div>
                                </td>
                            </tr>)}
                        </tbody>
                    </table>
                </div>
                {reports.data.length === 0 && <div className="px-5 py-12 text-center"><p className="font-display text-lg font-bold text-gray-800">Belum ada laporan</p><p className="mt-1 text-sm text-gray-600">Coba ubah filter atau mulai buat laporan penanaman.</p></div>}
                {reports.links.length > 3 && <nav aria-label="Halaman laporan" className="flex flex-wrap gap-2 border-t border-gray-100 px-5 py-4 sm:px-7">{reports.links.map((link, index) => {
                    const label = index === 0 ? 'Sebelumnya' : index === reports.links.length - 1 ? 'Berikutnya' : link.label;
                    return link.url
                        ? <Link key={index} href={link.url} className={`rounded-lg px-3 py-2 text-sm font-semibold ${link.active ? 'bg-primary-700 text-white' : 'border border-gray-200 text-gray-700 hover:bg-gray-50'}`}>{label}</Link>
                        : <span key={index} className="px-3 py-2 text-sm text-gray-500">{label}</span>;
                })}</nav>}
            </section>
        </div>
        {canExport && <ExportFilterModal
            show={showExportModal}
            onClose={() => setShowExportModal(false)}
            onExport={(exportFilters) => { window.location.href = route('penanaman.export', exportFilters); }}
            year={filters.year || currentYear}
            years={availableYears}
            defaultStatus="all"
            statusChoices={[[ 'all', 'Semua status' ], [ 'draft', 'Draft' ], [ 'final', 'Final' ]]}
            statusLabel="Status"
            fundSources={exportFundSources}
            exportColumns={exportColumns}
            showLocation={isAdmin}
            showDistrict={false}
        />}
        {canImport && <Modal show={showImportModal} onClose={() => setShowImportModal(false)} maxWidth="lg">
            <form onSubmit={previewImport} className="p-6">
                <h2 className="font-display text-xl font-bold text-gray-900">Import data penanaman</h2>
                <p className="mt-2 text-sm text-gray-600">Unggah file sesuai template. Data akan ditinjau sebelum disimpan sebagai draft.</p>
                <a href={route('penanaman.import-template')} className="mt-4 inline-flex items-center gap-2 text-sm font-bold text-primary-700 hover:underline">
                    <Download className="h-4 w-4" aria-hidden="true" /> Unduh template Excel
                </a>
                <label htmlFor="penanaman-import-file" className="mt-6 block text-sm font-semibold text-gray-700">File Excel atau CSV</label>
                <input id="penanaman-import-file" type="file" accept=".xlsx,.xls,.csv" required
                    onChange={(event) => { setImportFile(event.target.files?.[0] || null); setImportError(''); }}
                    className="mt-2 block w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:font-bold file:text-primary-800" />
                <p className="mt-2 text-xs text-gray-500">Maksimal 10 MB. Baris yang tidak valid akan ditandai pada pratinjau.</p>
                {importError && <p role="alert" className="mt-3 text-sm font-semibold text-red-700">{importError}</p>}
                <div className="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-5">
                    <button type="button" onClick={() => setShowImportModal(false)} className="min-h-11 rounded-xl border border-gray-200 px-5 text-sm font-bold text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit" disabled={!importFile || importing} className="min-h-11 rounded-xl bg-primary-700 px-5 text-sm font-bold text-white hover:bg-primary-800 disabled:opacity-60">
                        {importing ? 'Memproses...' : 'Lihat pratinjau'}
                    </button>
                </div>
            </form>
        </Modal>}
    </AuthenticatedLayout>;
}
