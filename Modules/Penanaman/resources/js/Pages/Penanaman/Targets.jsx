import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { BackLink, FieldError, fieldClass, FlashMessage, PageHero, primaryButton, SectionCard } from '../../Components/PenanamanUi';
import { useState } from 'react';

function TargetRow({ region, target, year }) {
    const { data, setData, put, processing, errors } = useForm({ target_ha: target?.target_ha ?? '' });
    const save = (event) => { event.preventDefault(); put(route('penanaman.targets.update', { regency: region.id, year }), { preserveScroll: true }); };
    return <form onSubmit={save} className="grid gap-4 border-b border-gray-100 px-5 py-5 last:border-b-0 sm:px-7 lg:grid-cols-[minmax(12rem,1fr)_10rem_auto] lg:items-end">
        <div className="self-center"><p className="font-bold text-gray-900">{region.name}</p><p className="mt-1 text-xs text-gray-500">Target tahun {year}</p></div>
        <label className="text-sm font-semibold text-gray-700">Target luas (Ha)<input type="number" step="0.01" min="0" value={data.target_ha} onChange={(e) => setData('target_ha', e.target.value)} required className={fieldClass} /><FieldError message={errors.target_ha} /></label>
        <button disabled={processing} className={primaryButton}>{processing ? 'Menyimpan...' : 'Simpan'}</button>
    </form>;
}

export default function Targets({ auth, year, regencies, targets, isAdmin }) {
    const { flash } = usePage().props;
    const [selectedYear, setSelectedYear] = useState(year);
    const showYear = (event) => { event.preventDefault(); router.get(route('penanaman.targets.index'), { year: selectedYear }); };
    return <AuthenticatedLayout user={auth.user} header={<h2 className="text-xl font-semibold text-gray-800">Target Penanaman</h2>}>
        <Head title="Target Tahunan Penanaman" />
        <div className="mx-auto max-w-5xl space-y-5">
            <BackLink href={route('penanaman.index')} children="Kembali ke laporan" />
            <PageHero eyebrow="Data Penanaman" title="Target tahunan" description="Satu target luas untuk setiap kabupaten/kota per tahun." />
            <FlashMessage success={flash?.success} error={flash?.error} />
            <SectionCard title={`Target tahun ${year}`} description="Pilih tahun, lalu simpan target pada masing-masing wilayah.">
                <form onSubmit={showYear} className="flex flex-wrap items-end gap-3 border-b border-gray-100 px-5 py-5 sm:px-7">
                    <label className="text-sm font-semibold text-gray-700">Tahun<input type="number" min="2000" max="2100" value={selectedYear} onChange={(e) => setSelectedYear(e.target.value)} className={`${fieldClass} w-32`} /></label>
                    <button className="min-h-11 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">Tampilkan</button>
                </form>
                {regencies.map((region) => <TargetRow key={`${year}-${region.id}`} region={region} target={targets[region.id]} year={year} />)}
                {regencies.length === 0 && <p className="p-7 text-sm text-gray-600">Tidak ada wilayah yang dapat dikelola.</p>}
            </SectionCard>
            {isAdmin && <p className="text-sm text-gray-600">Target tiap wilayah dihitung sekali pada rekap provinsi, meski memiliki banyak laporan.</p>}
        </div>
    </AuthenticatedLayout>;
}
