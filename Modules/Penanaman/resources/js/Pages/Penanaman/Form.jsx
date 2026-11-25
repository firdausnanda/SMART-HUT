import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import axios from 'axios';
import Select from 'react-select';
import { useEffect, useState } from 'react';
import { BackLink, FieldError, fieldClass, PageHero, primaryButton, secondaryButton, SectionCard } from '../../Components/PenanamanUi';
import RegencySelect, { selectStyles } from '../../Components/RegencySelect';

export default function Form({ auth, report, regencies, sumberDana, target: initialTarget, isAdmin }) {
    const editing = Boolean(report);
    const { data, setData, post, put, processing, errors } = useForm({
        year: report?.year || new Date().getFullYear(),
        month: report?.month || new Date().getMonth() + 1,
        regency_id: report?.regency_id || (!isAdmin ? regencies[0]?.id || '' : ''),
        district_id: report?.district_id || '',
        village_id: report?.village_id || '',
        location_name: report?.location_name || '',
        coordinates: report?.coordinates || '',
        realization_ha: report?.realization_ha ?? '',
        fund_source: report?.fund_source || '',
    });
    const [districts, setDistricts] = useState([]);
    const [villages, setVillages] = useState([]);
    const [target, setTarget] = useState(initialTarget);
    const [targetLoading, setTargetLoading] = useState(false);
    const [targetError, setTargetError] = useState(false);
    const [locationError, setLocationError] = useState('');
    const [loadingDistricts, setLoadingDistricts] = useState(false);
    const [loadingVillages, setLoadingVillages] = useState(false);

    useEffect(() => {
        if (!data.regency_id) { setDistricts([]); setLoadingDistricts(false); return; }
        let active = true;
        setDistricts([]);
        setLocationError('');
        setLoadingDistricts(true);
        axios.get(route('locations.districts', data.regency_id))
            .then(({ data: rows }) => { if (active) setDistricts(rows); })
            .catch(() => { if (active) setLocationError('Kecamatan gagal dimuat. Muat ulang halaman untuk mencoba lagi.'); })
            .finally(() => { if (active) setLoadingDistricts(false); });
        return () => { active = false; };
    }, [data.regency_id]);
    useEffect(() => {
        if (!data.district_id) { setVillages([]); setLoadingVillages(false); return; }
        let active = true;
        setVillages([]);
        setLocationError('');
        setLoadingVillages(true);
        axios.get(route('locations.villages', data.district_id))
            .then(({ data: rows }) => { if (active) setVillages(rows); })
            .catch(() => { if (active) setLocationError('Desa/kelurahan gagal dimuat. Muat ulang halaman untuk mencoba lagi.'); })
            .finally(() => { if (active) setLoadingVillages(false); });
        return () => { active = false; };
    }, [data.district_id]);
    useEffect(() => {
        if (!data.regency_id || !data.year) { setTarget(null); return; }
        let active = true;
        setTargetLoading(true);
        setTargetError(false);
        axios.get(route('penanaman.targets.value'), { params: { regency_id: data.regency_id, year: data.year } })
            .then(({ data: value }) => { if (active) setTarget(value); })
            .catch(() => { if (active) { setTarget(null); setTargetError(true); } })
            .finally(() => { if (active) setTargetLoading(false); });
        return () => { active = false; };
    }, [data.regency_id, data.year]);

    const fundSourceOptions = sumberDana.map((item) => ({ value: item.name, label: item.name }));
    const districtOptions = districts.map((item) => ({ value: item.id, label: item.name }));
    const villageOptions = villages.map((item) => ({ value: item.id, label: item.name }));
    const menuPortalTarget = typeof document !== 'undefined' ? document.body : undefined;
    const changeRegency = (value) => {
        setDistricts([]);
        setVillages([]);
        setData((current) => ({ ...current, regency_id: value, district_id: '', village_id: '' }));
    };
    const changeDistrict = (value) => {
        setVillages([]);
        setData((current) => ({ ...current, district_id: value, village_id: '' }));
    };
    const save = (event) => {
        event.preventDefault();
        if (editing) put(route('penanaman.update', report.id));
        else post(route('penanaman.store'));
    };

    return <AuthenticatedLayout user={auth.user} header={<h2 className="text-xl font-semibold text-gray-800">{editing ? 'Ubah Draft' : 'Input Data Penanaman'}</h2>}>
        <Head title={editing ? 'Ubah Draft Penanaman' : 'Input Data Penanaman'} />
        <div className="mx-auto max-w-5xl space-y-5">
            <BackLink href={editing ? route('penanaman.show', report.id) : route('penanaman.index')} />
            <PageHero eyebrow="Data Penanaman" title={editing ? 'Perbarui laporan draft' : 'Formulir penanaman'} description="Isi data lokasi dan realisasi. Laporan dapat disimpan sebagai draft sebelum diajukan." />
            <form onSubmit={save} className="space-y-6">
                <SectionCard title="Waktu dan wilayah" description="Pilih periode dan wilayah lokasi penanaman.">
                    <div className="grid gap-5 p-5 sm:grid-cols-2 sm:p-7">
                        <label className="text-sm font-semibold text-gray-700">Tahun laporan<input type="number" min="2000" max="2100" value={data.year} onChange={(e) => setData('year', e.target.value)} className={fieldClass} required /><FieldError message={errors.year} /></label>
                        <label className="text-sm font-semibold text-gray-700">Bulan laporan<select value={data.month} onChange={(e) => setData('month', e.target.value)} className={fieldClass} required>{Array.from({ length: 12 }, (_, i) => <option key={i + 1} value={i + 1}>{new Date(2020, i).toLocaleString('id-ID', { month: 'long' })}</option>)}</select><FieldError message={errors.month} /></label>
                        <label className="text-sm font-semibold text-gray-700">Provinsi<input value="Jawa Timur" disabled className={fieldClass} /></label>
                        <div className="text-sm font-semibold text-gray-700">
                            <label htmlFor="report-regency">Kabupaten/Kota</label>
                            <RegencySelect id="report-regency" regencies={regencies} value={data.regency_id} isDisabled={!isAdmin} required
                                onChange={changeRegency} />
                            <FieldError message={errors.regency_id} />
                        </div>
                        <div className="text-sm font-semibold text-gray-700">
                            <label htmlFor="report-district">Kecamatan</label>
                            <div className="mt-2">
                                <Select inputId="report-district" name="district_id" options={districtOptions}
                                    value={districtOptions.find((option) => String(option.value) === String(data.district_id)) || null}
                                    onChange={(option) => changeDistrict(option?.value ?? '')}
                                    isDisabled={!data.regency_id || loadingDistricts} isLoading={loadingDistricts}
                                    placeholder="Pilih kecamatan" noOptionsMessage={() => 'Kecamatan tidak ditemukan'}
                                    isClearable isSearchable styles={selectStyles}
                                    menuPortalTarget={menuPortalTarget} menuPosition="fixed" />
                            </div>
                            <FieldError message={errors.district_id} />
                        </div>
                        <div className="text-sm font-semibold text-gray-700">
                            <label htmlFor="report-village">Desa/Kelurahan</label>
                            <div className="mt-2">
                                <Select inputId="report-village" name="village_id" options={villageOptions}
                                    value={villageOptions.find((option) => String(option.value) === String(data.village_id)) || null}
                                    onChange={(option) => setData('village_id', option?.value ?? '')}
                                    isDisabled={!data.district_id || loadingVillages} isLoading={loadingVillages}
                                    placeholder="Pilih desa/kelurahan" noOptionsMessage={() => 'Desa/kelurahan tidak ditemukan'}
                                    isClearable isSearchable styles={selectStyles}
                                    menuPortalTarget={menuPortalTarget} menuPosition="fixed" />
                            </div>
                            <FieldError message={errors.village_id} />
                        </div>
                        {locationError && <p role="alert" className="text-sm text-red-700 sm:col-span-2">{locationError}</p>}
                    </div>
                </SectionCard>

                <SectionCard title="Lokasi penanaman" description="Nama lokasi diperlukan saat laporan diajukan.">
                    <div className="grid gap-5 p-5 sm:grid-cols-2 sm:p-7">
                        <label className="text-sm font-semibold text-gray-700">Nama lokasi<input value={data.location_name} onChange={(e) => setData('location_name', e.target.value)} className={fieldClass} maxLength="255" placeholder="Contoh: Hutan Kota" /><FieldError message={errors.location_name} /></label>
                        <label className="text-sm font-semibold text-gray-700">Koordinat (opsional)<input value={data.coordinates} onChange={(e) => setData('coordinates', e.target.value)} className={fieldClass} maxLength="100" placeholder="-8.123, 111.456" /><FieldError message={errors.coordinates} /></label>
                    </div>
                </SectionCard>

                <SectionCard title="Target dan realisasi" description="Target tahunan ditampilkan sebagai acuan dan dihitung sekali dalam rekap.">
                    <div className="space-y-5 p-5 sm:p-7">
                        <div className="rounded-xl border border-primary-100 bg-primary-50 p-4 text-sm text-primary-900" aria-live="polite">
                            {targetLoading ? 'Memuat target tahunan...' : targetError ? 'Target gagal dimuat. Muat ulang halaman untuk mencoba lagi.' : target ? <>Target tahunan: <strong>{target.target_ha} Ha</strong>.</> : <>Target tahunan belum diisi. <Link href={route('penanaman.targets.index', { year: data.year })} className="font-bold underline">Isi target</Link> sebelum mengajukan.</>}
                        </div>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <label className="block text-sm font-semibold text-gray-700">Realisasi luas (Ha)<input type="number" step="0.01" min="0" value={data.realization_ha} onChange={(e) => setData('realization_ha', e.target.value)} className={fieldClass} /><FieldError message={errors.realization_ha} /></label>
                            <div className="text-sm font-semibold text-gray-700">
                                <label htmlFor="fund-source">Sumber dana</label>
                                <div className="mt-2">
                                    <Select inputId="fund-source" name="fund_source" options={fundSourceOptions}
                                        value={fundSourceOptions.find((option) => option.value === data.fund_source) || null}
                                        onChange={(option) => setData('fund_source', option?.value || '')}
                                        placeholder="Pilih sumber dana" noOptionsMessage={() => 'Sumber dana tidak ditemukan'}
                                        isClearable isSearchable styles={selectStyles}
                                        menuPortalTarget={menuPortalTarget}
                                        menuPosition="fixed" />
                                </div>
                                <FieldError message={errors.fund_source} />
                            </div>
                        </div>
                    </div>
                </SectionCard>
                <div className="flex flex-wrap justify-end gap-3 pb-4"><Link href={route('penanaman.index')} className={secondaryButton}>Batal</Link><button type="submit" disabled={processing} className={primaryButton}>{processing ? 'Menyimpan...' : 'Simpan Draft'}</button></div>
            </form>
        </div>
    </AuthenticatedLayout>;
}
