import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { BackLink, FieldError, fieldClass, FlashMessage, PageHero, SectionCard, StatusBadge } from '../../Components/PenanamanUi';
import useSubmitPenanaman from '../../Components/useSubmitPenanaman';
import useDeletePenanaman from '../../Components/useDeletePenanaman';

const formatArea = (value) => new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0));
const formatTime = (value) => value ? new Date(value).toLocaleString('id-ID') : '-';

export default function Show({ auth, report, isAdmin, canDelete }) {
    const { flash } = usePage().props;
    const { submit, processing: submitting } = useSubmitPenanaman();
    const { remove, processing: deleting } = useDeletePenanaman();
    const returning = useForm({ alasan: '' });
    const returnToDraft = (event) => { event.preventDefault(); returning.post(route('penanaman.return', report.id)); };
    const facts = [
        ['Kecamatan', report.district?.name || '-'],
        ['Desa/Kelurahan', report.village?.name || '-'],
        ['Koordinat', report.coordinates || '-'],
        ['Diinput oleh', report.creator?.name || '-'],
        ['Pengajuan terakhir', formatTime(report.submitted_at)],
    ];

    return <AuthenticatedLayout user={auth.user} header={<h2 className="text-xl font-semibold text-gray-800">Detail Penanaman</h2>}>
        <Head title="Detail Penanaman" />
        <div className="mx-auto max-w-5xl space-y-5">
            <BackLink href={route('penanaman.index')} />
            <PageHero eyebrow={report.regency?.name} title={report.location_name || 'Lokasi belum diisi'} description={`Laporan ${report.month ? `${report.month}/` : ''}${report.year} · ${report.status === 'final' ? 'Selesai diajukan' : 'Masih berupa draft'}`}
                actions={report.status === 'draft' && <>
                    <Link href={route('penanaman.edit', report.id)} className="inline-flex min-h-11 items-center rounded-xl border border-white/40 px-5 py-2.5 text-sm font-bold text-white hover:bg-white/10">Ubah Draft</Link>
                    <button type="button" onClick={() => submit(report.id)} disabled={submitting} className="inline-flex min-h-11 items-center rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-primary-800 hover:bg-primary-50 disabled:opacity-60">
                        {submitting ? 'Mengajukan...' : 'Ajukan laporan'}
                    </button>
                    {canDelete && <button type="button" onClick={() => remove(report)} disabled={deleting}
                        className="inline-flex min-h-11 items-center rounded-xl border border-white/40 px-5 py-2.5 text-sm font-bold text-white hover:bg-white/10 disabled:opacity-60">
                        {deleting ? 'Menghapus...' : 'Hapus Draft'}
                    </button>}
                </>} />
            <FlashMessage success={flash?.success} error={flash?.error} />

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"><p className="text-sm text-gray-600">Status laporan</p><div className="mt-3"><StatusBadge status={report.status} /></div></div>
                <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"><p className="text-sm text-gray-600">Realisasi luas</p><p className="mt-2 font-display text-2xl font-bold text-primary-800">{formatArea(report.realization_ha)} <span className="text-sm">Ha</span></p></div>
                <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"><p className="text-sm text-gray-600">Sumber dana</p><p className="mt-2 font-display text-xl font-bold text-primary-800">{report.fund_source || 'Belum diisi'}</p></div>
            </div>

            <SectionCard title="Informasi lokasi" description="Rincian wilayah dan waktu laporan penanaman.">
                <dl className="grid gap-5 p-5 text-sm sm:grid-cols-2 sm:p-7">
                    {facts.map(([label, value]) => <div key={label}><dt className="font-medium text-gray-600">{label}</dt><dd className="mt-1 font-semibold text-gray-900">{value}</dd></div>)}
                </dl>
            </SectionCard>
            {isAdmin && report.status === 'final' && <SectionCard title="Kembalikan untuk koreksi" description="Operator dan admin dapat memperbaiki draft, lalu mengajukannya kembali.">
                <form onSubmit={returnToDraft} className="p-5 sm:p-7">
                    <label className="block text-sm font-semibold text-gray-700">Alasan pengembalian<textarea value={returning.data.alasan} onChange={(e) => returning.setData('alasan', e.target.value)} rows="3" maxLength="1000" required className={fieldClass} /></label>
                    <FieldError message={returning.errors.alasan} />
                    <button disabled={returning.processing} className="mt-4 inline-flex min-h-11 items-center rounded-xl border border-amber-500 px-5 py-2.5 text-sm font-bold text-amber-900 hover:bg-amber-50 disabled:opacity-60">Kembalikan ke Draft</button>
                </form>
            </SectionCard>}

            <SectionCard title="Riwayat pengajuan" description="Semua pengajuan dan pengembalian tersimpan di sini.">
                {report.events.length ? <ol className="space-y-5 p-5 sm:p-7">{report.events.map((event) => <li key={event.id} className="border-l-2 border-primary-600 pl-4 text-sm"><p className="font-bold text-gray-900">{event.action === 'submitted' ? 'Diajukan menjadi final' : 'Dikembalikan ke draft'}</p><p className="mt-1 text-gray-600">{event.actor?.name || 'Pengguna'} · {formatTime(event.created_at)}</p><p className="mt-2 whitespace-pre-wrap text-gray-800">{event.note}</p></li>)}</ol> : <p className="p-7 text-sm text-gray-600">Belum ada pengajuan.</p>}
            </SectionCard>
        </div>
    </AuthenticatedLayout>;
}
